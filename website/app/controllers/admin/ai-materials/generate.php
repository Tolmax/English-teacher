<?php

require ROOT . 'app/services/openai-teacher-assistant.php';
require ROOT . 'app/services/ai-knowledge-context.php';

$materialId = requireNumericId($segments[2] ?? null);
$material = getAiTeachingMaterialById($materialId);
requireFound($material);

if (!isPost()) {
    redirectTo('admin/ai-materials/' . $materialId . '/edit');
}

try {
    $knowledgeContext = buildAiKnowledgeContextForMaterial($materialId);
    $provider = getAiTextProvider($_POST['ai_provider'] ?? null);
    $result = generateAiTeachingMaterial($material, $knowledgeContext, $provider);
    updateAiTeachingMaterialGenerationSuccess($materialId, $result);
    setFlash('admin', 'ИИ-материал сгенерирован. Проверьте текст и при необходимости отредактируйте.');
} catch (Throwable $exception) {
    updateAiTeachingMaterialGenerationError($materialId, 'Не удалось сгенерировать материал: ' . $exception->getMessage());
    setFlash('admin_error', 'Не удалось сгенерировать материал. Подробности сохранены в карточке.');
}

redirectTo('admin/ai-materials/' . $materialId . '/edit');
