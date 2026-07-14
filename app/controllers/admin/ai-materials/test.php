<?php

$materialId = requireNumericId($segments[2] ?? null);
$material = getAiTeachingMaterialById($materialId);
requireFound($material);

$test = getAiInteractiveTestByMaterialId($materialId);

if (empty($test)) {
    require ROOT . 'app/services/ai-test-generator.php';

    $draft = generateFallbackAiInteractiveTestDraft($material);
    $testId = createAiInteractiveTestFromMaterial($material);
    updateAiInteractiveTest($testId, [
        'title' => 'Тест',
        'class_title' => (string)$material['class_title'],
        'description' => $draft['description'],
    ]);
    replaceAiInteractiveTestQuestions($testId, $draft['questions']);
    setFlash('admin', 'Черновик интерактивного теста 10×2 создан. Замените вопросы и ответы на нужные.');
} else {
    $testId = (int)$test['id'];
}

redirectTo('admin/ai-tests/' . $testId . '/edit');
