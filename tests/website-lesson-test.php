<?php

define('ROOT', dirname(__DIR__) . '/website/');
define('OPENAI_API_KEY', '');
define('OPENAI_MODEL', 'gpt-4.1-mini');
require ROOT . 'app/services/ai-test-lesson-context.php';
require ROOT . 'app/services/openai-teacher-assistant.php';
require ROOT . 'app/services/ai-test-generator.php';
require ROOT . 'app/validators/ai-teaching-material.php';

function lessonCheck(bool $ok, string $message): void
{
    if (!$ok) throw new RuntimeException($message);
}
function lessonReject(callable $call, string $expected): void
{
    try { $call(); } catch (RuntimeException $error) {
        lessonCheck(str_contains($error->getMessage(), $expected), $error->getMessage());
        return;
    }
    throw new RuntimeException('Expected rejection: ' . $expected);
}
$body = 'Our school has a computer room and a swimming pool. We study English on Monday. We play games in the farmyard. Vocabulary: school facilities, computer room, swimming pool. Grammar: there is / there are.';
$source = ['id' => 7, 'title' => 'Example textbook, grade 5', 'status' => 'active', 'archived_at' => null,
    'extracted_text' => "Unit 1 — Family\n" . str_repeat('Unrelated family text. ', 400)
        . "\nUnit 3 — School life\n" . $body . "\nUnit 30 — Space\nUnrelated space travel."];
foreach (['3', 'Unit 3', 'School life', 'unit 3 — school life'] as $query) {
    $context = buildAiTestLessonContext($source, $query);
    lessonCheck(str_contains($context['text'], $body), 'Lesson lost: ' . $query);
    lessonCheck(!str_contains($context['text'], 'Unrelated'), 'Other lesson leaked');
}
lessonCheck(buildAiTestLessonContext(null, '') === [], 'General mode broken');
lessonReject(fn() => buildAiTestLessonContext(null, '3'), 'выберите учебник');
lessonReject(fn() => buildAiTestLessonContext($source, ''), 'Укажите номер');
lessonReject(fn() => buildAiTestLessonContext($source, '5'), 'не найден');
lessonReject(fn() => buildAiTestLessonContext(array_replace($source, ['extracted_text' => '']), '3'), 'нет текста');
lessonReject(fn() => buildAiTestLessonContext(array_replace($source, ['status' => 'draft']), '3'), 'недоступен');
lessonReject(fn() => buildAiTestLessonContext(array_replace($source, ['archived_at' => 'today']), '3'), 'недоступен');
lessonReject(fn() => buildAiTestLessonContext(array_replace($source, ['extracted_text' => "Unit 3\nPage 30\n" . $source['extracted_text']]), '3'), 'несколько раз');
lessonReject(fn() => buildAiTestLessonContext(array_replace($source, ['extracted_text' => "Unit 3\nUnit 4\n"]), '3'), 'заголовок');
lessonReject(fn() => buildAiTestLessonContext(array_replace($source, ['extracted_text' => "Unit 3\n" . str_repeat($body, 40)]), '3'), 'слишком большой');
lessonCheck(!aiTestLessonHeadingMatches('Unit 30 — Space', '3'), '3 matched 30');
lessonCheck(!aiTestLessonHeadingMatches('Unit 3.1 — Space', '3'), '3 matched 3.1');
lessonCheck(aiTestLessonHeadingMatches('Lesson 3. School life', '3'), 'Number with punctuation');
lessonCheck(aiTestLessonHeadingMatches('Урок 3 — Школа', 'Школа'), 'Russian title');
$single = array_replace($source, ['title' => 'Textbook — Unit 3', 'extracted_text' => $body]);
lessonCheck(str_contains(buildAiTestLessonContext($single, 'Unit 3')['text'], $body), 'Single lesson source');
$material = ['class_title' => '5', 'topic' => 'School life', 'test_template' => 'reading_true_false', 'instructions' => '', 'source_notes' => 'Short sentences'];
$context = buildAiTestLessonContext($source, 'Unit 3');
$prompt = buildAiInteractiveTestPrompt($material, $context);
lessonCheck(str_contains($prompt, $body) && !str_contains($prompt, 'Unrelated'), 'Prompt not grounded');
lessonCheck(str_contains(buildAiInteractiveTestSystemInstruction(), 'не инструкции'), 'Source instruction isolation missing');
lessonCheck(isset(validateAiTeachingMaterialData(['lesson_reference' => '3'])['lesson_reference']), 'Missing source validation');
echo "PASS: lesson number/title/Russian, isolated section, late chapter, source title, no source, missing/ambiguous/empty/long/inactive, prompt grounding\n";

if (in_array('--live', $argv, true)) {
    putenv('YANDEX_FOLDER_ID=b1gun8kk36mc31tlbgpt');
    foreach (file('/Users/tolmax/Library/Application Support/EnglishPresentationGenerator/.env', FILE_IGNORE_NEW_LINES) as $line) {
        if (preg_match('/^(YANDEX_API_KEY|YANDEX_FOLDER_ID)=(.*)$/', $line, $match)) {
            putenv($match[1] . '=' . trim($match[2], "\"' "));
        }
    }
    $draft = generateAiInteractiveTestDraft($material, $context, 'yandex');
    lessonCheck(count($draft['questions']) === 10, 'Live question count');
    lessonCheck(str_contains(strtolower($draft['description']), 'school'), 'Reading not on school topic');
    echo json_encode(['description' => $draft['description'], 'questions' => $draft['questions']], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
}
