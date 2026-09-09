<?php

require ROOT . 'app/validators/calendar-event.php';
require ROOT . 'app/models/material.php';

$eventId = requireNumericId($segments[2] ?? null);
$event = getCalendarEventById($eventId);
requireFound($event);

$classes = getAllLearningClasses(true);
$errors = [];
$old = [
    'class_id' => (int)($event['class_id'] ?? 0),
    'title' => $event['title'],
    'event_type' => $event['event_type'],
    'description' => $event['description'] ?? '',
    'lesson_number' => (int)($event['lesson_number'] ?? 0),
    'homework_content' => $event['homework_content'] ?? '',
    'material_id' => (int)($event['material_id'] ?? 0),
    'starts_at' => calendarEventInputDateTime($event['starts_at']),
    'ends_at' => calendarEventInputDateTime($event['ends_at'] ?? ''),
    'recurrence_group' => $event['recurrence_group'] ?? '',
    'recurrence_rule' => $event['recurrence_rule'] ?? 'none',
    'recurrence_source_id' => (int)($event['recurrence_source_id'] ?? 0),
    'is_published' => (int)$event['is_published'],
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
        'recurrence_group' => $event['recurrence_group'] ?? '',
        'recurrence_rule' => $event['recurrence_rule'] ?? 'none',
        'recurrence_source_id' => (int)($event['recurrence_source_id'] ?? 0),
        'is_published' => isset($_POST['is_published']) ? 1 : 0,
    ];

    $errors = validateCalendarEventData($old);

    if (empty($errors) && findDuplicateCalendarEventId($old, $eventId) > 0) {
        $errors['starts_at'] = 'Такой урок или событие уже есть в календаре.';
    }

    if (empty($errors)) {
        updateCalendarEvent($eventId, $old);
        if (
            $old['event_type'] === 'lesson'
            && (int)$old['class_id'] > 0
            && (string)($event['recurrence_rule'] ?? 'none') === 'weekly'
        ) {
            ensureWeeklyLessonSeries($eventId);
        }
        setFlash('admin', 'Событие календаря обновлено.');
        redirectTo('admin/calendar');
    }
}

renderTemplate('pages/admin/calendar/form.tpl', [
    'mode' => 'edit',
    'event' => $event,
    'classes' => $classes,
    'materials' => getMaterialsForAdmin(),
    'eventTypes' => calendarEventTypes(),
    'errors' => $errors,
    'old' => $old,
]);
