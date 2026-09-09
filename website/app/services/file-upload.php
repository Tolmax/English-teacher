<?php

function normalizeUploadedFiles(array $fileInput): array
{
    if (!isset($fileInput['name']) || !is_array($fileInput['name'])) {
        return ($fileInput['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE ? [] : [$fileInput];
    }

    $files = [];

    foreach ($fileInput['name'] as $index => $name) {
        $files[] = [
            'name' => $name,
            'type' => $fileInput['type'][$index] ?? '',
            'tmp_name' => $fileInput['tmp_name'][$index] ?? '',
            'error' => $fileInput['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $fileInput['size'][$index] ?? 0,
        ];
    }

    return array_values(array_filter(
        $files,
        static fn(array $file): bool => ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
    ));
}

function sanitizeUploadedFilename(string $originalName, string $mimeType): string
{
    $extensionsByMime = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'text/plain' => 'txt',
    ];
    $extension = $extensionsByMime[$mimeType] ?? strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $extension = preg_replace('/[^a-z0-9]/', '', (string)$extension);

    if ($extension === '') {
        $extension = 'file';
    }

    return uniqid('material_', true) . '.' . $extension;
}

function getUploadedFileMimeType(array $file): string
{
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return (string)$finfo->file($file['tmp_name']);
    }

    return (string)($file['type'] ?? 'application/octet-stream');
}

function saveMaterialUploadedFile(array $file, int $materialId): array|false
{
    $mimeType = getUploadedFileMimeType($file);
    $storedName = sanitizeUploadedFilename((string)$file['name'], (string)$mimeType);
    $dir = ROOT . 'uploads/materials/' . $materialId . '/';

    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return false;
    }

    if (!move_uploaded_file($file['tmp_name'], $dir . $storedName)) {
        return false;
    }

    return [
        'material_id' => $materialId,
        'original_name' => (string)$file['name'],
        'stored_name' => $storedName,
        'mime_type' => (string)$mimeType,
        'file_size' => (int)$file['size'],
    ];
}

function hasUploadedSingleFile(array|null $file): bool
{
    return is_array($file)
        && (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
}

function validatePptxUploadedFile(array $file): string
{
    $errorCode = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($errorCode === UPLOAD_ERR_NO_FILE) {
        return 'Выберите PPTX-файл презентации.';
    }

    if ($errorCode !== UPLOAD_ERR_OK) {
        return 'Не удалось загрузить файл. Попробуйте выбрать презентацию ещё раз.';
    }

    if ((int)($file['size'] ?? 0) <= 0) {
        return 'Файл презентации пустой.';
    }

    if ((int)$file['size'] > 50 * 1024 * 1024) {
        return 'Размер PPTX-файла не должен превышать 50 МБ.';
    }

    $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));

    if ($extension !== 'pptx') {
        return 'Загрузите файл в формате PPTX.';
    }

    return '';
}

function deleteMaterialUploadedFile(int $materialId, string $storedName): void
{
    $path = ROOT . 'uploads/materials/' . $materialId . '/' . $storedName;

    if (is_file($path)) {
        unlink($path);
    }
}

function deleteMaterialUploadedDirectory(int $materialId): bool
{
    if ($materialId <= 0) {
        return true;
    }

    $basePath = ROOT . 'uploads/materials/';
    $targetPath = $basePath . $materialId;
    $baseDir = realpath($basePath);
    $targetDir = realpath($targetPath);

    if ($targetDir === false) {
        return true;
    }

    if ($baseDir === false || !str_starts_with($targetDir, $baseDir)) {
        return false;
    }

    $deleted = true;

    try {
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($targetDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
    } catch (Throwable) {
        return false;
    }

    foreach ($items as $item) {
        $path = $item->getPathname();

        if ($item->isDir()) {
            if (is_dir($path) && !@rmdir($path)) {
                $deleted = false;
            }
            continue;
        }

        if (is_file($path) && !@unlink($path)) {
            $deleted = false;
        }
    }

    if (is_dir($targetDir) && !@rmdir($targetDir)) {
        $deleted = false;
    }

    return $deleted && !is_dir($targetDir);
}
