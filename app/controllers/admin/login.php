<?php

require ROOT . 'app/models/user.php';
require ROOT . 'app/validators/auth.php';

if (isAdmin()) {
    redirectTo('admin/dashboard');
}

$errors = [];
$old = ['email' => ''];

if (isPost()) {
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $old = ['email' => $email];
    $errors = validateLoginData([
        'email' => $email,
        'password' => $password,
    ]);

    if (empty($errors)) {
        $user = getUserByEmail($email);

        if ($user === false || !password_verify($password, $user['password_hash'])) {
            $errors['form'] = 'Неверный email или пароль.';
        } else {
            $_SESSION['is_admin'] = true;
            $_SESSION['admin_user_id'] = (int)$user['id'];
            $_SESSION['admin_name'] = $user['name'];
            $returnTo = getSafeAdminReturnPath();
            setFlash('admin', 'Вы вошли в панель учителя.');
            redirectTo($returnTo);
        }
    }
}

function getSafeAdminReturnPath(): string
{
    $returnTo = (string)($_SESSION['admin_return_to'] ?? 'admin/dashboard');
    unset($_SESSION['admin_return_to']);

    if (!str_starts_with($returnTo, 'admin/') || str_contains($returnTo, '//')) {
        return 'admin/dashboard';
    }

    if ($returnTo === 'admin/login' || $returnTo === 'admin/logout') {
        return 'admin/dashboard';
    }

    return $returnTo;
}

renderTemplate('pages/admin/login.tpl', [
    'errors' => $errors,
    'old' => $old,
    'flash' => getFlash('admin'),
]);
