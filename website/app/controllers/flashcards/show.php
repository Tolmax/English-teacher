<?php

require ROOT . 'app/models/ai-word-presentation.php';

$presentationId = requireNumericId($segments[1] ?? null);
$presentation = getAiWordPresentationById($presentationId);
requireFound($presentation);

$isDeckPublished = aiWordPresentationDeckIsPublished($presentation);
$isTeacherPreview = isAdmin() && !$isDeckPublished;

if (!in_array((string)($presentation['status'] ?? ''), ['ready', 'published'], true) || (!$isDeckPublished && !$isTeacherPreview)) {
    abort404();
}

$cards = aiWordPresentationDeckCards($presentation);
requireFound($cards);

renderTemplate('pages/flashcards/show.tpl', [
    'presentation' => $presentation,
    'cards' => $cards,
    'isTeacherPreview' => $isTeacherPreview,
]);
