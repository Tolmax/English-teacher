<?php

function buildWordPresentationPptx(array $presentation, array $cards, string $outputPath): void
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('На компьютере недоступно расширение ZipArchive для сборки PPTX.');
    }

    $cards = array_values($cards);
    if (empty($cards)) {
        throw new RuntimeException('Нет карточек для сборки презентации.');
    }

    $dir = dirname($outputPath);
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new RuntimeException('Не удалось создать папку для презентации.');
    }

    $zip = new ZipArchive();
    if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Не удалось создать PPTX-файл.');
    }

    $slides = [[
        'type' => 'cover',
        'title' => (string)($presentation['title'] ?? 'Word presentation'),
        'subtitle' => 'English word cards',
        'hint' => count($cards) . ' words',
        'image_prompt' => '',
        'image_path' => 'assets/img/presentation-cover.png',
    ]];

    foreach ($cards as $card) {
        $slides[] = array_merge($card, ['type' => 'image']);
        $slides[] = array_merge($card, [
            'type' => 'text',
            'image_path' => '',
        ]);
    }

    $quizCards = array_values(array_filter($cards, static fn(array $card): bool => trim((string)($card['quiz_sentence'] ?? $card['example_sentence'] ?? '')) !== ''));
    $quizWords = array_map(static fn(array $card): string => (string)($card['english_word'] ?? ''), $quizCards);
    shuffle($quizWords);
    foreach (array_chunk($quizCards, 2) as $quizPageIndex => $pageCards) {
        $slides[] = [
            'type' => 'quiz',
            'words' => $quizWords,
            'cards' => $pageCards,
            'offset' => $quizPageIndex * 2,
        ];
    }

    foreach (array_chunk($quizCards, 3) as $answerPageIndex => $answerCards) {
        $slides[] = ['type' => 'answers', 'cards' => $answerCards, 'offset' => $answerPageIndex * 3];
    }

    $zip->addFromString('[Content_Types].xml', pptxContentTypesXml(count($slides)));
    $zip->addFromString('_rels/.rels', pptxRootRelsXml());
    $zip->addFromString('docProps/core.xml', pptxCoreXml((string)($presentation['title'] ?? 'Word presentation')));
    $zip->addFromString('docProps/app.xml', pptxAppXml(count($slides)));
    $zip->addFromString('ppt/presentation.xml', pptxPresentationXml(count($slides)));
    $zip->addFromString('ppt/_rels/presentation.xml.rels', pptxPresentationRelsXml(count($slides)));
    $zip->addFromString('ppt/presProps.xml', pptxPresPropsXml());
    $zip->addFromString('ppt/viewProps.xml', pptxViewPropsXml());
    $zip->addFromString('ppt/tableStyles.xml', pptxTableStylesXml());
    $zip->addFromString('ppt/theme/theme1.xml', pptxThemeXml());
    $zip->addFromString('ppt/slideMasters/slideMaster1.xml', pptxSlideMasterXml());
    $zip->addFromString('ppt/slideMasters/_rels/slideMaster1.xml.rels', pptxSlideMasterRelsXml());
    $zip->addFromString('ppt/slideLayouts/slideLayout1.xml', pptxSlideLayoutXml());
    $zip->addFromString('ppt/slideLayouts/_rels/slideLayout1.xml.rels', pptxSlideLayoutRelsXml());

    foreach ($slides as $index => $slide) {
        $slideNumber = $index + 1;
        $imagePath = pptxResolveImagePath($slide);
        $imageTarget = '';

        if ($imagePath !== '') {
            $extension = pptxImageExtension($imagePath);
            $imageTarget = 'media/image' . $slideNumber . '.' . $extension;
            $zip->addFile($imagePath, 'ppt/' . $imageTarget);
        }

        $zip->addFromString(
            'ppt/slides/slide' . $slideNumber . '.xml',
            pptxSlideXml($slide, $slideNumber, $imageTarget !== '' ? 'rId1' : '')
        );
        $zip->addFromString('ppt/slides/_rels/slide' . $slideNumber . '.xml.rels', pptxSlideRelsXml($imageTarget));
    }

    if (!$zip->close()) {
        throw new RuntimeException('Не удалось сохранить PPTX-файл.');
    }
}

function pptxContentTypesXml(int $slidesCount): string
{
    $slides = '';
    for ($i = 1; $i <= $slidesCount; $i++) {
        $slides .= '<Override PartName="/ppt/slides/slide' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slide+xml"/>';
    }

    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Default Extension="png" ContentType="image/png"/>'
        . '<Default Extension="jpg" ContentType="image/jpeg"/>'
        . '<Default Extension="jpeg" ContentType="image/jpeg"/>'
        . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
        . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
        . '<Override PartName="/ppt/presentation.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.presentation.main+xml"/>'
        . '<Override PartName="/ppt/presProps.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.presProps+xml"/>'
        . '<Override PartName="/ppt/viewProps.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.viewProps+xml"/>'
        . '<Override PartName="/ppt/tableStyles.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.tableStyles+xml"/>'
        . '<Override PartName="/ppt/slideMasters/slideMaster1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideMaster+xml"/>'
        . '<Override PartName="/ppt/slideLayouts/slideLayout1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideLayout+xml"/>'
        . '<Override PartName="/ppt/theme/theme1.xml" ContentType="application/vnd.openxmlformats-officedocument.theme+xml"/>'
        . $slides
        . '</Types>';
}

function pptxRootRelsXml(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="ppt/presentation.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
        . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
        . '</Relationships>';
}

function pptxPresentationXml(int $slidesCount): string
{
    $slideIds = '';
    for ($i = 1; $i <= $slidesCount; $i++) {
        $slideIds .= '<p:sldId id="' . (255 + $i) . '" r:id="rId' . ($i + 1) . '"/>';
    }

    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<p:presentation xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">'
        . '<p:sldMasterIdLst><p:sldMasterId id="2147483648" r:id="rId1"/></p:sldMasterIdLst>'
        . '<p:sldIdLst>' . $slideIds . '</p:sldIdLst>'
        . '<p:sldSz cx="12192000" cy="6858000"/>'
        . '<p:notesSz cx="6858000" cy="9144000"/>'
        . '<p:defaultTextStyle><a:defPPr><a:defRPr lang="en-US"/></a:defPPr></p:defaultTextStyle>'
        . '</p:presentation>';
}

function pptxPresentationRelsXml(int $slidesCount): string
{
    $rels = '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideMaster" Target="slideMasters/slideMaster1.xml"/>';
    for ($i = 1; $i <= $slidesCount; $i++) {
        $rels .= '<Relationship Id="rId' . ($i + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slide" Target="slides/slide' . $i . '.xml"/>';
    }
    $rels .= '<Relationship Id="rId' . ($slidesCount + 2) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/presProps" Target="presProps.xml"/>';
    $rels .= '<Relationship Id="rId' . ($slidesCount + 3) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/viewProps" Target="viewProps.xml"/>';
    $rels .= '<Relationship Id="rId' . ($slidesCount + 4) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/tableStyles" Target="tableStyles.xml"/>';

    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rels . '</Relationships>';
}

function pptxPresPropsXml(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<p:presentationPr xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">'
        . '<p:showPr><p:present/></p:showPr>'
        . '</p:presentationPr>';
}

function pptxViewPropsXml(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<p:viewPr xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">'
        . '<p:normalViewPr><p:restoredLeft sz="15620"/><p:restoredTop sz="94660"/></p:normalViewPr>'
        . '<p:slideViewPr><p:cSldViewPr><p:cViewPr varScale="1"><p:scale><a:sx n="100" d="100"/><a:sy n="100" d="100"/></p:scale><p:origin x="0" y="0"/></p:cViewPr><p:guideLst/></p:cSldViewPr></p:slideViewPr>'
        . '<p:notesTextViewPr><p:cViewPr><p:scale><a:sx n="100" d="100"/><a:sy n="100" d="100"/></p:scale><p:origin x="0" y="0"/></p:cViewPr></p:notesTextViewPr>'
        . '<p:gridSpacing cx="72008" cy="72008"/>'
        . '</p:viewPr>';
}

function pptxTableStylesXml(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<a:tblStyleLst xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" def="{5C22544A-7EE6-4342-B048-85BDC9FD1C3A}"/>';
}

function pptxSlideXml(array $slide, int $slideNumber, string $imageRelId = ''): string
{
    if (($slide['type'] ?? '') === 'answers') return pptxAnswerSlideXml($slide, $slideNumber);
    $isCover = ($slide['type'] ?? '') === 'cover';
    $isImage = ($slide['type'] ?? '') === 'image';
    $isQuiz = ($slide['type'] ?? '') === 'quiz';
    $title = $isCover ? (string)($slide['title'] ?? '') : (string)($slide['english_word'] ?? '');
    $transcription = $isCover ? (string)($slide['subtitle'] ?? '') : (string)($slide['transcription'] ?? '');
    $hint = $isCover ? (string)($slide['hint'] ?? '') : (string)($slide['hint'] ?? '');
    $imageText = (string)($slide['image_prompt'] ?? '');

    if ($isCover) {
        $coverImage = $imageRelId !== ''
            ? pptxPictureXml(3, 'Presentation cover background', $imageRelId, 0, 0, 12192000, 6858000)
            : pptxCoverIllustrationXml();

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">'
            . '<p:cSld><p:spTree>'
            . pptxGroupShapeXml()
            . $coverImage
            . pptxTextOnlyXml(4, 'Cover title', 6900000, 750000, 4650000, 2900000, $title, 3100, '1D4F91', true)
            . pptxTextOnlyXml(5, 'Cover subtitle', 6900000, 3900000, 4650000, 850000, $transcription, 2000, '536177')
            . pptxTextOnlyXml(6, 'Cover hint', 6900000, 4900000, 4650000, 700000, $hint, 1900, '6B7890')
            . '</p:spTree></p:cSld><p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr></p:sld>';
    }

    if ($isImage) {
        $imageShape = $imageRelId !== ''
            ? pptxPictureXml(3, 'Word image', $imageRelId, 3048000, 381000, 6096000, 6096000)
            : pptxShapeXml(3, 'Image placeholder', 3048000, 381000, 6096000, 6096000, 'E9EFF8', 'BFD0E8', $imageText, 2000, '6B7890');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">'
            . '<p:cSld><p:spTree>'
            . pptxGroupShapeXml()
            . pptxShapeXml(2, 'Background', 0, 0, 12192000, 6858000, 'F6F8FC', 'F6F8FC', '')
            . $imageShape
            . pptxShapeXml(4, 'Page', 10700000, 6240000, 900000, 300000, 'F6F8FC', 'F6F8FC', (string)$slideNumber, 1400, '6B7890')
            . '</p:spTree></p:cSld><p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr></p:sld>';
    }


    if ($isQuiz) {
        return pptxQuizSlideXml($slide, $slideNumber);
    }

    $titleBox = pptxAdaptiveTextBox($title, 5200, 1040000);
    $transcriptionBox = pptxAdaptiveTextBox($transcription, 3200, 1200000);
    $hintBox = pptxAdaptiveTextBox($hint, 2934, 2050000);

    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">'
        . '<p:cSld><p:spTree>'
        . pptxGroupShapeXml()
        . pptxShapeXml(2, 'Background', 0, 0, 12192000, 6858000, 'F6F8FC', 'F6F8FC', '')
        . pptxShapeXml(4, 'Word', $titleBox['x'], 450000, $titleBox['width'], 2300000, 'FFFFFF', 'FFFFFF', $title, $titleBox['font'], '1D4F91', true)
        . pptxShapeXml(5, 'Transcription', $transcriptionBox['x'], 2750000, $transcriptionBox['width'], 1300000, 'FFFFFF', 'FFFFFF', $transcription, $transcriptionBox['font'], '536177')
        . pptxShapeXml(6, 'Hint', $hintBox['x'], 4150000, $hintBox['width'], 2150000, 'FFFFFF', 'D8E2F1', $hint, $hintBox['font'], '1E2633')
        . pptxShapeXml(7, 'Page', 10700000, 6240000, 900000, 300000, 'F6F8FC', 'F6F8FC', (string)$slideNumber, 1400, '6B7890')
        . '</p:spTree></p:cSld><p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr></p:sld>';
}

function pptxAdaptiveTextBox(string $text, int $font, int $height): array
{
    // EMU coordinates: keep half-inch margins and expand symmetrically.
    // Conservative width estimate includes padding and room for font differences.
    $width = (int)max(7315200, min(10972800, mb_strlen($text, 'UTF-8') * ($font / 100) * 0.6 * 12700 + 240000));
    $words = preg_split('/\s+/u', trim($text)) ?: [];
    for ($size = $font; $size > 1400; $size -= 100) {
        $capacity = max(1, (int)floor(($width - 240000) / (($size / 100) * 0.6 * 12700)));
        $lines = 1;
        $used = 0;
        foreach ($words as $word) {
            $length = mb_strlen($word, 'UTF-8');
            if ($used && $used + 1 + $length > $capacity) { $lines++; $used = 0; }
            $used += ($used ? 1 : 0) + $length;
            while ($used > $capacity) { $lines++; $used -= $capacity; }
        }
        if ($lines * ($size / 100) * 1.25 * 12700 <= $height - 100000) break;
    }
    return ['x' => (int)((12192000 - $width) / 2), 'width' => $width, 'font' => $size];
}

function pptxQuizSlideXml(array $slide, int $slideNumber): string
{
    $words = array_values(array_filter((array)($slide['words'] ?? []), static fn($word): bool => trim((string)$word) !== ''));
    $cards = array_values((array)($slide['cards'] ?? []));
    $offset = (int)($slide['offset'] ?? 0);
    $palette = [
        ['FFF2C7', '9A6700'],
        ['DCEBFF', '1D4F91'],
        ['FDECEF', '9E3A48'],
        ['E5F6E8', '26733C'],
        ['EFE7FF', '6842A5'],
        ['FFE8D8', 'A34A16'],
    ];

    $marginX = 457200;
    $gapX = 91440;
    $fullWidth = 12192000 - ($marginX * 2);
    $columns = 3;
    $cloudWidth = (int)floor(($fullWidth - ($gapX * ($columns - 1))) / $columns);
    $cloudHeight = 720000;
    $cloudGapY = 45000;
    $cloudTop = 900000;

    $clouds = '';
    $longWords = array_values(array_filter($words, static fn($word): bool => mb_strlen((string)$word, 'UTF-8') > 28));
    $shortWords = array_values(array_filter($words, static fn($word): bool => mb_strlen((string)$word, 'UTF-8') <= 28));
    $layoutWords = [];
    foreach ($longWords as $word) {
        $layoutWords[] = ['word' => $word, 'column' => 0, 'columns' => 3];
    }
    foreach ($shortWords as $index => $word) {
        $layoutWords[] = ['word' => $word, 'column' => $index % $columns, 'columns' => 1];
    }
    $longRows = count($longWords);
    foreach ($layoutWords as $index => $item) {
        $isLong = $item['columns'] === 3;
        $row = $isLong ? $index : $longRows + intdiv($index - $longRows, $columns);
        $column = (int)$item['column'];
        $x = $marginX + ($column * ($cloudWidth + $gapX));
        $y = $cloudTop + ($row * ($cloudHeight + $cloudGapY));
        [$fill, $fontColor] = $palette[$index % count($palette)];
        $clouds .= pptxVectorShapeXml(10 + $index, 'Word cloud ' . ($index + 1), $x, $y, $isLong ? $fullWidth : $cloudWidth, $cloudHeight, 'cloud', $fill, $fill, (string)$item['word'], 1450, $fontColor, true);
    }

    $rows = $longRows + (int)ceil(count($shortWords) / $columns);
    $sentencesTop = max(3000000, $cloudTop + ($rows * ($cloudHeight + $cloudGapY)) + 100000);
    $availableHeight = 6100000 - $sentencesTop;
    $sentenceHeight = (int)floor($availableHeight / max(1, count($cards)));
    $sentences = '';

    foreach ($cards as $index => $card) {
        $sentence = pptxQuizSentenceWithBlank($card);
        $sentences .= pptxTextOnlyXml(
            40 + $index,
            'Quiz sentence ' . ($index + 1),
            914400,
            $sentencesTop + ($index * $sentenceHeight),
            10363200,
            min(620000, $sentenceHeight),
            ($offset + $index + 1) . '. ' . $sentence,
            2467,
            '1E2633'
        );
    }

    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">'
        . '<p:cSld><p:spTree>'
        . pptxGroupShapeXml()
        . pptxShapeXml(2, 'Background', 0, 0, 12192000, 6858000, 'F6F8FC', 'F6F8FC', '')
        . pptxTextOnlyXml(3, 'Quiz title', 609600, 80000, 10972800, 700000, 'Complete the sentences', 2600, '1D4F91', true)
        . $clouds
        . $sentences
        . pptxShapeXml(8, 'Page', 10700000, 6240000, 900000, 300000, 'F6F8FC', 'F6F8FC', (string)$slideNumber, 1400, '6B7890')
        . '</p:spTree></p:cSld><p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr></p:sld>';
}

function pptxAnswerSlideXml(array $slide, int $slideNumber): string
{
    $cards = array_values($slide['cards']);
    $offset = (int)$slide['offset'];
    $rowHeight = (int)floor(5200000 / max(1, count($cards)));
    $shapes = pptxGroupShapeXml()
        . pptxShapeXml(2, 'Background', 0, 0, 12192000, 6858000, 'F6F8FC', 'F6F8FC', '')
        . pptxTextOnlyXml(3, 'Answer title', 609600, 200000, 10972800, 500000, 'Check your answers', 2800, '1D4F91', true);
    foreach ($cards as $index => $card) {
        $sentence = trim((string)($card['quiz_sentence'] ?? $card['example_sentence'] ?? ''));
        $word = (string)$card['english_word'];
        $numbered = ($offset + $index + 1) . '. ' . $sentence;
        $box = pptxAdaptiveTextBox($numbered, 2667, $rowHeight);
        $shape = pptxTextOnlyXml(40 + $index, 'Answer ' . ($offset + $index + 1), 609600, 900000 + $index * $rowHeight, 10972800, $rowHeight, $numbered, $box['font'], '1E2633');
        if ($word !== '' && preg_match('/(?<![\p{L}\p{N}])' . preg_quote($word, '/') . '(?![\p{L}\p{N}])/iu', $numbered, $match, PREG_OFFSET_CAPTURE)) {
            $position = $match[0][1];
            $answer = $match[0][0];
            $runs = '';
            foreach ([[substr($numbered, 0, $position), false], [$answer, true], [substr($numbered, $position + strlen($answer)), false]] as [$text, $highlight]) {
                $runs .= '<a:r><a:rPr lang="en-US" sz="' . pptxProjectorFontSize($box['font']) . '"' . ($highlight ? ' b="1"' : '') . '><a:solidFill><a:srgbClr val="' . ($highlight ? '087F5B' : '1E2633') . '"/></a:solidFill><a:latin typeface="Aptos"/></a:rPr><a:t xml:space="preserve">' . pptxXml($text) . '</a:t></a:r>';
            }
            $shape = preg_replace('/<a:r>.*?<\/a:r>/s', str_replace(['\\', '$'], ['\\\\', '\\$'], $runs), $shape, 1) ?? $shape;
        }
        $shapes .= $shape;
    }
    $shapes .= pptxTextOnlyXml(8, 'Page', 10700000, 6240000, 900000, 300000, (string)$slideNumber, 1400, '6B7890');
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">'
        . '<p:cSld><p:spTree>' . $shapes . '</p:spTree></p:cSld><p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr></p:sld>';
}

function pptxQuizSentenceWithBlank(array $card): string
{
    $word = trim((string)($card['english_word'] ?? ''));
    $sentence = trim((string)($card['quiz_sentence'] ?? $card['example_sentence'] ?? ''));
    if ($word === '' || $sentence === '') {
        return 'Choose the correct word: ...';
    }

    $blanked = preg_replace(
        '/(?<![\p{L}\p{N}])' . preg_quote($word, '/') . '(?![\p{L}\p{N}])/iu',
        '...',
        $sentence,
        1
    );

    return is_string($blanked) && $blanked !== $sentence ? $blanked : 'Choose the correct word: ...';
}

function pptxTextOnlyXml(
    int $id,
    string $name,
    int $x,
    int $y,
    int $cx,
    int $cy,
    string $text,
    int $fontSize,
    string $fontColor,
    bool $bold = false
): string {
    $fontSize = pptxProjectorFontSize($fontSize);
    $boldAttr = $bold ? ' b="1"' : '';

    return '<p:sp>'
        . '<p:nvSpPr><p:cNvPr id="' . $id . '" name="' . pptxXml($name) . '"/><p:cNvSpPr/><p:nvPr/></p:nvSpPr>'
        . '<p:spPr><a:xfrm><a:off x="' . $x . '" y="' . $y . '"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm>'
        . '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:noFill/><a:ln><a:noFill/></a:ln></p:spPr>'
        . '<p:txBody><a:bodyPr wrap="square" anchor="ctr"/><a:lstStyle/>'
        . '<a:p><a:pPr algn="l"/><a:r><a:rPr lang="en-US" sz="' . $fontSize . '"' . $boldAttr . '>'
        . '<a:solidFill><a:srgbClr val="' . $fontColor . '"/></a:solidFill><a:latin typeface="Aptos"/></a:rPr>'
        . '<a:t>' . pptxXml($text) . '</a:t></a:r><a:endParaRPr lang="en-US"/></a:p>'
        . '</p:txBody></p:sp>';
}

function pptxPictureXml(int $id, string $name, string $relId, int $x, int $y, int $cx, int $cy): string
{
    return '<p:pic>'
        . '<p:nvPicPr><p:cNvPr id="' . $id . '" name="' . pptxXml($name) . '"/><p:cNvPicPr><a:picLocks noChangeAspect="1"/></p:cNvPicPr><p:nvPr/></p:nvPicPr>'
        . '<p:blipFill><a:blip r:embed="' . pptxXml($relId) . '"/><a:stretch><a:fillRect/></a:stretch></p:blipFill>'
        . '<p:spPr><a:xfrm><a:off x="' . $x . '" y="' . $y . '"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm>'
        . '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:ln><a:solidFill><a:srgbClr val="BFD0E8"/></a:solidFill></a:ln></p:spPr>'
        . '</p:pic>';
}

function pptxCoverIllustrationXml(): string
{
    return pptxVectorShapeXml(30, 'Cover panel', 609600, 685800, 5030000, 5480000, 'roundRect', 'EAF1FB', 'BFD0E8', '')
        . pptxVectorShapeXml(31, 'Warm accent', 980000, 1090000, 1680000, 1680000, 'ellipse', 'FFF2C7', 'FFF2C7', '')
        . pptxVectorShapeXml(32, 'Cool accent', 3630000, 3600000, 1180000, 1180000, 'ellipse', 'DCEBFF', 'DCEBFF', '')
        . pptxVectorShapeXml(33, 'Globe', 1510000, 1800000, 2220000, 2220000, 'ellipse', '315FB8', '315FB8', '')
        . pptxVectorShapeXml(34, 'Globe shine', 1860000, 2140000, 620000, 620000, 'ellipse', '6FA4FF', '6FA4FF', '')
        . pptxVectorShapeXml(35, 'Globe meridian one', 2100000, 1810000, 260000, 2200000, 'ellipse', '315FB8', 'C9DCFF', '')
        . pptxVectorShapeXml(36, 'Globe meridian two', 2750000, 1810000, 260000, 2200000, 'ellipse', '315FB8', 'C9DCFF', '')
        . pptxVectorShapeXml(37, 'Globe latitude one', 1660000, 2380000, 1920000, 220000, 'ellipse', '315FB8', 'C9DCFF', '')
        . pptxVectorShapeXml(38, 'Globe latitude two', 1660000, 3260000, 1920000, 220000, 'ellipse', '315FB8', 'C9DCFF', '')
        . pptxVectorShapeXml(39, 'Hello card', 3140000, 1240000, 1440000, 720000, 'roundRect', 'FFFFFF', 'D8E2F1', 'Hello!', 2000, '1D4F91', true)
        . pptxVectorShapeXml(40, 'Learn card', 850000, 3950000, 1880000, 720000, 'roundRect', 'FFFFFF', 'D8E2F1', 'Learn English', 1700, '1D4F91', true)
        . pptxVectorShapeXml(41, 'Word chip one', 3300000, 2660000, 1020000, 440000, 'roundRect', 'FDECEF', 'F5BBC2', 'speak', 1500, '9E3A48', true)
        . pptxVectorShapeXml(42, 'Word chip two', 3180000, 3230000, 1200000, 440000, 'roundRect', 'FFF4D8', 'E8C878', 'listen', 1500, '7B5B12', true)
        . pptxVectorShapeXml(43, 'Paper plane body', 4180000, 2050000, 720000, 420000, 'triangle', 'FFFFFF', 'AFC4E8', '')
        . pptxVectorShapeXml(44, 'Paper plane wing', 3970000, 2380000, 520000, 300000, 'triangle', 'BFD0E8', 'AFC4E8', '')
        . pptxVectorShapeXml(45, 'Spark one', 1110000, 1540000, 220000, 220000, 'ellipse', 'EA7A82', 'EA7A82', '')
        . pptxVectorShapeXml(46, 'Spark two', 4470000, 3830000, 170000, 170000, 'ellipse', 'F4C76A', 'F4C76A', '')
        . pptxVectorShapeXml(47, 'Spark three', 1340000, 3440000, 150000, 150000, 'ellipse', '78A5ED', '78A5ED', '');
}

function pptxVectorShapeXml(
    int $id,
    string $name,
    int $x,
    int $y,
    int $cx,
    int $cy,
    string $preset,
    string $fill,
    string $line,
    string $text = '',
    int $fontSize = 1600,
    string $fontColor = '1E2633',
    bool $bold = false
): string {
    $fontSize = pptxProjectorFontSize($fontSize);
    $text = pptxXml($text);
    $boldAttr = $bold ? ' b="1"' : '';
    $textBody = $text === ''
        ? '<p:txBody><a:bodyPr/><a:lstStyle/><a:p><a:endParaRPr lang="en-US"/></a:p></p:txBody>'
        : '<p:txBody><a:bodyPr wrap="square" anchor="ctr"/><a:lstStyle/>'
            . '<a:p><a:pPr algn="ctr"/><a:r><a:rPr lang="en-US" sz="' . $fontSize . '"' . $boldAttr . '>'
            . '<a:solidFill><a:srgbClr val="' . $fontColor . '"/></a:solidFill><a:latin typeface="Aptos"/></a:rPr>'
            . '<a:t>' . $text . '</a:t></a:r><a:endParaRPr lang="en-US"/></a:p></p:txBody>';

    return '<p:sp>'
        . '<p:nvSpPr><p:cNvPr id="' . $id . '" name="' . pptxXml($name) . '"/><p:cNvSpPr/><p:nvPr/></p:nvSpPr>'
        . '<p:spPr><a:xfrm><a:off x="' . $x . '" y="' . $y . '"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm>'
        . '<a:prstGeom prst="' . pptxXml($preset) . '"><a:avLst/></a:prstGeom>'
        . '<a:solidFill><a:srgbClr val="' . $fill . '"/></a:solidFill>'
        . '<a:ln><a:solidFill><a:srgbClr val="' . $line . '"/></a:solidFill></a:ln></p:spPr>'
        . $textBody
        . '</p:sp>';
}

function pptxGroupShapeXml(): string
{
    return '<p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr>'
        . '<p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>';
}

function pptxShapeXml(
    int $id,
    string $name,
    int $x,
    int $y,
    int $cx,
    int $cy,
    string $fill,
    string $line,
    string $text,
    int $fontSize = 2200,
    string $fontColor = '1E2633',
    bool $bold = false
): string {
    $fontSize = pptxProjectorFontSize($fontSize);
    $text = pptxXml($text);
    $boldAttr = $bold ? ' b="1"' : '';

    return '<p:sp>'
        . '<p:nvSpPr><p:cNvPr id="' . $id . '" name="' . pptxXml($name) . '"/><p:cNvSpPr/><p:nvPr/></p:nvSpPr>'
        . '<p:spPr><a:xfrm><a:off x="' . $x . '" y="' . $y . '"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm>'
        . '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom>'
        . '<a:solidFill><a:srgbClr val="' . $fill . '"/></a:solidFill>'
        . '<a:ln><a:solidFill><a:srgbClr val="' . $line . '"/></a:solidFill></a:ln></p:spPr>'
        . '<p:txBody><a:bodyPr wrap="square" anchor="ctr"/><a:lstStyle/>'
        . '<a:p><a:pPr algn="ctr"/><a:r><a:rPr lang="en-US" sz="' . $fontSize . '"' . $boldAttr . '>'
        . '<a:solidFill><a:srgbClr val="' . $fontColor . '"/></a:solidFill><a:latin typeface="Aptos"/></a:rPr>'
        . '<a:t>' . $text . '</a:t></a:r><a:endParaRPr lang="en-US"/></a:p>'
        . '</p:txBody></p:sp>';
}

function pptxProjectorFontSize(int $fontSize): int
{
    return $fontSize * 2;
}

function pptxCoreXml(string $title): string
{
    $now = gmdate('Y-m-d\TH:i:s\Z');

    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
        . '<dc:title>' . pptxXml($title) . '</dc:title><dc:creator>English Teacher</dc:creator>'
        . '<cp:lastModifiedBy>English Teacher</cp:lastModifiedBy>'
        . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created>'
        . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:modified>'
        . '</cp:coreProperties>';
}

function pptxAppXml(int $slidesCount): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
        . '<Application>English Teacher</Application><PresentationFormat>Widescreen</PresentationFormat>'
        . '<Slides>' . $slidesCount . '</Slides></Properties>';
}

function pptxThemeXml(): string
{
    $solidFill = '<a:solidFill><a:schemeClr val="phClr"/></a:solidFill>';
    $line = '<a:ln w="6350"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:ln>';
    $effect = '<a:effectStyle><a:effectLst/></a:effectStyle>';

    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<a:theme xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" name="English Teacher">'
        . '<a:themeElements><a:clrScheme name="English Teacher">'
        . '<a:dk1><a:srgbClr val="1E2633"/></a:dk1><a:lt1><a:srgbClr val="FFFFFF"/></a:lt1>'
        . '<a:dk2><a:srgbClr val="315FB8"/></a:dk2><a:lt2><a:srgbClr val="F6F8FC"/></a:lt2>'
        . '<a:accent1><a:srgbClr val="315FB8"/></a:accent1><a:accent2><a:srgbClr val="EA7A82"/></a:accent2>'
        . '<a:accent3><a:srgbClr val="BFD0E8"/></a:accent3><a:accent4><a:srgbClr val="536177"/></a:accent4>'
        . '<a:accent5><a:srgbClr val="F4C76A"/></a:accent5><a:accent6><a:srgbClr val="E9EFF8"/></a:accent6>'
        . '<a:hlink><a:srgbClr val="315FB8"/></a:hlink><a:folHlink><a:srgbClr val="315FB8"/></a:folHlink>'
        . '</a:clrScheme><a:fontScheme name="Aptos">'
        . '<a:majorFont><a:latin typeface="Aptos Display"/><a:ea typeface=""/><a:cs typeface=""/></a:majorFont>'
        . '<a:minorFont><a:latin typeface="Aptos"/><a:ea typeface=""/><a:cs typeface=""/></a:minorFont>'
        . '</a:fontScheme>'
        . '<a:fmtScheme name="English Teacher">'
        . '<a:fillStyleLst>' . $solidFill . $solidFill . $solidFill . '</a:fillStyleLst>'
        . '<a:lnStyleLst>' . $line . $line . $line . '</a:lnStyleLst>'
        . '<a:effectStyleLst>' . $effect . $effect . $effect . '</a:effectStyleLst>'
        . '<a:bgFillStyleLst>' . $solidFill . $solidFill . $solidFill . '</a:bgFillStyleLst>'
        . '</a:fmtScheme></a:themeElements></a:theme>';
}

function pptxSlideMasterXml(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<p:sldMaster xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">'
        . '<p:cSld><p:spTree>' . pptxGroupShapeXml() . '</p:spTree></p:cSld>'
        . '<p:clrMap bg1="lt1" tx1="dk1" bg2="lt2" tx2="dk2" accent1="accent1" accent2="accent2" accent3="accent3" accent4="accent4" accent5="accent5" accent6="accent6" hlink="hlink" folHlink="folHlink"/>'
        . '<p:sldLayoutIdLst><p:sldLayoutId id="2147483649" r:id="rId1"/></p:sldLayoutIdLst>'
        . '</p:sldMaster>';
}

function pptxSlideMasterRelsXml(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout1.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="../theme/theme1.xml"/>'
        . '</Relationships>';
}

function pptxSlideLayoutXml(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<p:sldLayout xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main" type="blank" preserve="1">'
        . '<p:cSld name="Blank"><p:spTree>' . pptxGroupShapeXml() . '</p:spTree></p:cSld><p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr></p:sldLayout>';
}

function pptxSlideLayoutRelsXml(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideMaster" Target="../slideMasters/slideMaster1.xml"/>'
        . '</Relationships>';
}

function pptxSlideRelsXml(string $imageTarget): string
{
    $rels = '<Relationship Id="rIdLayout1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout1.xml"/>';
    if ($imageTarget !== '') {
        $rels .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../' . pptxXml($imageTarget) . '"/>';
    }

    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rels . '</Relationships>';
}

function pptxResolveImagePath(array $slide): string
{
    $relativePath = trim((string)($slide['image_path'] ?? ''));

    if ($relativePath === '') {
        return '';
    }

    $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
    $path = ROOT . $relativePath;

    if (!is_file($path) && defined('APP_DATA_ROOT')) {
        $path = APP_DATA_ROOT . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    return is_file($path) ? $path : '';
}

function pptxImageExtension(string $path): string
{
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    return in_array($extension, ['png', 'jpg', 'jpeg'], true) ? $extension : 'png';
}

function pptxXml(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8');
}
