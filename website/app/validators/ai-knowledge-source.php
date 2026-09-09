<?php

function validateAiKnowledgeSourceData(array $data, bool $requireFile = false): array
{
    $errors = [];
    $sourceTypes = ['text', 'file'];
    $statuses = ['active', 'draft', 'archived'];

    if (($data['title'] ?? '') === '') {
        $errors['title'] = 'Укажите название источника.';
    }

    if (!in_array(($data['source_type'] ?? ''), $sourceTypes, true)) {
        $errors['source_type'] = 'Выберите тип источника.';
    }

    if (!in_array(($data['status'] ?? ''), $statuses, true)) {
        $errors['status'] = 'Выберите корректный статус.';
    }

    if ((int)($data['category_id'] ?? 0) < 0) {
        $errors['category_id'] = 'Выберите корректную категорию.';
    }

    if (($data['source_type'] ?? '') === 'text' && trim((string)($data['extracted_text'] ?? '')) === '') {
        $errors['extracted_text'] = 'Для текстового источника добавьте содержимое.';
    }

    if ($requireFile && ($data['source_type'] ?? '') === 'file') {
        $errors['source_file'] = 'Для файлового источника прикрепите файл.';
    }

    if (aiKnowledgeSourceTextLength($data['title'] ?? '') > 255) {
        $errors['title'] = 'Название должно быть не длиннее 255 символов.';
    }

    if (aiKnowledgeSourceTextLength($data['description'] ?? '') > 1000) {
        $errors['description'] = 'Описание должно быть не длиннее 1000 символов.';
    }

    if (aiKnowledgeSourceTextLength($data['tags'] ?? '') > 500) {
        $errors['tags'] = 'Теги должны быть не длиннее 500 символов.';
    }

    return $errors;
}

function validateAiKnowledgeUploadFile(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return 'Ошибка загрузки файла "' . ($file['name'] ?? 'file') . '".';
    }

    if ((int)($file['size'] ?? 0) > aiKnowledgeMaxFileSize()) {
        return 'Файл "' . ($file['name'] ?? 'file') . '" больше допустимого размера.';
    }

    $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    $allowedExtensions = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($extension, $allowedExtensions, true)) {
        return 'Недопустимое расширение файла "' . ($file['name'] ?? 'file') . '".';
    }

    if (!in_array(getAiKnowledgeUploadedFileMimeType($file), aiKnowledgeAllowedMimeTypes(), true)) {
        return 'Недопустимый тип файла "' . ($file['name'] ?? 'file') . '".';
    }

    return null;
}

function aiKnowledgeSourceFormData(): array
{
    return [
        'title' => trim((string)($_POST['title'] ?? '')),
        'category_id' => (int)($_POST['category_id'] ?? 0),
        'source_type' => trim((string)($_POST['source_type'] ?? 'text')),
        'description' => trim((string)($_POST['description'] ?? '')),
        'tags' => trim((string)($_POST['tags'] ?? '')),
        'status' => trim((string)($_POST['status'] ?? 'active')),
        'extracted_text' => trim((string)($_POST['extracted_text'] ?? '')),
        'created_by' => (int)($_SESSION['admin_user_id'] ?? 0),
    ];
}

function aiKnowledgeSourceTextLength(string $value): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($value);
    }

    return strlen($value);
}
