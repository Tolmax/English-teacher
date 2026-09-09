<?php

function getUserByLogin(string $login): array|false
{
    $stmt = getDB()->prepare('SELECT * FROM users WHERE COALESCE(login, email) = :login LIMIT 1');
    $stmt->execute([':login' => $login]);

    return $stmt->fetch();
}

function getUserByEmail(string $email): array|false
{
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);

    return $stmt->fetch();
}

function getUserById(int $id): array|false
{
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    return $stmt->fetch();
}

function createUser(array $data): int
{
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO users (email, password_hash, name, role)
         VALUES (:email, :password_hash, :name, :role)'
    );
    $stmt->execute([
        ':email' => $data['email'],
        ':password_hash' => $data['password_hash'],
        ':name' => $data['name'],
        ':role' => $data['role'] ?? 'teacher',
    ]);

    return (int)$db->lastInsertId();
}
