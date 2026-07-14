<?php

require ROOT . 'app/validators/material.php';
require ROOT . 'app/validators/material-file.php';
require ROOT . 'app/services/file-upload.php';

$classes = getAllLearningClasses(true);
$errors = [];
$fileErrors = [];
$old = [
    'class_id' => (int)($_GET['class_id'] ?? 0),
    'type' => 'homework',
    'title' => 'Домашка',
    'description' => '',
    'content' => '',
    'status' => 'deadline',
    'deadline_at' => '',
    'is_published' => 1,
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
        $materialId = createMaterial($old);

        foreach ($uploadedFiles as $file) {
            $savedFile = saveMaterialUploadedFile($file, $materialId);

            if ($savedFile !== false) {
                createMaterialFile($savedFile);
            }
        }

        setFlash('admin', 'Материал добавлен.');
        redirectTo('admin/materials');
    }
}

renderTemplate('pages/admin/materials/form.tpl', [
    'mode' => 'create',
    'classes' => $classes,
    'errors' => $errors,
    'fileErrors' => $fileErrors,
    'files' => [],
    'old' => $old,
]);
