<?php

require ROOT . 'app/models/ai-word-presentation.php';

$presentationId = requireNumericId($segments[1] ?? null);
$presentation = getAiWordPresentationById($presentationId);
requireFound($presentation);

if (!in_array((string)($presentation['status'] ?? ''), ['ready', 'published'], true) || !aiWordPresentationDeckIsPublished($presentation)) {
    abort404();
}

$cards = aiWordPresentationDeckCards($presentation);
requireFound($cards);

renderTemplate('pages/flashcards/show.tpl', [
    'presentation' => $presentation,
    'cards' => $cards,
]);
