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
$cards = array_map(static fn($word) => ['source_word' => $word, 'english_word' => $word, 'transcription' => '[test]', 'hint' => 'A simple English definition.', 'image_prompt' => 'A bright educational illustration.', 'example_sentence' => 'We learn about ' . $word . ' today.', 'quiz_sentence' => 'We learn about ' . $word . ' today.'], $words);
$presentation = ['title' => 'Yandex integration check', 'class_id' => 1, 'class_title' => '5', 'source_words' => json_encode($words)];
checkWebsite(validateAiWordPresentationData($presentation + ['source_words_text' => implode("\n", $words)]) === [], 'Phrases rejected');
checkWebsite(array_keys(getAiTextProviderOptions()) === ['openai', 'yandex'], 'Wrong providers');
$parsed = generatePresentationCardsWithOpenAi('fixture', 'test', '5', $words, static fn() => ['text' => json_encode(['cards' => array_reverse($cards)])]);
checkWebsite(array_column($parsed['cards'], 'english_word') === $words, 'Order changed');
checkWebsite(aiWordPresentationCards(['cards_json' => encodeAiWordPresentationCards($cards)]) === normalizeAiWordPresentationCards($cards), 'Storage lost fields');
$longBox = pptxAdaptiveTextBox($words[5], 5200, 1400000);
checkWebsite($longBox['width'] > 7315200, 'Long phrase box did not expand');
checkWebsite(count(buildPresentationPlayerSlides($cards)) === 23, 'Wrong browser slide count');
checkWebsite(!str_contains(pptxQuizSentenceWithBlank($cards[8]), 'miss hometown'), 'Blank missing');
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
$xml = $zip->getFromName('ppt/slides/slide24.xml');
checkWebsite(is_string($xml) && str_contains($xml, 'Check your answers'), 'Answers missing');
foreach ($cards as $card) checkWebsite(str_contains(strip_tags($xml), htmlspecialchars($card['quiz_sentence'], ENT_XML1 | ENT_QUOTES, 'UTF-8')), 'Answer sentence differs');
for ($i = 1; $i <= 24; $i++) {
    checkWebsite((new DOMDocument())->loadXML($zip->getFromName('ppt/slides/slide' . $i . '.xml')), 'Invalid slide XML');
}
$zip->close();
echo "PASS: providers, phrases, storage, adaptive layout, quiz, answers, 24-slide PPTX\n";
