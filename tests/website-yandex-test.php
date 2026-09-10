<?php

define('ROOT', dirname(__DIR__) . '/website/');
define('OPENAI_API_KEY', '');
define('OPENAI_MODEL', 'gpt-4.1-mini');
define('OPENAI_IMAGE_MODEL', 'gpt-image-2');
define('AI_TEXT_PROVIDER', 'yandex');
define('AI_IMAGE_PROVIDER', 'yandex');
require ROOT . 'app/models/ai-word-presentation.php';
require ROOT . 'app/validators/ai-word-presentation.php';
require ROOT . 'app/services/ai-word-presentation-generator.php';
require ROOT . 'app/services/ai-test-generator.php';
require ROOT . 'app/services/presentation-slides.php';

function checkWebsite(bool $ok, string $message): void
{
    if (!$ok) throw new RuntimeException($message);
}

$words = ['bring a cup of tea', 'farmyard', 'stay in bed late', 'excited', 'travel by tube', 'school facilities (a computer room and a swimming pool)', 'feel tired', 'homesick', 'miss hometown', 'lovely family'];
$cards = array_map(static fn($word) => ['source_word' => $word, 'english_word' => $word, 'transcription' => '[test]', 'hint' => 'A simple English definition.', 'translation_ru' => 'Учебный перевод', 'image_prompt' => 'A bright educational illustration.', 'example_sentence' => 'We learn about ' . $word . ' today.', 'quiz_sentence' => 'We learn about ' . $word . ' today.'], $words);
$presentation = ['title' => 'Yandex integration check', 'class_id' => 1, 'class_title' => '5', 'source_words' => json_encode($words)];
checkWebsite(validateAiWordPresentationData($presentation + ['source_words_text' => implode("\n", $words)]) === [], 'Phrases rejected');
checkWebsite(array_keys(getAiTextProviderOptions()) === ['openai', 'yandex'], 'Wrong providers');
$parsed = generatePresentationCardsWithOpenAi('fixture', 'test', '5', $words, static fn() => ['text' => json_encode(['cards' => array_reverse($cards)])]);
checkWebsite(array_column($parsed['cards'], 'english_word') === $words, 'Order changed');
checkWebsite(aiWordPresentationCards(['cards_json' => encodeAiWordPresentationCards($cards)]) === normalizeAiWordPresentationCards($cards), 'Storage lost fields');
$longBox = pptxAdaptiveTextBox($words[5], 5200, 1400000);
checkWebsite($longBox['width'] > 7315200, 'Long phrase box did not expand');
$browserSlides = buildPresentationPlayerSlides($cards);
checkWebsite(count($browserSlides) === 26, 'Wrong browser slide count');
foreach ($cards as $index => $_card) {
    checkWebsite(
        ($browserSlides[$index * 2]['card_number'] ?? 0) === $index + 1
        && ($browserSlides[$index * 2 + 1]['card_number'] ?? 0) === $index + 1,
        'Browser card pair has the wrong number'
    );
}
checkWebsite(!str_contains(pptxQuizSentenceWithBlank($cards[8]), 'miss hometown'), 'Blank missing');
$driveCard = $cards[0];
$driveCard['source_word'] = 'driving a car';
$driveCard['english_word'] = 'driving a car';
$driveCard['quiz_sentence'] = 'My father drives a car every day.';
$driveCard['example_sentence'] = 'My father drives a car every day.';
$driveCard['image_prompt'] = '';
$driveCard['hint'] = '';
$repairedDrive = parseCardsJson(json_encode(['cards' => [$driveCard]]), ['drive a car']);
checkWebsite($repairedDrive['drive a car']['english_word'] === 'drive a car', 'Single-item identifier was not restored');
checkWebsite(cardLearningItemOccurrenceCount($repairedDrive['drive a car']['quiz_sentence'], 'drive a car') === 1, 'Inflected quiz expression was not repaired');
checkWebsite($repairedDrive['drive a car']['hint'] !== '' && $repairedDrive['drive a car']['image_prompt'] !== '', 'Optional card fields were not repaired');
$duplicateQuizCard = $cards[0];
$duplicateQuizCard['source_word'] = $duplicateQuizCard['english_word'] = 'drive a car';
$duplicateQuizCard['quiz_sentence'] = 'I drive a car because I like to drive a car.';
$repairedDuplicate = parseCardsJson(json_encode(['cards' => [$duplicateQuizCard]]), ['drive a car']);
checkWebsite(cardLearningItemOccurrenceCount($repairedDuplicate['drive a car']['quiz_sentence'], 'drive a car') === 1, 'Repeated quiz answer was not repaired');
if (in_array('--live', $argv, true)) {
    $envPath = '/Users/tolmax/Library/Application Support/EnglishPresentationGenerator/.env';
    putenv('YANDEX_FOLDER_ID=b1gun8kk36mc31tlbgpt');
    foreach (file($envPath, FILE_IGNORE_NEW_LINES) as $line) {
        if (preg_match('/^(YANDEX_API_KEY|YANDEX_FOLDER_ID)=(.*)$/', $line, $match)) putenv($match[1] . '=' . trim($match[2], "\"' "));
    }
    $generated = generateAiWordPresentationCards($presentation, 'yandex');
    $cards = $generated['cards'];
    checkWebsite(count($cards) === 10, 'Live cards incomplete');
    echo "LIVE: 10 Yandex phrase cards validated\n";
    $draft = generateAiInteractiveTestDraft(['class_title' => '5', 'topic' => 'School and family', 'test_template' => 'vocabulary', 'source_notes' => '', 'instructions' => 'Simple vocabulary'], [], 'yandex');
    checkWebsite(count($draft['questions']) === 10, 'Live test incomplete');
    echo "LIVE: 10 Yandex test questions validated\n";
    $imagePath = sys_get_temp_dir() . '/english-yandex-site-check.png';
    generateYandexImage(presentationEnvValue('YANDEX_API_KEY'), $cards[1], $imagePath);
    checkWebsite(is_array(getimagesize($imagePath)), 'Live image invalid');
    echo "LIVE: Yandex image validated\n";
}
$out = sys_get_temp_dir() . '/english-website-yandex-test.pptx';
buildWordPresentationPptx($presentation, $cards, $out);
$zip = new ZipArchive();
$zip->open($out);
$xml = $zip->getFromName('ppt/slides/slide26.xml') . $zip->getFromName('ppt/slides/slide27.xml');
checkWebsite(str_contains($xml, 'Check your answers'), 'Answers missing');
foreach ($cards as $card) checkWebsite(str_contains(strip_tags($xml), htmlspecialchars($card['quiz_sentence'], ENT_XML1 | ENT_QUOTES, 'UTF-8')), 'Answer sentence differs');
for ($i = 1; $i <= 27; $i++) {
    checkWebsite((new DOMDocument())->loadXML($zip->getFromName('ppt/slides/slide' . $i . '.xml')), 'Invalid slide XML');
}
$titleSlide = $zip->getFromName('ppt/slides/slide3.xml');
checkWebsite(str_contains($titleSlide, 'sz="10400"'), 'Presentation word font is not exactly doubled');
$quizSlide = $zip->getFromName('ppt/slides/slide22.xml');
checkWebsite(str_contains($quizSlide, 'sz="5200"') && str_contains($quizSlide, 'sz="2900"'), 'Quiz fonts are not exactly doubled');
$zip->close();
echo "PASS: providers, phrases, storage, adaptive layout, quiz, answers, 27-slide PPTX\n";
