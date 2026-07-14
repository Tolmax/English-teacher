<?php

function normalizeLearningClassSlug(string $slug): string
{
    $slug = strtolower(trim($slug));
    $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
    $slug = trim((string)$slug, '-');

    return $slug;
}

function validateLearningClassData(array $data): array
{
    $errors = [];

    if (($data['title'] ?? '') === '') {
        $errors['title'] = 'Введите название класса.';
    }

    if (($data['slug'] ?? '') === '') {
        $errors['slug'] = 'Введите адрес страницы класса.';
    } elseif (!preg_match('/^[a-z0-9-]+$/', $data['slug'])) {
        $errors['slug'] = 'Используйте латинские буквы, цифры и дефис.';
    }

    return $errors;
}
