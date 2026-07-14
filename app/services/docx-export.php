<?php

function buildAiMaterialDocx(array $material): array
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('ZipArchive is not available.');
    }

    $tempPath = tempnam(ROOT . 'storage/temp', 'ai-docx-');

    if ($tempPath === false) {
        throw new RuntimeException('Cannot create a temporary DOCX file.');
    }

    $zip = new ZipArchive();

    if ($zip->open($tempPath, ZipArchive::OVERWRITE) !== true) {
        @unlink($tempPath);
        throw new RuntimeException('Cannot open a temporary DOCX archive.');
    }

    $zip->addFromString('[Content_Types].xml', docxContentTypesXml());
    $zip->addFromString('_rels/.rels', docxRootRelsXml());
    $zip->addFromString('word/_rels/document.xml.rels', docxDocumentRelsXml());
    $zip->addFromString('word/styles.xml', docxStylesXml());
    $zip->addFromString('word/document.xml', docxDocumentXml($material));
    $zip->close();

    return [
        'path' => $tempPath,
        'filename' => aiMaterialDocxFilename($material),
    ];
}

function aiMaterialDocxFilename(array $material): string
{
    $baseName = (string)($material['slug'] ?? '');
    $baseName = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $baseName) ?? '';
    $baseName = trim($baseName, '-_');

    if ($baseName === '') {
        $baseName = 'ai-material-' . (int)($material['id'] ?? 0);
    }

    return $baseName . '.docx';
}

function docxDocumentXml(array $material): string
{
    $paragraphs = [
        ['text' => (string)($material['title'] ?? 'AI material'), 'style' => 'Title'],
        ['text' => 'Предмет: ' . (string)($material['subject'] ?? '')],
        ['text' => 'Класс: ' . (string)($material['class_title'] ?? '')],
        ['text' => 'Тема: ' . (string)($material['topic'] ?? '')],
        ['text' => 'Тип материала: ' . docxMaterialTypeLabel((string)($material['material_type'] ?? ''))],
    ];

    if (!empty($material['estimated_duration'])) {
        $paragraphs[] = ['text' => 'Длительность: ' . (string)$material['estimated_duration']];
    }

    $paragraphs[] = ['text' => ''];

    foreach (docxSplitParagraphs((string)($material['edited_content'] ?? '')) as $line) {
        $paragraphs[] = ['text' => $line];
    }

    $body = '';

    foreach ($paragraphs as $paragraph) {
        $body .= docxParagraphXml($paragraph['text'], $paragraph['style'] ?? null);
    }

    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas" '
        . 'xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" '
        . 'xmlns:o="urn:schemas-microsoft-com:office:office" '
        . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" '
        . 'xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" '
        . 'xmlns:v="urn:schemas-microsoft-com:vml" '
        . 'xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing" '
        . 'xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" '
        . 'xmlns:w10="urn:schemas-microsoft-com:office:word" '
        . 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
        . 'xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" '
        . 'xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup" '
        . 'xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk" '
        . 'xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml" '
        . 'xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape" '
        . 'mc:Ignorable="w14 wp14">'
        . '<w:body>'
        . $body
        . '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="708" w:footer="708" w:gutter="0"/></w:sectPr>'
        . '</w:body></w:document>';
}

function docxParagraphXml(string $text, ?string $style = null): string
{
    $styleXml = $style !== null ? '<w:pPr><w:pStyle w:val="' . docxXml($style) . '"/></w:pPr>' : '';

    if ($text === '') {
        return '<w:p>' . $styleXml . '</w:p>';
    }

    return '<w:p>' . $styleXml . '<w:r><w:t xml:space="preserve">' . docxXml($text) . '</w:t></w:r></w:p>';
}

function docxSplitParagraphs(string $text): array
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $lines = explode("\n", $text);
    $paragraphs = [];

    foreach ($lines as $line) {
        $paragraphs[] = rtrim($line);
    }

    return $paragraphs;
}

function docxXml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
}

function docxMaterialTypeLabel(string $type): string
{
    return [
        'worksheet' => 'Рабочий лист',
        'quiz' => 'Мини-тест',
        'lesson_plan' => 'План урока',
        'homework' => 'Домашнее задание',
        'explanation' => 'Объяснение',
    ][$type] ?? 'Материал';
}

function docxContentTypesXml(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
        . '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
        . '</Types>';
}

function docxRootRelsXml(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
        . '</Relationships>';
}

function docxDocumentRelsXml(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"/>'
        ;
}

function docxStylesXml(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
        . '<w:style w:type="paragraph" w:styleId="Normal"><w:name w:val="Normal"/></w:style>'
        . '<w:style w:type="paragraph" w:styleId="Title"><w:name w:val="Title"/><w:basedOn w:val="Normal"/><w:pPr><w:spacing w:after="240"/></w:pPr><w:rPr><w:b/><w:sz w:val="32"/></w:rPr></w:style>'
        . '</w:styles>';
}
