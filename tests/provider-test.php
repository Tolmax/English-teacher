<?php
declare(strict_types=1);
putenv('PRESENTATION_DATA_DIR=' . sys_get_temp_dir() . '/english-teacher-provider-test');
require __DIR__ . '/../windows-package-build/package/local-presentation-generator/src/bootstrap.php';
require APP_ROOT . 'src/openai.php';
require APP_ROOT . 'src/yandex.php';
function check(bool $value, string $label): void {
    if (!$value) throw new RuntimeException($label);
    echo "PASS: $label\n";
}
$words = ['bring a cup of tea', 'farmyard', 'stay in bed late', 'excited', 'travel by tube', 'school facilities (a computer room and a swimming pool)', 'feel tired', 'homesick', 'miss hometown', 'lovely family'];
$calls = [];
$request = static function ($key, $title, $class, $items) use (&$calls): array {
    $calls[] = $items;
    $cards = [];
    foreach (array_reverse($items) as $item) {
        if (count($items) > 1 && $item === 'homesick') continue;
        $cards[] = ['source_word' => $item, 'english_word' => $item, 'transcription' => '[test]', 'hint' => 'A simple English definition.', 'image_prompt' => 'An educational scene.', 'example_sentence' => 'We say ' . $item . ' today.'];
    }
    return ['text' => json_encode(['cards' => $cards]), 'model' => 'fixture'];
};
$result = generatePresentationCardsWithOpenAi('test', 'Test', '', $words, $request);
check(array_column($result['cards'], 'english_word') === $words, 'All ten phrases preserved in input order despite shuffled response');
check(count($calls) === 2 && $calls[1] === ['homesick'], 'Only missing phrase retried through the selected provider');
check(yandexFolder() === 'b1gun8kk36mc31tlbgpt', 'Selected Yandex folder');
try { yandexRequest('text', "bad\nkey", []); throw new LogicException('Accepted header injection'); }
catch (RuntimeException $e) { check(str_contains($e->getMessage(), 'API-ключ'), 'Invalid key rejected before network request'); }
echo "No paid API calls made.\n";
require APP_ROOT . 'src/pptx-presentation-builder.php';
$phraseCard = ['source_word' => 'miss hometown', 'english_word' => 'miss hometown',
    'transcription' => '[test]', 'hint' => 'To feel sad because you are away from your home town.',
    'image_prompt' => 'A suitcase beside a framed town landscape.',
    'example_sentence' => 'I miss my hometown when I travel.',
    'quiz_sentence' => 'I miss hometown traditions when I travel.'];
$parsed = parseCardsJson(json_encode(['cards' => [$phraseCard]]), ['miss hometown']);
check(count($parsed) === 1, 'Natural example with a possessive pronoun accepted');
check(pptxQuizSentenceWithBlank($parsed['miss hometown']) === 'I ... traditions when I travel.', 'Quiz uses the literal word-bank expression with one blank');
$phraseCard['quiz_sentence'] = 'I miss my hometown when I travel.';
check(parseCardsJson(json_encode(['cards' => [$phraseCard]]), ['miss hometown'], true) === [], 'Non-matching quiz answer is not silently accepted');
$pptxPath = tempnam(sys_get_temp_dir(), 'answer-slide-');
buildWordPresentationPptx(['title' => 'Answer slide test'], $result['cards'], $pptxPath);
$zip = new ZipArchive();
$zip->open($pptxPath);
// 1 cover + 20 teaching slides + 2 quiz slides + 1 answer slide.
$answers = $zip->getFromName('ppt/slides/slide24.xml');
check(is_string($answers) && str_contains($answers, 'Check your answers'), 'Ten-item presentation ends with answer slide after both quiz slides');
$doc = new DOMDocument();
check($doc->loadXML($answers), 'Answer slide XML is valid');
$xp = new DOMXPath($doc);
$xp->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
$xp->registerNamespace('p', 'http://schemas.openxmlformats.org/presentationml/2006/main');
foreach ($result['cards'] as $index => $card) {
    $paragraph = $xp->query('//p:sp[p:nvSpPr/p:cNvPr[@name="Answer ' . ($index + 1) . '"]]//a:p')->item(0);
    check($paragraph && $paragraph->textContent === ($index + 1) . '. ' . $card['example_sentence'], 'Answer ' . ($index + 1) . ' preserves the complete quiz sentence');
}
check($xp->query('//a:rPr[@b="1"]/a:solidFill/a:srgbClr[@val="087F5B"]')->length === 10, 'All ten answers highlighted');
$zip->close();
unlink($pptxPath);
