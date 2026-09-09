<?php
require '/home/c/cr77641/My_Projects/public_html/english/config/app.php';
require ROOT . 'app/models/ai-word-presentation.php';
require ROOT . 'app/services/ai-word-presentation-generator.php';
require ROOT . 'app/services/ai-test-generator.php';
require ROOT . 'app/services/pptx-presentation-builder.php';
if (!isYandexConfigured() || getAiTextProvider() !== 'yandex' || getAiImageProvider() !== 'yandex') throw new RuntimeException('Provider config failed');
$presentation = ['title' => 'Yandex site check', 'class_title' => '5', 'source_words' => '["farmyard"]'];
$result = generateAiWordPresentationCards($presentation, 'yandex');
if (count($result['cards']) !== 1) throw new RuntimeException('Card count');
echo "HOST: Yandex phrase generation OK\n";
$draft = generateAiInteractiveTestDraft(['class_title' => '5', 'topic' => 'Farm animals', 'test_template' => 'vocabulary', 'source_notes' => '', 'instructions' => 'Simple vocabulary'], [], 'yandex');
if (count($draft['questions']) !== 10) throw new RuntimeException('Test count');
echo "HOST: Yandex 10-question test OK\n";
$temp = ROOT . 'storage/temp/yandex-smoke-' . bin2hex(random_bytes(8));
if (!mkdir($temp, 0700, true)) throw new RuntimeException('Cannot create smoke directory');
try {
    generateYandexImage(YANDEX_API_KEY, $result['cards'][0], $temp . '/image.png');
    $result['cards'][0]['image_path'] = substr($temp, strlen(ROOT)) . '/image.png';
    buildWordPresentationPptx($presentation, $result['cards'], $temp . '/test.pptx');
    $zip = new ZipArchive();
    $zip->open($temp . '/test.pptx');
    if (!$zip->getFromName('ppt/media/image2.png') || !str_contains($zip->getFromName('ppt/slides/slide5.xml'), 'Check your answers')) throw new RuntimeException('PPTX media or answers missing');
    $zip->close();
    echo "HOST: Yandex image and PPTX with quiz/answers OK\n";
} finally {
    foreach (['image.png', 'test.pptx'] as $file) if (is_file($temp . '/' . $file)) unlink($temp . '/' . $file);
    rmdir($temp);
}
