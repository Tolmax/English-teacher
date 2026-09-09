<?php

function isAdmin(): bool
{
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

function requireAuth(): void
{
    if (!isAdmin()) {
        rememberAdminReturnPath();
        header('Location: ' . HOST . 'admin/login');
        exit;
    }
}

function rememberAdminReturnPath(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        return;
    }

    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

    if (!is_string($path)) {
        return;
    }

    $adminPosition = strpos($path, '/admin/');

    if ($adminPosition === false) {
        return;
    }

    $returnPath = ltrim(substr($path, $adminPosition + 1), '/');

    if ($returnPath === 'admin/login' || $returnPath === 'admin/logout') {
        return;
    }

    $_SESSION['admin_return_to'] = $returnPath;
}

function isUser(): bool
{
    return isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0;
}

function requireUser(): void
{
    if (!isUser()) {
        header('Location: ' . HOST . 'login');
        exit;
    }
}

function getCurrentUserId(): ?int
{
    return isUser() ? (int)$_SESSION['user_id'] : null;
}
