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
