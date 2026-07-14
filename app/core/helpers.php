<?php

function abort404(): never
{
    http_response_code(404);
    renderTemplate('pages/404.tpl');
    exit;
}

function requireNumericId(mixed $value): int
{
    if (!is_numeric($value) || (int)$value <= 0) {
        abort404();
    }
    return (int)$value;
}

function requireFound(mixed $value): void
{
    if (empty($value)) {
        abort404();
    }
}

function formatPrice(int $price): string
{
    return number_format($price, 0, '', ' ') . ' ₽';
}

/*
Защита от XSS-атак.

Функция e() — это короткий alias для экранирования HTML-вывода.

Что делает:
Преобразует специальные HTML-символы в их безопасные сущности перед выводом в браузер.

Любые пользовательские данные (из БД, из $_POST, из $_GET)
перед выводом в шаблоне должны проходить через e():
<h1><?= e($product['title']) ?></h1>
*/
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirectTo(string $path): never
{
    header('Location: ' . HOST . ltrim($path, '/'));
    exit;
}

function isPost(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function isGet(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/*
setFlash() сохраняет одноразовое сообщение в сессии,
которое нужно показать пользователю после редиректа.
*/
function setFlash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function getFlash(string $key): ?string
{
    $message = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $message;
}

require ROOT . 'app/helpers/mail-logger.php';
