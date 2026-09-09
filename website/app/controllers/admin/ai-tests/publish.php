<?php

if (!isPost()) {
    abort404();
}

$testId = requireNumericId($segments[2] ?? null);
$test = getAiInteractiveTestById($testId);
requireFound($test);

$questions = getAiInteractiveTestQuestions($testId);
if (empty($questions)) {
    setFlash('admin_error', 'Перед публикацией добавьте хотя бы один вопрос.');
    redirectTo('admin/ai-tests/' . $testId . '/edit');
}

publishAiInteractiveTest($testId);
setFlash('admin', 'Тест опубликован на странице класса.');
redirectTo('admin/ai-tests/' . $testId . '/edit');
