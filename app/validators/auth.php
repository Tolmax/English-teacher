<?php

function validateLoginData(array $data): array
{
    $errors = [];

    if (($data['email'] ?? '') === '') {
        $errors['email'] = 'Введите email.';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Введите корректный email.';
    }

    if (($data['password'] ?? '') === '') {
        $errors['password'] = 'Введите пароль.';
    }

    return $errors;
}
