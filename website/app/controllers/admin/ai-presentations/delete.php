<?php

require ROOT . 'app/models/material.php';
require ROOT . 'app/models/material-file.php';
require ROOT . 'app/services/ai-word-presentation-images.php';

$presentationId = requireNumericId($segments[2] ?? null);
$presentation = getAiWordPresentationById($presentationId);
requireFound($presentation);

if (!isPost()) {
    redirectTo('admin/ai-presentations');
}

$materialId = (int)($presentation['material_id'] ?? 0);
$directoryDeleted = true;

if ($materialId > 0) {
    foreach (getMaterialFilesByMaterialId($materialId) as $file) {
        $path = ROOT . 'uploads/materials/' . (int)$file['material_id'] . '/' . (string)$file['stored_name'];
        if (is_file($path)) {
            unlink($path);
        }
        deleteMaterialFile((int)$file['id']);
    }

    $directoryDeleted = deleteAiWordPresentationMaterialDirectory($materialId);
    deleteMaterial($materialId);
}

deleteAiWordPresentation($presentationId);
if ($directoryDeleted) {
    setFlash('admin', 'ИИ-презентация удалена.');
} else {
    setFlash('admin_error', 'ИИ-презентация удалена из базы, но папку uploads/materials/' . $materialId . ' не удалось удалить: её удерживает другой процесс.');
}

redirectTo('admin/ai-presentations');
