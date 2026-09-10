<?php

require ROOT . 'app/models/class.php';
require ROOT . 'app/models/calendar-event.php';
require ROOT . 'app/models/material.php';
require ROOT . 'app/models/ai-word-presentation.php';

$classes = getAllLearningClasses(true);
$classCards = [];
$publicClassTitles = ['1', '5', '6', '7', '8', '10'];
$classesByTitle = [];

foreach ($classes as $class) {
    $classesByTitle[(string)$class['title']] = $class;
}

foreach ($publicClassTitles as $classTitle) {
    if (!isset($classesByTitle[$classTitle])) {
        continue;
    }

    $class = $classesByTitle[$classTitle];
    $materials = getMaterialsByClassId((int)$class['id']);
    $publishedDecks = getPublishedAiWordPresentationDecksByClassId((int)$class['id']);
    $classCards[] = [
        'class' => $class,
        'latest_material' => $materials[0] ?? null,
        // Presentation materials are intentionally excluded from the regular
        // material query, so count their published flashcard decks separately.
        'material_count' => count($materials) + count($publishedDecks),
    ];
}

renderTemplate('pages/home.tpl', [
    'classCards' => $classCards,
    'calendarEvents' => getUpcomingPublicCalendarEvents(),
]);
