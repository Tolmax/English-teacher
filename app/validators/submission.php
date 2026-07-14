<?php

function validateSubmissionData(array $data): array
{
    $errors = [];

    if ((int)($data['task_id'] ?? 0) <= 0) {
        $errors['task_id'] = 'Задание не найдено.';
    }

    if ((int)($data['class_id'] ?? 0) <= 0) {
        $errors['class_id'] = 'Класс не найден.';
    }

    if (($data['student_name'] ?? '') === '') {
        $errors['student_name'] = 'Введите имя.';
    }

    return $errors;
}

function validatePracticeSubmissionData(array $data, array $questions): array
{
    $errors = validateSubmissionData($data);

    foreach ($questions as $question) {
        $questionId = (int)$question['id'];

        if (!isset($data['answers'][$questionId]) || !is_numeric($data['answers'][$questionId])) {
            $errors['answers_' . $questionId] = 'Выберите ответ.';
        }
    }

    return $errors;
}
