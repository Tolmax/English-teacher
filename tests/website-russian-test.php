<?php
require __DIR__ . '/website-yandex-test.php';
$mixed = in_array('--coins', $argv, true) ? ['монета', 'ассигнация', 'баржа', 'вертолет'] : ['принести чашку чая', 'farmyard', 'школьные помещения (компьютерный класс и бассейн)'];
checkWebsite(validateAiWordPresentationData(['class_id'=>1, 'title'=>'Mixed', 'source_words_text'=>implode("\n", $mixed)]) === [], 'Mixed input rejected');
checkWebsite(translatePresentationLearningWords(['farmyard'], 'yandex') === ['farmyard'], 'English changed');
if (in_array('--russian-live', $argv, true)) {
    putenv('YANDEX_FOLDER_ID=b1gun8kk36mc31tlbgpt');
    foreach (file('/Users/tolmax/Library/Application Support/EnglishPresentationGenerator/.env', FILE_IGNORE_NEW_LINES) as $line) {
        if (preg_match('/^(YANDEX_API_KEY|YANDEX_FOLDER_ID)=(.*)$/', $line, $match)) putenv($match[1] . '=' . trim($match[2], "\"' "));
    }
    $generated = generateAiWordPresentationCards(['title'=>'Mixed vocabulary','class_title'=>'5','source_words'=>json_encode($mixed)], 'yandex');
    checkWebsite(array_column($generated['cards'], 'source_word') === $mixed, 'Original sources lost');
    foreach ($generated['cards'] as $card) {
        checkWebsite(!preg_match('/[А-Яа-яЁё]/u', $card['english_word']), 'Russian remains in target');
        checkWebsite(str_contains(pptxQuizSentenceWithBlank($card), '...'), 'Quiz blank missing');
        echo $card['source_word'] . ' => ' . $card['english_word'] . PHP_EOL;
    }
}
echo "PASS: Russian and mixed input\n";
