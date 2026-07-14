<?php

require ROOT . 'app/models/material.php';
require ROOT . 'app/models/material-file.php';
require ROOT . 'app/services/ai-word-presentation-images.php';
require ROOT . 'app/services/pptx-presentation-builder.php';

if (!isPost()) {
    abort404();
}

$presentationId = requireNumericId($segments[2] ?? null);
$presentation = getAiWordPresentationById($presentationId);
requireFound($presentation);

$cards = aiWordPresentationCards($presentation);
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
        updateMaterial($materialId, $materialData);
    } else {
        $materialId = createMaterial($materialData);
        $createdMaterial = true;
    }

    $storedName = 'ai_word_presentation_' . $presentationId . '_' . date('Ymd_His') . '.pptx';
    $filePath = ROOT . 'uploads/materials/' . $materialId . '/' . $storedName;

    $cards = prepareAiWordPresentationImages($cards, $materialId);
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

    setFlash('admin', 'PPTX собран. Презентация доступна в админке и не показывается на странице класса.');
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
