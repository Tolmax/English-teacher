<?php

require ROOT . 'app/validators/ai-teaching-material.php';
require ROOT . 'app/services/openai-teacher-assistant.php';

$classes = getAllLearningClasses(true);
$knowledgeSources = getActiveAiKnowledgeSources();
$errors = [];
$old = [
    'title' => '',
    'subject' => 'Английский язык',
    'class_title' => '',
    'topic' => '',
    'material_type' => 'worksheet',
    'difficulty' => '',
    'estimated_duration' => '',
    'language' => 'ru',
    'instructions' => '',
    'source_notes' => '',
    'edited_content' => '',
    'status' => 'draft',
    'school_year' => '',
    'period' => '',
    'tags' => '',
    'source_material_id' => 0,
    'knowledge_source_ids' => [],
    'created_by' => (int)($_SESSION['admin_user_id'] ?? 0),
];

if (isPost()) {
    $old = aiTeachingMaterialFormData();
    $old['knowledge_source_ids'] = filterActiveAiKnowledgeSourceIds($old['knowledge_source_ids'] ?? []);
    $errors = validateAiTeachingMaterialData($old);

    if (empty($errors)) {
        $materialId = createAiTeachingMaterial($old);
        replaceAiMaterialKnowledgeSources($materialId, $old['knowledge_source_ids']);
        setFlash('admin', 'ИИ-материал сохранён как черновик.');
        redirectTo('admin/ai-materials');
    }
}

renderTemplate('pages/admin/ai-materials/form.tpl', [
    'mode' => 'create',
    'aiGenerationMode' => getAiGenerationModeStatus(),
    'classes' => $classes,
    'knowledgeSources' => $knowledgeSources,
    'selectedKnowledgeSourceIds' => $old['knowledge_source_ids'],
    'errors' => $errors,
    'old' => $old,
]);
