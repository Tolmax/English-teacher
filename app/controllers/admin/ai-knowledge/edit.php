<?php

require ROOT . 'app/validators/ai-knowledge-source.php';
require ROOT . 'app/services/ai-knowledge-upload.php';

$sourceId = requireNumericId($segments[2] ?? null);
$source = getAiKnowledgeSourceById($sourceId);
requireFound($source);

$errors = [];
$old = [
    'title' => $source['title'],
    'category_id' => (int)($source['category_id'] ?? 0),
    'source_type' => $source['source_type'],
    'description' => $source['description'] ?? '',
    'tags' => $source['tags'] ?? '',
    'status' => $source['status'],
    'extracted_text' => $source['extracted_text'] ?? '',
    'created_by' => (int)($source['created_by'] ?? 0),
];

if (isPost()) {
    $old = aiKnowledgeSourceFormData();
    $errors = validateAiKnowledgeSourceData($old, false);

    if (empty($errors)) {
        updateAiKnowledgeSource($sourceId, $old);
        setFlash('admin', 'Источник базы знаний обновлён.');
        redirectTo('admin/ai-knowledge');
    }
}

renderTemplate('pages/admin/ai-knowledge/edit.tpl', [
    'mode' => 'edit',
    'source' => $source,
    'categories' => getAiKnowledgeCategories(),
    'errors' => $errors,
    'fileError' => null,
    'old' => $old,
]);
