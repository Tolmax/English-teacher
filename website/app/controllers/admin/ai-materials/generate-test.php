<?php

require ROOT . 'app/validators/ai-teaching-material.php';
require ROOT . 'app/services/openai-teacher-assistant.php';
require ROOT . 'app/services/ai-test-generator.php';
require ROOT . 'app/services/ai-test-lesson-context.php';

if (!isPost()) {
    redirectTo('admin/ai-materials');
}

$generationMode = trim((string)($_POST['generation_mode'] ?? 'ai'));
requirePresentationCsrf();
$aiProvider = getAiInteractiveTestProvider($_POST['ai_provider'] ?? null);
$topic = trim((string)($_POST['topic'] ?? ''));
$classTitle = trim((string)($_POST['class_title'] ?? ''));
$sourceNotes = trim((string)($_POST['source_notes'] ?? ''));
$testTemplate = normalizeAiTestTemplateKey($_POST['test_template'] ?? null);
$lessonReference = trim((string)($_POST['lesson_reference'] ?? ''));
$knowledgeSourceId = (int)($_POST['knowledge_source_id'] ?? 0);
$_SESSION['ai_test_form'] = [
    'class_title' => $classTitle, 'topic' => $topic, 'source_notes' => $sourceNotes,
    'lesson_reference' => $lessonReference, 'knowledge_source_id' => $knowledgeSourceId,
    'test_template' => $testTemplate, 'ai_provider' => $aiProvider, 'generation_mode' => $generationMode,
];

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
    'knowledge_source_ids' => $knowledgeSourceId > 0 ? [$knowledgeSourceId] : [],
    'lesson_reference' => $lessonReference,
    'created_by' => (int)($_SESSION['admin_user_id'] ?? 0),
];

$errors = validateAiTeachingMaterialData($data);

if (!empty($errors)) {
    setFlash('admin_error', implode(' ', $errors));
    redirectTo('admin/ai-materials');
}

try {
    $source = $knowledgeSourceId > 0 ? getAiKnowledgeSourceById($knowledgeSourceId) : null;
    if ($source === false) {
        throw new RuntimeException('Выбранный учебник не найден. Выберите другой источник.');
    }
    $knowledgeContext = $generationMode === 'manual' ? [] : buildAiTestLessonContext($source, $lessonReference);
    if ($source) {
        $data['source_notes'] = 'Учебник: ' . $source['title'] . "\nУрок: " . $lessonReference . "\n" . $sourceNotes;
    }
    if ($generationMode !== 'manual' && !hasConfiguredAiInteractiveTestProvider($aiProvider)) {
        setFlash(
            'admin_error',
            'AI-провайдер для тестов не настроен. Тест не создан. Выберите ручной шаблон или настройте ключ OpenAI/Yandex.'
        );
        redirectTo('admin/ai-materials');
    }

    $draft = $generationMode === 'manual'
        ? generateFallbackAiInteractiveTestDraft($data)
        : generateAiInteractiveTestDraft($data, $knowledgeContext, $aiProvider);
} catch (Throwable $exception) {
    $providerLabel = $aiProvider === 'yandex' ? 'Yandex' : 'OpenAI';
    setFlash('admin_error', $providerLabel . ' не смог сгенерировать тест: ' . $exception->getMessage() . ' Тест не создан.');
    redirectTo('admin/ai-materials');
}

$materialId = createAiTeachingMaterial($data);
replaceAiMaterialKnowledgeSources($materialId, $data['knowledge_source_ids']);
unset($_SESSION['ai_test_form']);
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
