<?php

require_once ROOT . 'app/models/material.php';
require_once ROOT . 'app/models/material-file.php';
require_once ROOT . 'app/services/file-upload.php';
require_once ROOT . 'app/services/openai-teacher-assistant.php';
require_once ROOT . 'app/services/ai-word-presentation-images.php';

$classes = getAllLearningClasses(true);
$errors = [];
$old = [
    'class_id' => (int)($_GET['class_id'] ?? 0),
    'title' => 'Презентация слов',
    'source_words_text' => '',
];

if (isPost()) {
    $pptxFile = $_FILES['pptx_file'] ?? null;
    $hasPptxUpload = hasUploadedSingleFile(is_array($pptxFile) ? $pptxFile : null);
    $old = [
        'class_id' => (int)($_POST['class_id'] ?? 0),
        'title' => trim((string)($_POST['title'] ?? '')),
        'source_words_text' => trim((string)($_POST['source_words_text'] ?? '')),
    ];

    $errors = $hasPptxUpload
        ? validateAiWordPresentationUploadData($old, $pptxFile)
        : validateAiWordPresentationData($old);

    if (empty($errors) && getLearningClassById((int)$old['class_id']) === false) {
        $errors['class_id'] = 'Выберите существующий класс.';
    }

    if (empty($errors)) {
        if ($hasPptxUpload) {
            $presentationId = createUploadedAiWordPresentation($old, $pptxFile);
            setFlash('admin', 'Готовая PPTX-презентация загружена. Она доступна в админке и не показывается на странице класса.');
        } else {
            $presentationId = createAiWordPresentation([
                'class_id' => (int)$old['class_id'],
                'title' => $old['title'],
                'source_words' => normalizeAiWordPresentationWords($old['source_words_text']),
                'cards_json' => [],
                'status' => 'draft',
            ]);
            setFlash('admin', 'Слова сохранены. Следующий шаг: выберите AI-провайдера и нажмите «Сгенерировать карточки», затем «Собрать презентацию».');
        }

        redirectTo('admin/ai-presentations/' . $presentationId . '/edit');
    }
}

renderTemplate('pages/admin/ai-presentations/form.tpl', [
    'mode' => 'create',
    'classes' => $classes,
    'errors' => $errors,
    'old' => $old,
    'flash' => getFlash('admin'),
    'errorFlash' => getFlash('admin_error'),
]);

function validateAiWordPresentationUploadData(array $data, array $pptxFile): array
{
    $errors = [];
    $title = trim((string)($data['title'] ?? ''));

    if ((int)($data['class_id'] ?? 0) <= 0) {
        $errors['class_id'] = 'Выберите класс.';
    }

    if ($title === '') {
        $errors['title'] = 'Введите название презентации.';
    }

    $pptxError = validatePptxUploadedFile($pptxFile);
    if ($pptxError !== '') {
        $errors['pptx_file'] = $pptxError;
    }

    return $errors;
}

function createUploadedAiWordPresentation(array $data, array $pptxFile): int
{
    $materialId = createMaterial([
        'class_id' => (int)$data['class_id'],
        'type' => 'presentation',
        'title' => (string)$data['title'],
        'description' => 'Готовая PPTX-презентация, загруженная учителем.',
        'content' => 'Файл презентации доступен в админке для скачивания и показа с компьютера учителя.',
        'status' => 'published',
        'deadline_at' => '',
        'is_published' => 0,
    ]);

    $savedFile = saveMaterialUploadedFile($pptxFile, $materialId);

    if ($savedFile === false) {
        deleteAiWordPresentationMaterialDirectory($materialId);
        deleteMaterial($materialId);
        setFlash('admin_error', 'Не удалось сохранить PPTX-файл на сервере.');
        redirectTo('admin/ai-presentations/create');
    }

    $fileId = createMaterialFile($savedFile);

    return createAiWordPresentation([
        'material_id' => $materialId,
        'class_id' => (int)$data['class_id'],
        'title' => (string)$data['title'],
        'source_words' => [],
        'cards_json' => [],
        'pptx_file_id' => $fileId,
        'status' => 'published',
        'model' => 'uploaded-pptx',
        'response_id' => '',
    ]);
}
