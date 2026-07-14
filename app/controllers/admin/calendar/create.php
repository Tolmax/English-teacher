<?php

require ROOT . 'app/validators/calendar-event.php';
require ROOT . 'app/models/material.php';

$classes = getAllLearningClasses(true);
$defaultDate = trim((string)($_GET['date'] ?? ''));
$defaultStartsAt = $defaultDate !== '' ? $defaultDate . 'T09:00' : '';
$errors = [];
$old = [
    'class_id' => (int)($_GET['class_id'] ?? 0),
    'title' => 'Урок английского',
    'event_type' => 'lesson',
    'description' => '',
    'lesson_number' => 1,
    'homework_content' => '',
    'material_id' => 0,
    'starts_at' => $defaultStartsAt,
    'ends_at' => '',
    'is_single' => 0,
    'is_published' => 1,
];

if (isPost()) {
    $old = [
        'class_id' => (int)($_POST['class_id'] ?? 0),
        'title' => trim((string)($_POST['title'] ?? '')),
        'event_type' => trim((string)($_POST['event_type'] ?? 'lesson')),
        'description' => trim((string)($_POST['description'] ?? '')),
        'lesson_number' => (int)($_POST['lesson_number'] ?? 0),
        'homework_content' => trim((string)($_POST['homework_content'] ?? '')),
        'material_id' => (int)($_POST['material_id'] ?? 0),
        'starts_at' => trim((string)($_POST['starts_at'] ?? '')),
        'ends_at' => trim((string)($_POST['ends_at'] ?? '')),
        'is_single' => isset($_POST['is_single']) ? 1 : 0,
        'is_published' => isset($_POST['is_published']) ? 1 : 0,
    ];

    $errors = validateCalendarEventData($old);

    if (empty($errors)) {
        if (findDuplicateCalendarEventId($old) > 0) {
            setFlash('admin', 'Такое событие календаря уже есть, повтор не создан.');
            redirectTo('admin/calendar');
        }

        if ($old['event_type'] === 'lesson' && (int)$old['class_id'] > 0 && (int)$old['is_single'] !== 1) {
            createRecurringLessonEvents($old);
            setFlash('admin', 'Урок добавлен и перенесён на следующие недели. Домашка сохранена только для первой даты.');
            redirectTo('admin/calendar');
        }

        createCalendarEvent($old);
        setFlash('admin', 'Событие календаря добавлено.');
        redirectTo('admin/calendar');
    }
}

renderTemplate('pages/admin/calendar/form.tpl', [
    'mode' => 'create',
    'classes' => $classes,
    'materials' => getMaterialsForAdmin(),
    'eventTypes' => calendarEventTypes(),
    'errors' => $errors,
    'old' => $old,
]);
