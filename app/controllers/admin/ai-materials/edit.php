<?php

require ROOT . 'app/validators/ai-teaching-material.php';
require ROOT . 'app/services/openai-teacher-assistant.php';

$materialId = requireNumericId($segments[2] ?? null);
$material = getAiTeachingMaterialById($materialId);
requireFound($material);

$classes = getAllLearningClasses(true);
$knowledgeSources = getActiveAiKnowledgeSources();
$selectedKnowledgeSourceIds = getAiKnowledgeSourceIdsByMaterialId($materialId);
$errors = [];
$old = [
    'title' => $material['title'],
    'subject' => $material['subject'],
    'class_title' => $material['class_title'],
    'topic' => $material['topic'],
    'material_type' => $material['material_type'],
    'difficulty' => '',
    'estimated_duration' => $material['estimated_duration'] ?? '',
    'language' => $material['language'],
    'instructions' => $material['instructions'] ?? '',
    'source_notes' => $material['source_notes'] ?? '',
    'edited_content' => $material['edited_content'] ?? '',
    'status' => $material['status'],
    'school_year' => $material['school_year'] ?? '',
    'period' => $material['period'] ?? '',
    'tags' => $material['tags'] ?? '',
    'source_material_id' => (int)($material['source_material_id'] ?? 0),
    'knowledge_source_ids' => $selectedKnowledgeSourceIds,
    'created_by' => (int)($material['created_by'] ?? 0),
];

if (isPost()) {
    $old = aiTeachingMaterialFormData();
    $old['knowledge_source_ids'] = filterActiveAiKnowledgeSourceIds($old['knowledge_source_ids'] ?? []);
    $selectedKnowledgeSourceIds = $old['knowledge_source_ids'];
    $errors = validateAiTeachingMaterialData($old);

    if (empty($errors)) {
        updateAiTeachingMaterial($materialId, $old);
        replaceAiMaterialKnowledgeSources($materialId, $old['knowledge_source_ids']);
        setFlash('admin', 'ИИ-материал обновлён.');
        redirectTo('admin/ai-materials');
    }
}

renderTemplate('pages/admin/ai-materials/edit.tpl', [
    'mode' => 'edit',
    'aiGenerationMode' => getAiGenerationModeStatus(),
    'material' => $material,
    'classes' => $classes,
    'knowledgeSources' => $knowledgeSources,
    'selectedKnowledgeSourceIds' => $selectedKnowledgeSourceIds,
    'errors' => $errors,
    'old' => $old,
    'flash' => getFlash('admin'),
    'errorFlash' => getFlash('admin_error'),
]);
