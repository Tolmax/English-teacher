<?php

require_once ROOT . 'app/services/pptx-presentation-builder.php';

function buildPresentationPlayerSlides(array $cards): array
{
    $slides = [];
    foreach (array_values($cards) as $cardIndex => $card) {
        $cardNumber = $cardIndex + 1;
        $slides[] = ['type' => 'image', 'card_number' => $cardNumber] + $card;
        $slides[] = ['type' => 'text', 'card_number' => $cardNumber] + $card;
    }
    $quizCards = array_values(array_filter($cards, static fn(array $card): bool => trim((string)($card['quiz_sentence'] ?? '')) !== ''));
    $words = array_column($quizCards, 'english_word');
    shuffle($words);
    foreach (array_chunk($quizCards, 2) as $page => $items) {
        $slides[] = ['type' => 'quiz', 'words' => $words, 'sentences' => array_map('pptxQuizSentenceWithBlank', $items), 'offset' => $page * 2];
    }
    foreach (array_chunk($quizCards, 3) as $page => $items) {
        $slides[] = ['type' => 'answers', 'sentences' => array_column($items, 'quiz_sentence'), 'offset' => $page * 3];
    }
    return $slides;
}
