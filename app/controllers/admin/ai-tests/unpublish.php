<?php

if (!isPost()) {
    abort404();
}

$testId = requireNumericId($segments[2] ?? null);
$test = getAiInteractiveTestById($testId);
requireFound($test);

unpublishAiInteractiveTest($testId);
setFlash('admin', 'Тест снят с публикации.');
redirectTo('admin/ai-tests/' . $testId . '/edit');
