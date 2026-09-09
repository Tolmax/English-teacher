<?php

require ROOT . 'app/validators/ai-teaching-material.php';

$materialId = requireNumericId($segments[2] ?? null);
$material = getAiTeachingMaterialById($materialId);
requireFound($material);

$classes = getAllLearningClasses(true);
$sourceKnowledgeSources = getAiKnowledgeSourcesByMaterialId($materialId);
$activeSourceKnowledgeSourceIds = getActiveAiKnowledgeSourceIdsByMaterialId($materialId);
$errors = [];
$old = [
    'title' => 'Копия — ' . $material['title'],
    'class_title' => $material['class_title'],
    'topic' => $material['topic'],
    'school_year' => $material['school_year'] ?? '',
    'period' => $material['period'] ?? '',
    'tags' => $material['tags'] ?? '',
    'knowledge_source_ids' => $activeSourceKnowledgeSourceIds,
];

if (isPost()) {
    $old = aiTeachingMaterialDuplicateFormData($material);
    $errors = validateAiTeachingMaterialData($old);

    if (empty($errors)) {
        $newMaterialId = duplicateAiTeachingMaterial($material, $old);
        copyAiMaterialFiles((int)$material['id'], $newMaterialId);
        replaceAiMaterialKnowledgeSources($newMaterialId, $activeSourceKnowledgeSourceIds);

        setFlash('admin', 'Копия ИИ-материала создана. Проверьте её перед публикацией.');
        redirectTo('admin/ai-materials/' . $newMaterialId . '/edit');
    }
}

renderTemplate('pages/admin/ai-materials/duplicate.tpl', [
    'material' => $material,
    'classes' => $classes,
    'sourceKnowledgeSources' => $sourceKnowledgeSources,
    'activeSourceKnowledgeSourceIds' => $activeSourceKnowledgeSourceIds,
    'errors' => $errors,
    'old' => $old,
    'flash' => getFlash('admin'),
    'errorFlash' => getFlash('admin_error'),
]);
