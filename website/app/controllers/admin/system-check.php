<?php

function checkSystemPath(string $label, string $relativePath): array
{
    $absolutePath = ROOT . $relativePath;

    return [
        'label' => $label,
        'path' => $relativePath,
        'exists' => file_exists($absolutePath),
        'readable' => is_readable($absolutePath),
        'writable' => is_writable($absolutePath),
        'type' => is_dir($absolutePath) ? 'directory' : 'file',
    ];
}

$pdoDrivers = class_exists('PDO') ? PDO::getAvailableDrivers() : [];

$checks = [
    [
        'label' => 'PHP',
        'value' => PHP_VERSION,
        'ok' => version_compare(PHP_VERSION, '8.0.0', '>='),
        'note' => 'Нужна версия PHP 8.x или выше.',
    ],
    [
        'label' => 'PDO',
        'value' => extension_loaded('pdo') ? 'доступен' : 'не найден',
        'ok' => extension_loaded('pdo'),
        'note' => 'PDO нужен для работы с базой данных.',
    ],
    [
        'label' => 'SQLite',
        'value' => extension_loaded('sqlite3') ? 'доступен' : 'не найден',
        'ok' => extension_loaded('sqlite3'),
        'note' => 'SQLite используется как основная база сайта.',
    ],
    [
        'label' => 'PDO SQLite',
        'value' => in_array('sqlite', $pdoDrivers, true) ? 'доступен' : 'не найден',
        'ok' => in_array('sqlite', $pdoDrivers, true),
        'note' => 'PDO SQLite нужен для подключения к database.sqlite.',
    ],
    [
        'label' => '.env',
        'value' => is_file(ROOT . '.env') ? 'найден' : 'не найден',
        'ok' => is_file(ROOT . '.env'),
        'note' => 'Файл .env должен лежать рядом с index.php, если нужен OpenAI API.',
    ],
    [
        'label' => 'OpenAI режим',
        'value' => trim((string)OPENAI_API_KEY) !== '' ? 'OpenAI API' : 'mock',
        'ok' => true,
        'note' => 'Ключ не показывается. Отображается только режим работы.',
    ],
];

$paths = [
    checkSystemPath('SQLite база', 'database/database.sqlite'),
    checkSystemPath('Папка database', 'database'),
    checkSystemPath('Папка uploads', 'uploads'),
    checkSystemPath('Папка storage', 'storage'),
    checkSystemPath('Папка storage/cache', 'storage/cache'),
    checkSystemPath('Папка storage/logs', 'storage/logs'),
    checkSystemPath('Папка storage/temp', 'storage/temp'),
];

renderTemplate('pages/admin/system-check.tpl', [
    'checks' => $checks,
    'paths' => $paths,
]);
