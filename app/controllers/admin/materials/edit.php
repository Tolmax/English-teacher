<?php

require ROOT . 'app/validators/material.php';
require ROOT . 'app/validators/material-file.php';
require ROOT . 'app/services/file-upload.php';

$materialId = requireNumericId($segments[2] ?? null);
$material = getMaterialById($materialId);
requireFound($material);

if (($material['type'] ?? '') === 'presentation') {
    setFlash('admin', 'Презентации редактируются в разделе ИИ-презентаций.');
    redirectTo('admin/materials');
}

$classes = getAllLearningClasses(true);
$errors = [];
$fileErrors = [];
$old = [
    'class_id' => (int)$material['class_id'],
    'type' => $material['type'],
    'title' => $material['title'],
    'description' => $material['description'] ?? '',
    'content' => $material['content'] ?? '',
    'status' => $material['status'],
    'deadline_at' => $material['deadline_at'] ?? '',
    'is_published' => (int)$material['is_published'],
];

if (isPost()) {
    $uploadedFiles = normalizeUploadedFiles($_FILES['files'] ?? []);
    $type = trim((string)($_POST['type'] ?? 'homework'));
    $old = [
        'class_id' => (int)($_POST['class_id'] ?? 0),
        'type' => $type,
        'title' => materialDefaultTitle($type, trim((string)($_POST['deadline_at'] ?? ''))),
        'description' => '',
        'content' => trim((string)($_POST['content'] ?? '')),
        'status' => materialDefaultStatus($type),
        'deadline_at' => trim((string)($_POST['deadline_at'] ?? '')),
        'is_published' => isset($_POST['is_published']) ? 1 : 0,
    ];

    $errors = validateMaterialData($old);
    $fileErrors = validateMaterialUploadFiles($uploadedFiles);

    if (empty($errors) && empty($fileErrors)) {
        updateMaterial($materialId, $old);

        foreach ($uploadedFiles as $file) {
            $savedFile = saveMaterialUploadedFile($file, $materialId);

            if ($savedFile !== false) {
                createMaterialFile($savedFile);
            }
        }

        setFlash('admin', 'Материал обновлён.');
        redirectTo('admin/materials');
    }
}

renderTemplate('pages/admin/materials/form.tpl', [
    'mode' => 'edit',
    'material' => $material,
    'classes' => $classes,
    'errors' => $errors,
    'fileErrors' => $fileErrors,
    'files' => getMaterialFilesByMaterialId($materialId),
    'old' => $old,
]);
