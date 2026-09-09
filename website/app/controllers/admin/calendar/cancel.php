<?php

$eventId = requireNumericId($segments[2] ?? null);
$mode = $segments[3] ?? '';
$event = getCalendarEventById($eventId);
requireFound($event);

if (!isPost()) {
    abort404();
}

$redirectWeek = calendarWeekStart(substr((string)$event['starts_at'], 0, 10));

if ($mode === 'cancel-series') {
    $deletedCount = deleteCalendarEventSeriesFromEvent($event);
    setFlash('admin', 'Удалены выбранный урок и будущие повторы: ' . (int)$deletedCount . '.');
    redirectTo('admin/calendar?week=' . $redirectWeek);
}

if ($mode === 'delete-series') {
    $deletedCount = deleteCalendarEventEntireSeriesFromEvent($event);
    setFlash('admin', 'Удалён весь ряд уроков: ' . (int)$deletedCount . '.');
    redirectTo('admin/calendar?week=' . $redirectWeek);
}

deleteCalendarEventById($eventId);
setFlash('admin', 'Урок удалён для выбранной даты.');
redirectTo('admin/calendar?week=' . $redirectWeek);
