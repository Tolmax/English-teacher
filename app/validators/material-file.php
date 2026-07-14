<?php

function validateMaterialUploadFile(array $file): ?string
{
    $allowedMime = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
    ];
    $maxSize = 10 * 1024 * 1024;

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return 'Ошибка загрузки файла "' . ($file['name'] ?? 'file') . '".';
    }

    if ((int)$file['size'] > $maxSize) {
        return 'Файл "' . $file['name'] . '" больше 10 МБ.';
    }

    $mimeType = detectUploadedFileMimeType($file);
    $extension = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'txt'];

    if (!in_array($mimeType, $allowedMime, true) || !in_array($extension, $allowedExtensions, true)) {
        return 'Недопустимый тип файла "' . $file['name'] . '".';
    }

    return null;
}

function detectUploadedFileMimeType(array $file): string
{
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return (string)$finfo->file($file['tmp_name']);
    }

    return (string)($file['type'] ?? 'application/octet-stream');
}

function validateMaterialUploadFiles(array $files): array
{
    $errors = [];

    foreach ($files as $file) {
        $error = validateMaterialUploadFile($file);

        if ($error !== null) {
            $errors[] = $error;
        }
    }

    return $errors;
}
