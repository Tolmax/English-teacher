<?php

require_once ROOT . 'app/services/pptx-presentation-builder.php';

function buildPresentationPlayerSlides(array $cards): array
{
    $slides = [];
    foreach ($cards as $card) {
        $slides[] = ['type' => 'image'] + $card;
        $slides[] = ['type' => 'text'] + $card;
    }
    $quizCards = array_values(array_filter($cards, static fn(array $card): bool => trim((string)($card['quiz_sentence'] ?? '')) !== ''));
    $words = array_column($quizCards, 'english_word');
    shuffle($words);
    foreach (array_chunk($quizCards, 6) as $page => $items) {
        $slides[] = ['type' => 'quiz', 'words' => $words, 'sentences' => array_map('pptxQuizSentenceWithBlank', $items), 'offset' => $page * 6];
    }
    foreach (array_chunk($quizCards, 10) as $page => $items) {
        $slides[] = ['type' => 'answers', 'sentences' => array_column($items, 'quiz_sentence'), 'offset' => $page * 10];
    }
    return $slides;
}
