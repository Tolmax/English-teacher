<?php

require ROOT . 'app/models/material-file.php';
require ROOT . 'app/services/pptx-preview.php';
require_once ROOT . 'app/services/presentation-slides.php';

$presentationId = requireNumericId($segments[2] ?? null);
$presentation = getAiWordPresentationById($presentationId);
requireFound($presentation);

$cards = aiWordPresentationCards($presentation);
$pptxFile = false;
$pptxPath = '';
$pptxPreviewSlides = [];

if ((int)($presentation['pptx_file_id'] ?? 0) > 0) {
    $pptxFile = getMaterialFileById((int)$presentation['pptx_file_id']);
    if ($pptxFile !== false) {
        $pptxPath = ROOT . 'uploads/materials/' . (int)$pptxFile['material_id'] . '/' . (string)$pptxFile['stored_name'];
        $pptxPreviewSlides = buildPptxPreviewSlides($pptxPath);
    }
}

if (empty($cards) && $pptxFile === false) {
    abort404();
}

renderTemplate('pages/admin/ai-presentations/show.tpl', [
    'presentation' => $presentation,
    'cards' => $cards,
    'presentationSlides' => buildPresentationPlayerSlides($cards),
    'pptxFile' => $pptxFile,
    'pptxPreviewSlides' => $pptxPreviewSlides,
]);
