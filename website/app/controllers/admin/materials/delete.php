<?php

require ROOT . 'app/services/file-upload.php';

$materialId = requireNumericId($segments[2] ?? null);
$material = getMaterialById($materialId);
requireFound($material);

if (!isPost()) {
    abort404();
}

if (($material['type'] ?? '') === 'presentation') {
    setFlash('admin', 'Презентации удаляются в разделе ИИ-презентаций.');
    redirectTo('admin/materials');
}

foreach (getMaterialFilesByMaterialId($materialId) as $file) {
    deleteMaterialUploadedFile($materialId, (string)$file['stored_name']);
    deleteMaterialFile((int)$file['id']);
}

$directoryDeleted = deleteMaterialUploadedDirectory($materialId);
deleteMaterial($materialId);

if ($directoryDeleted) {
    setFlash('admin', 'Материал удалён.');
} else {
    setFlash('admin', 'Материал удалён, но папку с файлами не удалось удалить полностью. Проверьте uploads/materials/' . $materialId . '.');
}

redirectTo('admin/materials');
