<?php

define('ROOT', dirname(__DIR__) . '/');
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? '') === '443')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    || (($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on');
$scheme = $isHttps ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$basePath = ($basePath === '/' || $basePath === '.' || $basePath === '\\') ? '' : rtrim($basePath, '/');

define('HOST', $scheme . '://' . $host . $basePath . '/');

function loadEnvFile(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
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
        $_SERVER[$key] = $value;
    }
}

loadEnvFile(ROOT . '.env');

// Admin credentials
// Default: login = admin, password = admin
// To change the password, generate a new hash: php -r "echo password_hash('your_password', PASSWORD_BCRYPT, ['cost' => 12]);"
define('ADMIN_LOGIN', 'admin');
define('ADMIN_PASSWORD_HASH', '$2y$12$nYSCc/rWPqoA.SULF6ix9.02VlP1by7rxAcfUm8RTE9rE7zJhRHlm');

// Admin email вЂ” receives order notifications
define('ADMIN_EMAIL', 'admin@site.com');

define('OPENAI_API_KEY', (string)(getenv('OPENAI_API_KEY') ?: ''));
define('OPENAI_MODEL', (string)(getenv('OPENAI_MODEL') ?: 'gpt-4.1-mini'));
define('OPENAI_IMAGE_MODEL', (string)(getenv('OPENAI_IMAGE_MODEL') ?: 'gpt-image-2'));
define('AI_TEXT_PROVIDER', strtolower((string)(getenv('AI_TEXT_PROVIDER') ?: 'yandex')));
define('AI_IMAGE_PROVIDER', strtolower((string)(getenv('AI_IMAGE_PROVIDER') ?: 'yandex')));
define('YANDEX_API_KEY', (string)(getenv('YANDEX_API_KEY') ?: ''));
define('YANDEX_FOLDER_ID', (string)(getenv('YANDEX_FOLDER_ID') ?: 'b1gun8kk36mc31tlbgpt'));
session_start();

