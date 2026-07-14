<?php

require ROOT . 'app/services/file-upload.php';

$materialId = requireNumericId($segments[2] ?? null);
$material = getMaterialById($materialId);
requireFound($material);

if (!isPost()) {
    abort404();
}

$fileId = (int)($_POST['file_id'] ?? 0);
$file = getMaterialFileById($fileId);
requireFound($file);

if ((int)$file['material_id'] !== $materialId) {
    abort404();
}

deleteMaterialUploadedFile($materialId, $file['stored_name']);
deleteMaterialFile($fileId);

setFlash('admin', 'Файл удален.');
redirectTo('admin/materials/' . $materialId . '/edit');
