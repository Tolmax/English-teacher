<?php

require ROOT . 'app/models/ai-word-presentation.php';

$presentationId = requireNumericId($segments[1] ?? null);
$presentation = getAiWordPresentationById($presentationId);
requireFound($presentation);

if (($presentation['status'] ?? '') !== 'published' || !aiWordPresentationDeckIsPublished($presentation)) {
    abort404();
}

$cards = aiWordPresentationDeckCards($presentation);
requireFound($cards);

renderTemplate('pages/flashcards/show.tpl', [
    'presentation' => $presentation,
    'cards' => $cards,
]);
