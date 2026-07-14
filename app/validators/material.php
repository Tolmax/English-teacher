<?php

function validateMaterialData(array $data): array
{
    $errors = [];
    $types = ['lesson', 'homework', 'announcement'];
    $statuses = ['new', 'important', 'repeat', 'control', 'deadline'];

    if ((int)($data['class_id'] ?? 0) <= 0) {
        $errors['class_id'] = 'Выберите класс.';
    }

    if (!in_array(($data['type'] ?? ''), $types, true)) {
        $errors['type'] = 'Выберите тип материала.';
    }

    if (!in_array(($data['status'] ?? ''), $statuses, true)) {
        $errors['status'] = 'Выберите статус.';
    }

    if (($data['deadline_at'] ?? '') !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['deadline_at'])) {
        $errors['deadline_at'] = 'Укажите дату в формате ГГГГ-ММ-ДД.';
    }

    return $errors;
}

function materialDefaultTitle(string $type, string $lessonDate = ''): string
{
    $titles = [
        'lesson' => 'Урок',
        'homework' => 'Домашка',
        'announcement' => 'Объявление',
    ];

    return $titles[$type] ?? 'Материал';
}

function materialDefaultStatus(string $type): string
{
    return match ($type) {
        'homework' => 'deadline',
        'announcement' => 'important',
        default => 'new',
    };
}
