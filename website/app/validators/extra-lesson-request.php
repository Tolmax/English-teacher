<?php

function validateExtraLessonRequestData(array $data): array
{
    $errors = [];

    if (trim((string)($data['student_name'] ?? '')) === '') {
        $errors['student_name'] = 'Укажите имя ученика.';
    }

    if (trim((string)($data['student_contact'] ?? '')) === '') {
        $errors['student_contact'] = 'Укажите контакт для связи.';
    }

    if (!isValidExtraLessonFormat(trim((string)($data['lesson_format'] ?? '')))) {
        $errors['lesson_format'] = 'Выберите формат занятия.';
    }

    if (trim((string)($data['topic'] ?? '')) === '') {
        $errors['topic'] = 'Напишите, какую тему нужно разобрать.';
    }

    return $errors;
}

function isValidExtraLessonFormat(string $format): bool
{
    return in_array($format, ['individual', 'mini_group', 'before_test'], true);
}

function getExtraLessonFormatLabel(string $format): string
{
    return [
        'individual' => 'Индивидуально',
        'mini_group' => 'Мини-группа',
        'before_test' => 'Перед контрольной',
    ][$format] ?? 'Не выбран';
}

function isValidExtraLessonRequestStatus(string $status): bool
{
    return in_array($status, ['new', 'processed'], true);
}
