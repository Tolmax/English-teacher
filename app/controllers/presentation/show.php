<?php

require ROOT . 'app/models/ai-word-presentation.php';

$presentationId = requireNumericId($segments[1] ?? null);
$presentation = getAiWordPresentationById($presentationId);
requireFound($presentation);

if (($presentation['status'] ?? '') !== 'published') {
    abort404();
}

$cards = aiWordPresentationCards($presentation);
requireFound($cards);

renderTemplate('pages/presentation/show.tpl', [
    'presentation' => $presentation,
    'cards' => $cards,
]);
