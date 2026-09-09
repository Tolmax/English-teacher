<?php

require ROOT . 'app/models/calendar-event.php';
require ROOT . 'app/models/class.php';

$action = $segments[2] ?? 'index';

if ($action === 'create') {
    require ROOT . 'app/controllers/admin/calendar/create.php';
    return;
}

if (is_numeric($action) && ($segments[3] ?? '') === 'edit') {
    require ROOT . 'app/controllers/admin/calendar/edit.php';
    return;
}

if (is_numeric($action) && in_array(($segments[3] ?? ''), ['cancel', 'cancel-series', 'delete-series'], true)) {
    require ROOT . 'app/controllers/admin/calendar/cancel.php';
    return;
}

if ($action !== 'index') {
    abort404();
}

$weekStart = calendarWeekStart(trim((string)($_GET['week'] ?? date('Y-m-d'))));
$weekEnd = date('Y-m-d', strtotime($weekStart . ' +7 days'));
$showWeekend = (int)($_GET['show_weekend'] ?? 0) === 1;

renderTemplate('pages/admin/calendar/index.tpl', [
    'events' => getCalendarEventsForWeek($weekStart, $weekEnd),
    'classes' => getAllLearningClasses(true),
    'eventTypes' => calendarEventTypes(),
    'weekStart' => $weekStart,
    'weekEnd' => $weekEnd,
    'weekDays' => calendarWeekDays($weekStart, $showWeekend ? 7 : 5),
    'previousWeek' => date('Y-m-d', strtotime($weekStart . ' -7 days')),
    'nextWeek' => date('Y-m-d', strtotime($weekStart . ' +7 days')),
    'showWeekend' => $showWeekend,
    'flash' => getFlash('admin'),
]);
