<?php

function validateLoginData(array $data): array
{
    $errors = [];

    if (($data['email'] ?? '') === '') {
        $errors['email'] = 'Введите логин.';
    } elseif (strlen((string)$data['email']) > 255) {
        $errors['email'] = 'Логин слишком длинный.';
    }

    if (($data['password'] ?? '') === '') {
        $errors['password'] = 'Введите пароль.';
    }

    return $errors;
}
