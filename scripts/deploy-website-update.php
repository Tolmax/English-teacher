<?php
// Executed from a private deployment directory, never under the web root.
$root = '/home/c/cr77641/My_Projects/public_html/english/';
$manifest = json_decode(file_get_contents(__DIR__ . '/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
$settings = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
if (empty($settings['YANDEX_API_KEY']) || preg_match('/[\r\n]/', $settings['YANDEX_API_KEY']) || !preg_match('/^[a-z0-9]{10,64}$/D', $settings['YANDEX_FOLDER_ID'] ?? '')) {
    throw new RuntimeException('Invalid Yandex settings');
}
foreach (['curl', 'mbstring', 'zip', 'gd'] as $extension) {
    if (!extension_loaded($extension)) throw new RuntimeException('Missing PHP extension: ' . $extension);
}
foreach ($manifest as $item) {
    $path = $item['path'];
    if (str_contains($path, '..') || str_starts_with($path, '/') || !preg_match('~^(app/|config/|templates/|assets/css/blocks/presentation-slides\.css$|\.htaccess$)~', $path)) throw new RuntimeException('Invalid update path');
    $current = is_file($root . $path) ? hash_file('sha256', $root . $path) : null;
    if ($current !== $item['before']) throw new RuntimeException('Site changed since download: ' . $path);
    if ($item['after'] !== null && hash_file('sha256', __DIR__ . '/files/' . $path) !== $item['after']) throw new RuntimeException('Package integrity failure');
}
$backup = '/home/c/cr77641/deploy-upload/english-yandex-backup-' . date('Ymd-His') . '/';
umask(0077);
if (!mkdir($backup, 0700, true)) throw new RuntimeException('Cannot create backup');
$env = is_file($root . '.env') ? file_get_contents($root . '.env') : '';
file_put_contents($backup . '.env', $env);
file_put_contents($backup . 'manifest.json', json_encode($manifest));
foreach ($manifest as $item) {
    if ($item['before'] === null) continue;
    $destination = $backup . $item['path'];
    if (!is_dir(dirname($destination))) mkdir(dirname($destination), 0700, true);
    if (!copy($root . $item['path'], $destination)) throw new RuntimeException('Backup failed');
}
$applied = [];
try {
    foreach ($manifest as $item) {
        $path = $root . $item['path'];
        $applied[] = $item;
        if ($item['after'] === null) {
            if (!unlink($path)) throw new RuntimeException('Cannot remove obsolete service');
            continue;
        }
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
        $temp = $path . '.yandex-new';
        if (!copy(__DIR__ . '/files/' . $item['path'], $temp)) throw new RuntimeException('Copy failed');
        chmod($temp, 0644);
        if (!rename($temp, $path)) throw new RuntimeException('Replace failed');
    }
    $lines = preg_split('/\r?\n/', $env);
    $lines = array_filter($lines, static fn($line) => !preg_match('/^\s*(GIGACHAT_[A-Z_]+|YANDEX_API_KEY|YANDEX_FOLDER_ID|AI_TEXT_PROVIDER|AI_IMAGE_PROVIDER)\s*=/', $line));
    $lines[] = 'YANDEX_API_KEY=' . $settings['YANDEX_API_KEY'];
    $lines[] = 'YANDEX_FOLDER_ID=' . $settings['YANDEX_FOLDER_ID'];
    $lines[] = 'AI_TEXT_PROVIDER=yandex';
    $lines[] = 'AI_IMAGE_PROVIDER=yandex';
    $temp = $root . '.env.yandex-new';
    if (file_put_contents($temp, implode("\n", $lines) . "\n") === false) throw new RuntimeException('Cannot save config');
    chmod($temp, 0600);
    if (!rename($temp, $root . '.env')) throw new RuntimeException('Cannot replace config');
} catch (Throwable $error) {
    foreach (array_reverse($applied) as $item) {
        if ($item['before'] === null) {
            if (is_file($root . $item['path'])) unlink($root . $item['path']);
        } else {
            copy($backup . $item['path'], $root . $item['path']);
        }
    }
    copy($backup . '.env', $root . '.env');
    throw $error;
}
echo 'Deployed ' . count($manifest) . ' files. Backup: ' . $backup . PHP_EOL;
