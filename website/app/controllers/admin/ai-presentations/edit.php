<?php

require_once ROOT . 'app/services/openai-teacher-assistant.php';
require_once ROOT . 'app/models/material.php';

$presentationId = requireNumericId($segments[2] ?? null);
$presentation = getAiWordPresentationById($presentationId);
requireFound($presentation);

$classes = getAllLearningClasses(true);
$errors = [];
$old = [
    'class_id' => (int)$presentation['class_id'],
    'title' => (string)$presentation['title'],
    'source_words_text' => aiWordPresentationSourceWordsText($presentation),
    'cards' => aiWordPresentationCards($presentation),
];
$isUploadedPptx = isUploadedAiWordPresentation($presentation);

if (isPost()) {
    $old = [
        'class_id' => (int)($_POST['class_id'] ?? 0),
        'title' => trim((string)($_POST['title'] ?? '')),
        'source_words_text' => trim((string)($_POST['source_words_text'] ?? '')),
        'cards' => normalizeAiWordPresentationCards($_POST['cards'] ?? []),
    ];
    $errors = $isUploadedPptx
        ? validateUploadedAiWordPresentationEditData($old)
        : validateAiWordPresentationData($old);

    if (!$isUploadedPptx) {
        $errors = array_merge($errors, validateAiWordPresentationCardsData($old['cards']));
    }

    if (empty($errors) && getLearningClassById((int)$old['class_id']) === false) {
        $errors['class_id'] = 'Выберите существующий класс.';
    }

    if (empty($errors)) {
        $materialId = (int)($presentation['material_id'] ?? 0);
        updateAiWordPresentation($presentationId, [
            'material_id' => $materialId,
            'class_id' => (int)$old['class_id'],
            'title' => $old['title'],
            'source_words' => $isUploadedPptx ? [] : normalizeAiWordPresentationWords($old['source_words_text']),
            'cards_json' => $isUploadedPptx ? [] : $old['cards'],
            'pptx_file_id' => (int)($presentation['pptx_file_id'] ?? 0),
            'status' => (string)($presentation['status'] ?? 'draft'),
            'model' => (string)($presentation['model'] ?? ''),
            'response_id' => (string)($presentation['response_id'] ?? ''),
        ]);

        if ($materialId > 0 && getMaterialById($materialId) !== false) {
            $existingMaterial = getMaterialById($materialId);
            updateMaterial($materialId, [
                'class_id' => (int)$old['class_id'],
                'type' => 'presentation',
                'title' => $old['title'],
                'description' => $isUploadedPptx
                    ? 'Готовая PPTX-презентация, загруженная учителем.'
                    : 'ИИ-презентация по словам для урока английского.',
                'content' => $isUploadedPptx
                    ? 'Файл презентации доступен в админке для скачивания и показа с компьютера учителя.'
                    : 'Откройте PPTX-файл или экранный показ презентации.',
                'status' => 'published',
                'deadline_at' => '',
                'is_published' => (int)($existingMaterial['is_published'] ?? 0),
            ]);
        }

        setFlash('admin', 'Презентация обновлена.');
        redirectTo('admin/ai-presentations/' . $presentationId . '/edit');
    }
}

renderTemplate('pages/admin/ai-presentations/form.tpl', [
    'mode' => 'edit',
    'classes' => $classes,
    'errors' => $errors,
    'old' => $old,
    'presentation' => $presentation,
    'flash' => getFlash('admin'),
    'errorFlash' => getFlash('admin_error'),
]);

function isUploadedAiWordPresentation(array $presentation): bool
{
    return (int)($presentation['pptx_file_id'] ?? 0) > 0
        && aiWordPresentationSourceWords($presentation) === []
        && aiWordPresentationCards($presentation) === [];
}

function validateUploadedAiWordPresentationEditData(array $data): array
{
    $errors = [];
    $title = trim((string)($data['title'] ?? ''));

    if ((int)($data['class_id'] ?? 0) <= 0) {
        $errors['class_id'] = 'Выберите класс.';
    }

    if ($title === '') {
        $errors['title'] = 'Введите название презентации.';
    }

    return $errors;
}
