<?php

function validateCalendarEventData(array $data): array
{
    $errors = [];
    $eventTypes = array_keys(calendarEventTypes());

    if (trim((string)($data['title'] ?? '')) === '') {
        $errors['title'] = 'Укажите название события.';
    }

    if (!in_array((string)($data['event_type'] ?? ''), $eventTypes, true)) {
        $errors['event_type'] = 'Выберите тип события.';
    }

    if ((string)($data['event_type'] ?? '') === 'lesson') {
        $lessonNumber = (int)($data['lesson_number'] ?? 0);
        if ($lessonNumber < 1 || $lessonNumber > 10) {
            $errors['lesson_number'] = 'Выберите урок от 1 до 10.';
        }
    }

    if (trim((string)($data['starts_at'] ?? '')) === '') {
        $errors['starts_at'] = 'Укажите дату и время начала.';
    } elseif (!calendarEventDateTimeIsValid((string)$data['starts_at'])) {
        $errors['starts_at'] = 'Укажите корректную дату и время начала.';
    }

    if (trim((string)($data['ends_at'] ?? '')) !== '' && !calendarEventDateTimeIsValid((string)$data['ends_at'])) {
        $errors['ends_at'] = 'Укажите корректную дату и время окончания.';
    }

    if (
        empty($errors['starts_at'])
        && empty($errors['ends_at'])
        && trim((string)($data['ends_at'] ?? '')) !== ''
        && strtotime(calendarEventNormalizeDateTime((string)$data['ends_at'])) < strtotime(calendarEventNormalizeDateTime((string)$data['starts_at']))
    ) {
        $errors['ends_at'] = 'Окончание не может быть раньше начала.';
    }

    return $errors;
}

function calendarEventDateTimeIsValid(string $value): bool
{
    $timestamp = strtotime(calendarEventNormalizeDateTime($value));

    return $timestamp !== false;
}
