<?php

function validateAiTeachingMaterialData(array $data): array
{
    $errors = [];
    $types = ['worksheet', 'quiz', 'lesson_plan', 'homework', 'explanation'];
    $languages = ['ru', 'en'];
    $statuses = ['draft', 'ready', 'published', 'archived', 'error'];

    if (aiTeachingMaterialTextLength((string)($data['lesson_reference'] ?? '')) > 160) {
        $errors['lesson_reference'] = 'Номер или название урока — не более 160 символов.';
    }
    if (!empty($data['lesson_reference']) && empty($data['knowledge_source_ids'])) {
        $errors['lesson_reference'] = 'Выберите учебник из базы знаний для указанного урока.';
    }
    if (!empty($data['knowledge_source_ids']) && array_key_exists('lesson_reference', $data) && $data['lesson_reference'] === '') {
        $errors['lesson_reference'] = 'Укажите номер или название урока.';
    }

    if (($data['subject'] ?? '') === '') {
        $errors['subject'] = 'Укажите предмет.';
    }

    if (($data['class_title'] ?? '') === '') {
        $errors['class_title'] = 'Выберите класс.';
    }

    if (($data['topic'] ?? '') === '') {
        $errors['topic'] = 'Укажите тему материала.';
    }

    if (!in_array(($data['material_type'] ?? ''), $types, true)) {
        $errors['material_type'] = 'Выберите тип материала.';
    }

    if (!in_array(($data['language'] ?? ''), $languages, true)) {
        $errors['language'] = 'Выберите язык материала.';
    }

    if (!in_array(($data['status'] ?? ''), $statuses, true)) {
        $errors['status'] = 'Выберите корректный статус.';
    }

    if (aiTeachingMaterialTextLength($data['title'] ?? '') > 320) {
        $errors['title'] = 'Название должно быть не длиннее 320 символов.';
    }

    if (aiTeachingMaterialTextLength($data['school_year'] ?? '') > 40) {
        $errors['school_year'] = 'Учебный год должен быть не длиннее 40 символов.';
    }

    if (aiTeachingMaterialTextLength($data['period'] ?? '') > 80) {
        $errors['period'] = 'Период должен быть не длиннее 80 символов.';
    }

    if (aiTeachingMaterialTextLength($data['tags'] ?? '') > 500) {
        $errors['tags'] = 'Теги должны быть не длиннее 500 символов.';
    }

    return $errors;
}

function aiTeachingMaterialFormData(): array
{
    $topic = trim((string)($_POST['topic'] ?? ''));
    $type = trim((string)($_POST['material_type'] ?? 'worksheet'));
    $title = trim((string)($_POST['title'] ?? ''));

    if ($title === '' && $topic !== '') {
        $title = aiTeachingMaterialTypeLabel($type) . ': ' . $topic;
    }

    return [
        'title' => $title,
        'subject' => trim((string)($_POST['subject'] ?? 'Английский язык')),
        'class_title' => trim((string)($_POST['class_title'] ?? '')),
        'topic' => $topic,
        'material_type' => $type,
        'difficulty' => '',
        'estimated_duration' => trim((string)($_POST['estimated_duration'] ?? '')),
        'language' => trim((string)($_POST['language'] ?? 'ru')),
        'instructions' => trim((string)($_POST['instructions'] ?? '')),
        'source_notes' => trim((string)($_POST['source_notes'] ?? '')),
        'edited_content' => trim((string)($_POST['edited_content'] ?? '')),
        'status' => trim((string)($_POST['status'] ?? 'draft')),
        'school_year' => trim((string)($_POST['school_year'] ?? '')),
        'period' => trim((string)($_POST['period'] ?? '')),
        'tags' => trim((string)($_POST['tags'] ?? '')),
        'source_material_id' => (int)($_POST['source_material_id'] ?? 0),
        'knowledge_source_ids' => aiTeachingMaterialKnowledgeSourceIdsFromPost(),
        'created_by' => (int)($_SESSION['admin_user_id'] ?? 0),
    ];
}

function aiTeachingMaterialDuplicateFormData(array $sourceMaterial): array
{
    $topic = trim((string)($_POST['topic'] ?? $sourceMaterial['topic']));
    $type = (string)$sourceMaterial['material_type'];
    $title = trim((string)($_POST['title'] ?? $sourceMaterial['title']));

    if ($title === '' && $topic !== '') {
        $title = aiTeachingMaterialTypeLabel($type) . ': ' . $topic;
    }

    return [
        'title' => $title,
        'subject' => $sourceMaterial['subject'],
        'class_title' => trim((string)($_POST['class_title'] ?? $sourceMaterial['class_title'])),
        'topic' => $topic,
        'material_type' => $type,
        'difficulty' => '',
        'estimated_duration' => $sourceMaterial['estimated_duration'] ?? '',
        'language' => $sourceMaterial['language'],
        'instructions' => $sourceMaterial['instructions'] ?? '',
        'source_notes' => $sourceMaterial['source_notes'] ?? '',
        'edited_content' => $sourceMaterial['edited_content'] ?? '',
        'status' => trim((string)($sourceMaterial['edited_content'] ?? '')) !== '' ? 'ready' : 'draft',
        'school_year' => trim((string)($_POST['school_year'] ?? $sourceMaterial['school_year'] ?? '')),
        'period' => trim((string)($_POST['period'] ?? $sourceMaterial['period'] ?? '')),
        'tags' => trim((string)($_POST['tags'] ?? $sourceMaterial['tags'] ?? '')),
        'source_material_id' => (int)$sourceMaterial['id'],
        'knowledge_source_ids' => aiTeachingMaterialKnowledgeSourceIdsFromPost(),
        'created_by' => (int)($_SESSION['admin_user_id'] ?? 0),
    ];
}

function aiTeachingMaterialKnowledgeSourceIdsFromPost(): array
{
    $sourceIds = $_POST['knowledge_source_ids'] ?? [];

    if (!is_array($sourceIds)) {
        return [];
    }

    return array_values(array_unique(array_filter(array_map('intval', $sourceIds))));
}

function aiTeachingMaterialTextLength(string $value): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($value);
    }

    return strlen($value);
}
