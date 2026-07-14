<?php

$testId = requireNumericId($segments[2] ?? null);
$test = getAiInteractiveTestById($testId);
requireFound($test);

$testTemplate = getAiTestTemplate($test['template_key'] ?? null);
$questions = getAiInteractiveTestQuestions($testId);
$errors = [];
$old = [
    'title' => $test['title'],
    'class_title' => $test['class_title'],
    'description' => $test['description'],
    'questions' => aiInteractiveTestQuestionsForForm($questions),
];

if (isPost()) {
    $old = aiInteractiveTestFormData($_POST);
    $normalizedQuestions = normalizeAiInteractiveTestQuestions($old['questions']);
    $errors = validateAiInteractiveTestData($old);

    if (empty($errors)) {
        updateAiInteractiveTest($testId, [
            'title' => $old['title'],
            'class_title' => $old['class_title'],
            'description' => $old['description'],
        ]);
        replaceAiInteractiveTestQuestions($testId, $normalizedQuestions);
        setFlash('admin', 'Интерактивный тест сохранён.');
        redirectTo('admin/ai-tests/' . $testId . '/edit');
    }
}

renderTemplate('pages/admin/ai-tests/form.tpl', [
    'test' => $test,
    'testTemplate' => $testTemplate,
    'classes' => getAllLearningClasses(true),
    'old' => $old,
    'errors' => $errors,
    'flash' => getFlash('admin'),
]);

function aiInteractiveTestQuestionsForForm(array $questions): array
{
    $formQuestions = [];

    foreach ($questions as $question) {
        $options = [];
        $correctOption = 0;

        foreach (array_values($question['options'] ?? []) as $index => $option) {
            $options[] = (string)$option['option_text'];
            if ((int)$option['is_correct'] === 1) {
                $correctOption = $index;
            }
        }

        $formQuestions[] = [
            'question_text' => (string)$question['question_text'],
            'options' => $options,
            'correct_option' => $correctOption,
        ];
    }

    return $formQuestions;
}
