<?php

require ROOT . 'app/models/class.php';
require ROOT . 'app/models/calendar-event.php';
require ROOT . 'app/models/material.php';

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
    $classCards[] = [
        'class' => $class,
        'latest_material' => $materials[0] ?? null,
        'material_count' => count($materials),
    ];
}

renderTemplate('pages/home.tpl', [
    'classCards' => $classCards,
    'calendarEvents' => getUpcomingPublicCalendarEvents(),
]);
