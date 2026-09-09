<?php
// Pipe directly to SSH; never print this output in a terminal or log.
$settings = ['YANDEX_FOLDER_ID' => 'b1gun8kk36mc31tlbgpt'];
foreach (file('/Users/tolmax/Library/Application Support/EnglishPresentationGenerator/.env', FILE_IGNORE_NEW_LINES) as $line) {
    if (preg_match('/^(YANDEX_API_KEY|YANDEX_FOLDER_ID)=(.*)$/', $line, $match)) {
        $settings[$match[1]] = trim($match[2], "\"' ");
    }
}
if (empty($settings['YANDEX_API_KEY'])) throw new RuntimeException('Missing Yandex key');
echo json_encode($settings, JSON_THROW_ON_ERROR);
