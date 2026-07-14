<?php

$testId = requireNumericId($segments[2] ?? null);
$test = getAiInteractiveTestById($testId);
requireFound($test);

renderTemplate('pages/admin/ai-tests/submissions.tpl', [
    'test' => $test,
    'submissions' => getAiTestSubmissionsByTestId($testId),
]);
