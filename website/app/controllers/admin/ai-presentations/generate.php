<?php

require ROOT . 'app/services/ai-word-presentation-generator.php';

$presentationId = requireNumericId($segments[2] ?? null);
$presentation = getAiWordPresentationById($presentationId);
requireFound($presentation);

if (!isPost()) {
    redirectTo('admin/ai-presentations/' . $presentationId . '/edit');
}

$existingCards = aiWordPresentationCards($presentation);
requirePresentationCsrf();
if (!empty($existingCards)) {
    setFlash(
        'admin_error',
        'Карточки уже созданы. Чтобы не потерять картинки и правки, повторная генерация отключена.'
    );
    redirectTo('admin/ai-presentations/' . $presentationId . '/edit');
}

try {
    // The generation button submits the main edit form. Persist the teacher's
    // current corrections before calling the provider, so a retry never falls
    // back to the original expression stored when the draft was created.
    $submittedWordsText = array_key_exists('source_words_text', $_POST)
        ? trim((string)$_POST['source_words_text'])
        : aiWordPresentationSourceWordsText($presentation);
    $submittedWords = normalizeAiWordPresentationWords($submittedWordsText);
    $generationData = [
        'class_id' => (int)($_POST['class_id'] ?? $presentation['class_id']),
        'title' => trim((string)($_POST['title'] ?? $presentation['title'])),
        'source_words_text' => $submittedWordsText,
    ];
    $generationErrors = validateAiWordPresentationData($generationData);
    if ($generationErrors !== []) {
        throw new RuntimeException((string)reset($generationErrors));
    }
    if (getLearningClassById((int)$generationData['class_id']) === false) {
        throw new RuntimeException('Выберите существующий класс.');
    }

    updateAiWordPresentation($presentationId, [
        'material_id' => (int)($presentation['material_id'] ?? 0),
        'class_id' => (int)$generationData['class_id'],
        'title' => $generationData['title'],
        'source_words' => $submittedWords,
        'cards_json' => [],
        'pptx_file_id' => (int)($presentation['pptx_file_id'] ?? 0),
        'status' => (string)($presentation['status'] ?? 'draft'),
        'model' => (string)($presentation['model'] ?? ''),
        'response_id' => (string)($presentation['response_id'] ?? ''),
    ]);
    $presentation = getAiWordPresentationById($presentationId);
    requireFound($presentation);

    $generated = generateAiWordPresentationCards($presentation, trim((string)($_POST['ai_provider'] ?? AI_TEXT_PROVIDER)));

    updateAiWordPresentationCards(
        $presentationId,
        $generated['cards'],
        'ready',
        (string)($generated['model'] ?? ''),
        (string)($generated['response_id'] ?? '')
    );

    setFlash('admin', 'Карточки для презентации сгенерированы. Проверьте их перед сборкой PPTX.');
} catch (RuntimeException $exception) {
    setFlash('admin_error', $exception->getMessage());
}

redirectTo('admin/ai-presentations/' . $presentationId . '/edit');
