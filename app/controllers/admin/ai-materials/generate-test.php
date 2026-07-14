<?php

require ROOT . 'app/validators/ai-teaching-material.php';
require ROOT . 'app/services/openai-teacher-assistant.php';
require ROOT . 'app/services/ai-test-generator.php';

if (!isPost()) {
    redirectTo('admin/ai-materials');
}

$generationMode = trim((string)($_POST['generation_mode'] ?? 'ai'));
$aiProvider = getAiInteractiveTestProvider($_POST['ai_provider'] ?? null);
$topic = trim((string)($_POST['topic'] ?? ''));
$classTitle = trim((string)($_POST['class_title'] ?? ''));
$sourceNotes = trim((string)($_POST['source_notes'] ?? ''));
$testTemplate = normalizeAiTestTemplateKey($_POST['test_template'] ?? null);

$data = [
    'title' => 'Тест',
    'subject' => 'Английский язык',
    'class_title' => $classTitle,
    'topic' => $topic,
    'material_type' => 'quiz',
    'test_template' => $testTemplate,
    'difficulty' => '',
    'estimated_duration' => '10 минут',
    'language' => 'ru',
    'instructions' => 'Создай интерактивный тест: ровно 10 вопросов, два варианта ответа, один правильный вариант.',
    'source_notes' => $sourceNotes,
    'edited_content' => '',
    'status' => 'draft',
    'school_year' => '',
    'period' => '',
    'tags' => $topic,
    'source_material_id' => 0,
    'knowledge_source_ids' => [],
    'created_by' => (int)($_SESSION['admin_user_id'] ?? 0),
];

$errors = validateAiTeachingMaterialData($data);

if (!empty($errors)) {
    setFlash('admin_error', implode(' ', $errors));
    redirectTo('admin/ai-materials');
}

try {
    if ($generationMode !== 'manual' && !hasConfiguredAiInteractiveTestProvider($aiProvider)) {
        setFlash(
            'admin_error',
            'AI-провайдер для тестов не настроен. Тест не создан. Выберите ручной шаблон или настройте ключ OpenAI/GigaChat.'
        );
        redirectTo('admin/ai-materials');
    }

    $draft = $generationMode === 'manual'
        ? generateFallbackAiInteractiveTestDraft($data)
        : generateAiInteractiveTestDraft($data, [], $aiProvider);
} catch (Throwable $exception) {
    $providerLabel = $aiProvider === 'gigachat' ? 'GigaChat' : 'OpenAI';
    setFlash('admin_error', $providerLabel . ' не смог сгенерировать тест: ' . $exception->getMessage() . ' Тест не создан.');
    redirectTo('admin/ai-materials');
}

$materialId = createAiTeachingMaterial($data);
$material = getAiTeachingMaterialById($materialId);
requireFound($material);

$content = aiInteractiveTestDraftToText($draft);
updateAiTeachingMaterialGenerationSuccess($materialId, [
    'content' => $content,
    'model' => $draft['model'],
    'response_id' => $draft['response_id'],
]);

$material = getAiTeachingMaterialById($materialId);
requireFound($material);

$testId = createAiInteractiveTestFromMaterial($material);
updateAiInteractiveTest($testId, [
    'title' => 'Тест',
    'description' => $draft['description'],
    'class_title' => $classTitle,
]);
replaceAiInteractiveTestQuestions($testId, $draft['questions']);

setFlash('admin', 'Тест создан. Проверьте вопросы и опубликуйте, когда он готов для учеников.');

redirectTo('admin/ai-tests/' . $testId . '/edit');
