<?php

function validateUploadedImage(array $file): ?string
{
    $allowedMime = ['image/jpeg', 'image/png'];
    $maxSize     = 5 * 1024 * 1024; // 5 MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return 'Ошибка загрузки файла (код ' . (int)$file['error'] . ')';
    }
    if ($file['size'] > $maxSize) {
        return 'Файл слишком большой. Максимум 5 МБ';
    }

    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedMime, true)) {
        return 'Допускаются только изображения JPG и PNG';
    }

    return null;
}

function sanitizeImageFilename(string $originalName): string
{
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
        $ext = 'jpg';
    }
    return uniqid('img_', true) . '.' . $ext;
}

function saveProductImage(array $file, int $productId): string|false
{
    $dir = ROOT . 'uploads/products/' . $productId . '/';

    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return false;
    }

    $filename = sanitizeImageFilename($file['name']);
    $dest     = $dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return false;
    }

    return $filename;
}

function deleteProductImageFile(int $productId, string $filename): void
{
    $path = ROOT . 'uploads/products/' . $productId . '/' . $filename;
    if (file_exists($path)) {
        unlink($path);
    }
}
