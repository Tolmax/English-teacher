<?php

$testId = requireNumericId($segments[2] ?? null);
$test = getAiInteractiveTestById($testId);
requireFound($test);

if (!isPost()) {
    redirectTo('admin/ai-tests');
}

deleteAiInteractiveTest($testId);
setFlash('admin', 'ИИ-тест удалён.');

redirectTo('admin/ai-tests');
