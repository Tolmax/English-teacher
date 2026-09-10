<?php

require ROOT . 'app/models/material.php';
require ROOT . 'app/models/material-file.php';
require ROOT . 'app/services/ai-word-presentation-images.php';
require ROOT . 'app/services/pptx-presentation-builder.php';

if (!isPost()) {
    abort404();
}

$presentationId = requireNumericId($segments[2] ?? null);
requirePresentationCsrf();
$presentation = getAiWordPresentationById($presentationId);
requireFound($presentation);

$submittedCards = array_key_exists('cards', $_POST)
    ? normalizeAiWordPresentationCards($_POST['cards'])
    : aiWordPresentationCards($presentation);

if (array_key_exists('cards', $_POST)) {
    $submittedData = [
        'class_id' => (int)($_POST['class_id'] ?? $presentation['class_id']),
        'title' => trim((string)($_POST['title'] ?? $presentation['title'])),
        'source_words_text' => array_key_exists('source_words_text', $_POST)
            ? trim((string)$_POST['source_words_text'])
            : aiWordPresentationSourceWordsText($presentation),
    ];
    $submittedErrors = array_merge(
        validateAiWordPresentationData($submittedData),
        validateAiWordPresentationCardsData($submittedCards)
    );
    if ($submittedErrors !== []) {
        setFlash('admin_error', (string)reset($submittedErrors));
        redirectTo('admin/ai-presentations/' . $presentationId . '/edit');
    }
    if (getLearningClassById((int)$submittedData['class_id']) === false) {
        setFlash('admin_error', 'Выберите существующий класс.');
        redirectTo('admin/ai-presentations/' . $presentationId . '/edit');
    }

    updateAiWordPresentation($presentationId, [
        'material_id' => (int)($presentation['material_id'] ?? 0),
        'class_id' => (int)$submittedData['class_id'],
        'title' => $submittedData['title'],
        'source_words' => normalizeAiWordPresentationWords($submittedData['source_words_text']),
        'cards_json' => $submittedCards,
        'pptx_file_id' => (int)($presentation['pptx_file_id'] ?? 0),
        'status' => (string)($presentation['status'] ?? 'ready'),
        'model' => (string)($presentation['model'] ?? ''),
        'response_id' => (string)($presentation['response_id'] ?? ''),
    ]);
    $presentation = getAiWordPresentationById($presentationId);
    requireFound($presentation);
}

$cards = $submittedCards;
if (empty($cards)) {
    setFlash('admin_error', 'Сначала сгенерируйте и проверьте карточки презентации.');
    redirectTo('admin/ai-presentations/' . $presentationId . '/edit');
}

$materialId = 0;
$createdMaterial = false;
$filePath = '';

try {
    $materialData = [
        'class_id' => (int)$presentation['class_id'],
        'type' => 'presentation',
        'title' => (string)$presentation['title'],
        'description' => 'ИИ-презентация по словам для урока английского.',
        'content' => 'Откройте PPTX-файл или экранный показ презентации.',
        'status' => 'published',
        'deadline_at' => '',
        'is_published' => 0,
    ];

    $materialId = (int)($presentation['material_id'] ?? 0);
    if ($materialId > 0 && getMaterialById($materialId) !== false) {
        $existingMaterial = getMaterialById($materialId);
        $materialData['is_published'] = (int)($existingMaterial['is_published'] ?? 0);
        updateMaterial($materialId, $materialData);
    } else {
        $materialId = createMaterial($materialData);
        $createdMaterial = true;
    }

    $storedName = 'ai_word_presentation_' . $presentationId . '_' . date('Ymd_His') . '.pptx';
    $filePath = ROOT . 'uploads/materials/' . $materialId . '/' . $storedName;

    $cards = prepareAiWordPresentationImages($cards, $materialId, trim((string)($_POST['ai_image_provider'] ?? AI_IMAGE_PROVIDER)));
    buildWordPresentationPptx($presentation, $cards, $filePath);

    $fileId = createMaterialFile([
        'material_id' => $materialId,
        'original_name' => trim((string)$presentation['title']) . '.pptx',
        'stored_name' => $storedName,
        'mime_type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'file_size' => filesize($filePath) ?: 0,
    ]);

    $oldFileId = (int)($presentation['pptx_file_id'] ?? 0);
    if ($oldFileId > 0) {
        $oldFile = getMaterialFileById($oldFileId);
        if ($oldFile !== false) {
            $oldPath = ROOT . 'uploads/materials/' . (int)$oldFile['material_id'] . '/' . (string)$oldFile['stored_name'];
            if (is_file($oldPath)) {
                unlink($oldPath);
            }
            deleteMaterialFile($oldFileId);
        }
    }

    updateAiWordPresentationCards(
        $presentationId,
        $cards,
        (string)($presentation['status'] ?? 'ready'),
        (string)($presentation['model'] ?? ''),
        (string)($presentation['response_id'] ?? '')
    );
    publishAiWordPresentation($presentationId, $materialId, $fileId);

    setFlash('admin', 'PPTX и колода собраны. Колоду можно отдельно опубликовать на странице класса.');
} catch (Throwable $exception) {
    if ($filePath !== '' && is_file($filePath)) {
        unlink($filePath);
    }

    if ($createdMaterial && $materialId > 0) {
        deleteAiWordPresentationMaterialDirectory($materialId);
        deleteMaterial($materialId);
    }

    setFlash('admin_error', 'Не удалось собрать PPTX: ' . $exception->getMessage());
}

redirectTo('admin/ai-presentations/' . $presentationId . '/edit');
