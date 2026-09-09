<?php

require ROOT . 'app/validators/ai-knowledge-source.php';
require ROOT . 'app/services/ai-knowledge-upload.php';

$errors = [];
$fileError = null;
$old = [
    'title' => '',
    'category_id' => 0,
    'source_type' => 'text',
    'description' => '',
    'tags' => '',
    'status' => 'active',
    'extracted_text' => '',
    'created_by' => (int)($_SESSION['admin_user_id'] ?? 0),
];

if (isPost()) {
    $old = aiKnowledgeSourceFormData();
    $file = $_FILES['source_file'] ?? [];
    $hasFile = ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    $errors = validateAiKnowledgeSourceData($old, ($old['source_type'] ?? '') === 'file' && !$hasFile);
    $fileError = $hasFile ? validateAiKnowledgeUploadFile($file) : null;

    if (empty($errors) && $fileError === null) {
        $savedFile = [];

        if ($hasFile) {
            $savedFile = saveAiKnowledgeUploadedFile($file);

            if ($savedFile === false) {
                $fileError = 'Не удалось сохранить файл. Попробуйте ещё раз.';
            }
        }

        if ($fileError === null) {
            createAiKnowledgeSource(array_merge($old, $savedFile));
            setFlash('admin', 'Источник базы знаний добавлен.');
            redirectTo('admin/ai-knowledge');
        }
    }
}

renderTemplate('pages/admin/ai-knowledge/form.tpl', [
    'mode' => 'create',
    'source' => null,
    'categories' => getAiKnowledgeCategories(),
    'errors' => $errors,
    'fileError' => $fileError,
    'old' => $old,
]);
