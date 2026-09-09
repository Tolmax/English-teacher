<?php

function saveAiKnowledgeUploadedFile(array $file): array|false
{
    if (!isAiKnowledgeUploadedFileAllowed($file)) {
        return false;
    }

    $mimeType = getAiKnowledgeUploadedFileMimeType($file);
    $storedName = sanitizeAiKnowledgeUploadedFilename((string)$file['name'], $mimeType);
    $dir = ROOT . 'uploads/ai-knowledge/';

    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return false;
    }

    $targetPath = $dir . $storedName;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return false;
    }

    return [
        'original_name' => (string)$file['name'],
        'stored_name' => $storedName,
        'storage_path' => 'uploads/ai-knowledge/' . $storedName,
        'mime_type' => $mimeType,
        'file_size' => (int)$file['size'],
    ];
}

function isAiKnowledgeUploadedFileAllowed(array $file): bool
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return false;
    }

    if ((int)($file['size'] ?? 0) > aiKnowledgeMaxFileSize()) {
        return false;
    }

    $allowedExtensions = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'webp'];
    $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        return false;
    }

    return in_array(getAiKnowledgeUploadedFileMimeType($file), aiKnowledgeAllowedMimeTypes(), true);
}

function sanitizeAiKnowledgeUploadedFilename(string $originalName, string $mimeType): string
{
    $extensionsByMime = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'text/plain' => 'txt',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $extension = $extensionsByMime[$mimeType] ?? strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $extension = preg_replace('/[^a-z0-9]/', '', (string)$extension);

    if ($extension === '') {
        $extension = 'file';
    }

    return uniqid('knowledge_', true) . '.' . $extension;
}

function getAiKnowledgeUploadedFileMimeType(array $file): string
{
    if (class_exists('finfo') && is_file((string)($file['tmp_name'] ?? ''))) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return (string)$finfo->file($file['tmp_name']);
    }

    return (string)($file['type'] ?? 'application/octet-stream');
}

function aiKnowledgeAllowedMimeTypes(): array
{
    return [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];
}

function aiKnowledgeMaxFileSize(): int
{
    $sizeMb = (int)(getenv('AI_MAX_SOURCE_FILE_SIZE_MB') ?: 20);

    return max(1, $sizeMb) * 1024 * 1024;
}
