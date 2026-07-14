<?php

function buildPptxPreviewSlides(string $pptxPath): array
{
    if (!class_exists('ZipArchive') || !is_file($pptxPath)) {
        return [];
    }

    $zip = new ZipArchive();
    if ($zip->open($pptxPath) !== true) {
        return [];
    }

    $slideNames = [];
    for ($index = 0; $index < $zip->numFiles; $index++) {
        $name = (string)$zip->getNameIndex($index);
        if (preg_match('#^ppt/slides/slide(\d+)\.xml$#', $name, $matches) === 1) {
            $slideNames[(int)$matches[1]] = $name;
        }
    }

    ksort($slideNames);
    $slides = [];

    foreach ($slideNames as $slideNumber => $slideName) {
        $slideXml = (string)$zip->getFromName($slideName);
        if ($slideXml === '') {
            continue;
        }

        $texts = pptxPreviewTextLines($slideXml);
        $images = pptxPreviewImages($zip, $slideName, $slideXml);
        $slides[] = [
            'number' => $slideNumber,
            'title' => $texts[0] ?? 'Слайд ' . $slideNumber,
            'subtitle' => $texts[1] ?? '',
            'body' => array_slice($texts, 2),
            'images' => $images,
        ];
    }

    $zip->close();

    return $slides;
}

function pptxPreviewTextLines(string $slideXml): array
{
    if (preg_match_all('/<a:t>(.*?)<\/a:t>/su', $slideXml, $matches) === false || empty($matches[1])) {
        return [];
    }

    $lines = [];
    foreach ($matches[1] as $value) {
        $text = trim(html_entity_decode((string)$value, ENT_QUOTES | ENT_XML1, 'UTF-8'));
        if ($text !== '') {
            $lines[] = $text;
        }
    }

    return $lines;
}

function pptxPreviewImages(ZipArchive $zip, string $slideName, string $slideXml): array
{
    if (preg_match_all('/r:embed="([^"]+)"/u', $slideXml, $embedMatches) === false || empty($embedMatches[1])) {
        return [];
    }

    $rels = pptxPreviewSlideRelationships($zip, $slideName);
    $images = [];

    foreach (array_unique($embedMatches[1]) as $relId) {
        $target = $rels[$relId] ?? '';
        if ($target === '') {
            continue;
        }

        $mediaPath = pptxPreviewNormalizeZipPath(dirname($slideName) . '/' . $target);
        $bytes = $zip->getFromName($mediaPath);
        if (!is_string($bytes) || $bytes === '') {
            continue;
        }

        $mime = pptxPreviewMimeType($mediaPath);
        $images[] = 'data:' . $mime . ';base64,' . base64_encode($bytes);
    }

    return $images;
}

function pptxPreviewSlideRelationships(ZipArchive $zip, string $slideName): array
{
    $relsName = dirname($slideName) . '/_rels/' . basename($slideName) . '.rels';
    $relsXml = (string)$zip->getFromName($relsName);

    if ($relsXml === '') {
        return [];
    }

    $rels = [];
    if (preg_match_all('/<Relationship\b[^>]*Id="([^"]+)"[^>]*Target="([^"]+)"/u', $relsXml, $matches, PREG_SET_ORDER) === false || empty($matches)) {
        return [];
    }

    foreach ($matches as $match) {
        $rels[(string)$match[1]] = html_entity_decode((string)$match[2], ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    return $rels;
}

function pptxPreviewNormalizeZipPath(string $path): string
{
    $parts = [];

    foreach (explode('/', str_replace('\\', '/', $path)) as $part) {
        if ($part === '' || $part === '.') {
            continue;
        }

        if ($part === '..') {
            array_pop($parts);
            continue;
        }

        $parts[] = $part;
    }

    return implode('/', $parts);
}

function pptxPreviewMimeType(string $path): string
{
    return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
        'jpg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        default => 'image/png',
    };
}
