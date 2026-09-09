<?php

function validateStudentQuestionData(array $data): array
{
    $errors = [];

    if (($data['student_name'] ?? '') === '') {
        $errors['student_name'] = 'Введите имя.';
    }

    if (($data['question'] ?? '') === '') {
        $errors['question'] = 'Напишите вопрос.';
    } elseif (strlen($data['question']) < 5) {
        $errors['question'] = 'Вопрос слишком короткий.';
    }

    return $errors;
}
