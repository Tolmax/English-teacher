<?php

require_once ROOT . 'app/services/yandex-service.php';

if (!isPost()) {
    abort404();
}

$presentationId = requireNumericId($segments[2] ?? null);
requirePresentationCsrf();
$presentation = getAiWordPresentationById($presentationId);
requireFound($presentation);

$publish = (string)($_POST['published'] ?? '') === '1';
$returnToList = (string)($_POST['return_to'] ?? '') === 'index';
$deckCards = aiWordPresentationDeckCards($presentation);

if ($publish && $deckCards === []) {
    setFlash('admin_error', 'Колода ещё не готова: соберите презентацию с картинками и переводами.');
    redirectTo($returnToList ? 'admin/ai-presentations' : 'admin/ai-presentations/' . $presentationId . '/edit');
}

setAiWordPresentationDeckPublished($presentationId, $publish);
setFlash('admin', $publish
    ? 'Колода опубликована на странице класса.'
    : 'Колода скрыта со страницы класса.');
redirectTo($returnToList ? 'admin/ai-presentations' : 'admin/ai-presentations/' . $presentationId . '/edit');
