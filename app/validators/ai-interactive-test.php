<?php

function aiInteractiveTestFormData(array $post): array
{
    $questions = [];

    foreach (($post['questions'] ?? []) as $question) {
        $options = [];
        foreach (($question['options'] ?? []) as $option) {
            $options[] = trim((string)$option);
        }

        $questions[] = [
            'question_text' => trim((string)($question['question_text'] ?? '')),
            'options' => $options,
            'correct_option' => (int)($question['correct_option'] ?? 0),
        ];
    }

    return [
        'title' => trim((string)($post['title'] ?? '')),
        'class_title' => trim((string)($post['class_title'] ?? '')),
        'description' => trim((string)($post['description'] ?? '')),
        'questions' => $questions,
    ];
}

function normalizeAiInteractiveTestQuestions(array $questions): array
{
    $normalized = [];

    foreach ($questions as $question) {
        $questionText = trim((string)($question['question_text'] ?? ''));
        $options = array_values(array_filter(
            array_map(static fn($option): string => trim((string)$option), $question['options'] ?? []),
            static fn(string $option): bool => $option !== ''
        ));

        if ($questionText === '' && empty($options)) {
            continue;
        }

        $correctOption = (int)($question['correct_option'] ?? 0);
        if ($correctOption >= count($options)) {
            $correctOption = 0;
        }

        $normalized[] = [
            'question_text' => $questionText,
            'options' => $options,
            'correct_option' => $correctOption,
        ];
    }

    return $normalized;
}

function validateAiInteractiveTestData(array $data): array
{
    $errors = [];
    $questions = normalizeAiInteractiveTestQuestions($data['questions'] ?? []);

    if (($data['title'] ?? '') === '') {
        $errors['title'] = 'Введите название теста.';
    }

    if (($data['class_title'] ?? '') === '') {
        $errors['class_title'] = 'Выберите класс.';
    }

    if (count($questions) !== 10) {
        $errors['questions'] = 'В стандартном тесте должно быть ровно 10 вопросов.';
        return $errors;
    }

    foreach ($questions as $index => $question) {
        if ($question['question_text'] === '') {
            $errors['question_' . $index] = 'Заполните текст вопроса.';
        }

        if (count($question['options']) !== 2) {
            $errors['options_' . $index] = 'Добавьте ровно два варианта ответа.';
        }
    }

    return $errors;
}

function validateAiTestSubmissionData(array $data, array $questions): array
{
    $errors = [];

    if (($data['student_name'] ?? '') === '') {
        $errors['student_name'] = 'Введите имя.';
    }

    foreach ($questions as $question) {
        $questionId = (int)$question['id'];
        $selected = (int)($data['answers'][$questionId] ?? 0);

        if ($selected <= 0) {
            $errors['answers_' . $questionId] = 'Выберите ответ.';
        }
    }

    return $errors;
}
