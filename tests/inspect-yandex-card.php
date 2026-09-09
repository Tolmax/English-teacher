<?php
require __DIR__ . '/website-yandex-test.php';
putenv('YANDEX_FOLDER_ID=b1gun8kk36mc31tlbgpt');
foreach (file('/Users/tolmax/Library/Application Support/EnglishPresentationGenerator/.env', FILE_IGNORE_NEW_LINES) as $line) {
    if (preg_match('/^(YANDEX_API_KEY|YANDEX_FOLDER_ID)=(.*)$/', $line, $m)) putenv($m[1] . '=' . trim($m[2], "\"' "));
}
$response = requestYandexCardBatch(presentationEnvValue('YANDEX_API_KEY'), 'Vocabulary', '5', ['a barge']);
echo $response['text'] . PHP_EOL;
