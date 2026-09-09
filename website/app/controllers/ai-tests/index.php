<?php

require ROOT . 'app/models/ai-interactive-test.php';
require ROOT . 'app/validators/ai-interactive-test.php';

$slug = trim((string)($segments[1] ?? ''));
if ($slug === '') {
    abort404();
}

$test = getPublishedAiInteractiveTestBySlug($slug);
requireFound($test);

$questions = getAiInteractiveTestQuestions((int)$test['id']);
$errors = [];
$old = [
    'student_name' => '',
    'answers' => [],
];

if (isPost()) {
    $answers = [];
    foreach (($_POST['answers'] ?? []) as $questionId => $optionId) {
        $answers[(int)$questionId] = (int)$optionId;
    }

    $old = [
        'student_name' => trim((string)($_POST['student_name'] ?? '')),
        'answers' => $answers,
    ];
    $errors = validateAiTestSubmissionData($old, $questions);

    if (empty($errors)) {
        $result = scoreAiInteractiveTest($questions, $answers);
        $submissionId = createAiTestSubmission(
            (int)$test['id'],
            $old['student_name'],
            (int)$result['score'],
            (int)$result['total'],
            $result['details']
        );
        setFlash('ai_test', 'Результат отправлен учителю: ' . (int)$result['score'] . ' из ' . (int)$result['total'] . '.');
        redirectTo('ai-tests/' . $test['slug'] . '?submitted=' . $submissionId);
    }
}

renderTemplate('pages/ai-tests/show.tpl', [
    'test' => $test,
    'questions' => $questions,
    'errors' => $errors,
    'old' => $old,
    'flash' => getFlash('ai_test'),
    'submitted' => (int)($_GET['submitted'] ?? 0),
]);
