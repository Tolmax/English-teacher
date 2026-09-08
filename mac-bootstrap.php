<?php

declare(strict_types=1);

const APP_NAME = 'OpenAI Presentation Generator';
define('APP_ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('ROOT', APP_ROOT);
define(
    'APP_DATA_ROOT',
    PHP_OS_FAMILY === 'Darwin'
        ? rtrim((string)getenv('HOME'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'Library' . DIRECTORY_SEPARATOR . 'Application Support' . DIRECTORY_SEPARATOR . 'EnglishPresentationGenerator' . DIRECTORY_SEPARATOR
        : APP_ROOT
);

loadLocalEnv(APP_DATA_ROOT . '.env');

function loadLocalEnv(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if ($key === '') {
            continue;
        }

        $value = trim($value, "\"'");
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }
}

function envValue(string $key, string $default = ''): string
{
    $value = getenv($key);
    return $value === false ? $default : (string)$value;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function normalizeText(string $value): string
{
    $value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);
    return $value;
}

function sourceWordsFromText(string $text): array
{
    $parts = preg_split('/[\r\n,;]+/u', $text) ?: [];
    $words = [];

    foreach ($parts as $part) {
        $word = normalizeText((string)$part);
        if ($word !== '') {
            $words[] = $word;
        }
    }

    return array_values(array_unique($words));
}

function safeFilePart(string $value): string
{
    $value = preg_replace('/[^A-Za-z0-9._-]+/', '_', $value) ?? 'presentation';
    $value = trim($value, '._-');
    return $value !== '' ? substr($value, 0, 80) : 'presentation';
}

function appUrlPath(string $absolutePath): string
{
    $relative = str_replace('\\', '/', substr($absolutePath, strlen(APP_ROOT . 'public' . DIRECTORY_SEPARATOR)));
    return '/' . ltrim($relative, '/');
}

function saveEnvValue(string $key, string $value): void
{
    $envPath = APP_DATA_ROOT . '.env';
    $lines = is_file($envPath) ? file($envPath, FILE_IGNORE_NEW_LINES) : [];

    if ($lines === false) {
        $lines = [];
    }

    $found = false;
    foreach ($lines as $index => $line) {
        if (preg_match('/^\s*' . preg_quote($key, '/') . '\s*=/', (string)$line)) {
            $lines[$index] = $key . '=' . $value;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $lines[] = $key . '=' . $value;
    }

    file_put_contents($envPath, implode(PHP_EOL, $lines) . PHP_EOL);
    putenv($key . '=' . $value);
    $_ENV[$key] = $value;
}

function ensureLocalDirectory(string $relativePath): string
{
    $path = APP_DATA_ROOT . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
    if (!is_dir($path)) {
        mkdir($path, 0775, true);
    }

    return $path;
}

function openLocalFolder(string $path): void
{
    if (!is_dir($path)) {
        return;
    }

    if (PHP_OS_FAMILY === 'Darwin') {
        $command = 'open ' . escapeshellarg($path);
        pclose(popen($command, 'r'));
        return;
    }

    if (PHP_OS_FAMILY !== 'Windows') {
        return;
    }

    $normalizedPath = str_replace('/', DIRECTORY_SEPARATOR, $path);
    $command = 'cmd.exe /d /c start "" explorer.exe "' . str_replace('"', '""', $normalizedPath) . '"';
    pclose(popen($command, 'r'));
}

function generatedPresentationPath(string $runId, string $fileName): string
{
    if (!preg_match('/^\d{8}_\d{6}_[a-f0-9]{6}$/', $runId)) {
        return '';
    }

    if ($fileName === '' || basename($fileName) !== $fileName || !str_ends_with(strtolower($fileName), '.pptx')) {
        return '';
    }

    $path = APP_DATA_ROOT . 'storage' . DIRECTORY_SEPARATOR . 'output' . DIRECTORY_SEPARATOR . $runId . DIRECTORY_SEPARATOR . $fileName;
    return is_file($path) ? $path : '';
}

function openLocalPresentation(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    if (PHP_OS_FAMILY === 'Darwin') {
        pclose(popen('open ' . escapeshellarg($path), 'r'));
        return;
    }

    if (PHP_OS_FAMILY === 'Windows') {
        $normalizedPath = str_replace('/', DIRECTORY_SEPARATOR, $path);
        $command = 'cmd.exe /d /c start "" "' . str_replace('"', '""', $normalizedPath) . '"';
        pclose(popen($command, 'r'));
    }
}

function revealLocalPresentation(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    if (PHP_OS_FAMILY === 'Darwin') {
        pclose(popen('open -R ' . escapeshellarg($path), 'r'));
        return;
    }

    if (PHP_OS_FAMILY === 'Windows') {
        $normalizedPath = str_replace('/', DIRECTORY_SEPARATOR, $path);
        $command = 'cmd.exe /d /c start "" explorer.exe /select,"' . str_replace('"', '""', $normalizedPath) . '"';
        pclose(popen($command, 'r'));
    }
}

function recentGeneratedPresentations(int $limit = 10): array
{
    $basePath = APP_DATA_ROOT . 'storage' . DIRECTORY_SEPARATOR . 'output';
    $paths = glob($basePath . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*.pptx') ?: [];

    usort($paths, static fn(string $left, string $right): int => filemtime($right) <=> filemtime($left));
    $presentations = [];

    foreach (array_slice($paths, 0, max(1, $limit)) as $path) {
        $runId = basename(dirname($path));
        $fileName = basename($path);
        if (generatedPresentationPath($runId, $fileName) === '') {
            continue;
        }

        $presentations[] = [
            'name' => $fileName,
            'date' => date('d.m.Y H:i', (int)filemtime($path)),
            'size' => formatBytes((int)filesize($path)),
            'open_url' => '/?action=open-presentation&run=' . rawurlencode($runId) . '&file=' . rawurlencode($fileName),
            'reveal_url' => '/?action=reveal-presentation&run=' . rawurlencode($runId) . '&file=' . rawurlencode($fileName),
            'download_url' => '/?action=download-presentation&run=' . rawurlencode($runId) . '&file=' . rawurlencode($fileName),
        ];
    }

    return $presentations;
}

function cleanupOldGeneratedFiles(int $days = 30): void
{
    $cutoff = time() - ($days * 86400);
    $targets = [
        APP_DATA_ROOT . 'storage' . DIRECTORY_SEPARATOR . 'images',
    ];

    foreach ($targets as $target) {
        if (!is_dir($target)) {
            continue;
        }

        foreach (new DirectoryIterator($target) as $item) {
            if ($item->isDot() || !$item->isDir()) {
                continue;
            }

            if ($item->getMTime() < $cutoff) {
                deleteDirectoryRecursive($item->getPathname());
            }
        }
    }
}

function generatedStoragePaths(): array
{
    return [
        APP_DATA_ROOT . 'storage' . DIRECTORY_SEPARATOR . 'output',
        APP_DATA_ROOT . 'storage' . DIRECTORY_SEPARATOR . 'images',
    ];
}

function generatedStorageLimitBytes(): int
{
    $limitMb = (int)envValue('LOCAL_STORAGE_LIMIT_MB', '500');
    if ($limitMb < 50) {
        $limitMb = 500;
    }

    return $limitMb * 1024 * 1024;
}

function generatedStorageStats(): array
{
    $paths = generatedStoragePaths();
    $total = 0;

    foreach ($paths as $path) {
        $total += directorySizeBytes($path);
    }

    return [
        'paths' => $paths,
        'total_bytes' => $total,
        'total_label' => formatBytes($total),
        'limit_bytes' => generatedStorageLimitBytes(),
        'limit_label' => formatBytes(generatedStorageLimitBytes()),
        'is_over_limit' => $total >= generatedStorageLimitBytes(),
    ];
}

function cleanupGeneratedStorage(): void
{
    foreach (generatedStoragePaths() as $path) {
        if (!is_dir($path)) {
            mkdir($path, 0775, true);
            continue;
        }

        foreach (new DirectoryIterator($path) as $item) {
            if ($item->isDot()) {
                continue;
            }

            $item->isDir()
                ? deleteDirectoryRecursive($item->getPathname())
                : unlink($item->getPathname());
        }
    }
}

function directorySizeBytes(string $path): int
{
    if (!is_dir($path)) {
        return 0;
    }

    $size = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $item) {
        if ($item->isFile()) {
            $size += $item->getSize();
        }
    }

    return $size;
}

function formatBytes(int $bytes): string
{
    if ($bytes >= 1073741824) {
        return round($bytes / 1073741824, 2) . ' GB';
    }

    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 1) . ' MB';
    }

    if ($bytes >= 1024) {
        return round($bytes / 1024, 1) . ' KB';
    }

    return $bytes . ' B';
}

function deleteDirectoryRecursive(string $path): void
{
    if (!is_dir($path)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    rmdir($path);
}
