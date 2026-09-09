<?php

function calendarEventTypes(): array
{
    return [
        'lesson' => 'Урок',
        'event' => 'Мероприятие',
        'deadline' => 'Дедлайн',
        'extra_lesson' => 'Допзанятие',
    ];
}

function calendarEventTypeLabel(string $type): string
{
    $types = calendarEventTypes();

    return $types[$type] ?? $type;
}

function getCalendarEventsForAdmin(array $filters = []): array
{
    $db = getDB();
    $where = [];
    $params = [];
    $sql =
        'SELECT calendar_events.*, classes.title AS class_title, materials.title AS material_title
         FROM calendar_events
         LEFT JOIN classes ON classes.id = calendar_events.class_id
         LEFT JOIN materials ON materials.id = calendar_events.material_id';

    if ((int)($filters['class_id'] ?? 0) > 0) {
        $where[] = 'calendar_events.class_id = :class_id';
        $params[':class_id'] = (int)$filters['class_id'];
    }

    if (($filters['event_type'] ?? '') !== '') {
        $where[] = 'calendar_events.event_type = :event_type';
        $params[':event_type'] = $filters['event_type'];
    }

    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY calendar_events.starts_at ASC, calendar_events.id DESC';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function getCalendarEventsForWeek(string $weekStart, string $weekEnd): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT calendar_events.*, classes.title AS class_title, classes.slug AS class_slug,
                materials.title AS material_title
         FROM calendar_events
         LEFT JOIN classes ON classes.id = calendar_events.class_id
         LEFT JOIN materials ON materials.id = calendar_events.material_id
         WHERE calendar_events.starts_at >= :week_start
           AND calendar_events.starts_at < :week_end
         ORDER BY calendar_events.starts_at ASC, classes.title ASC, calendar_events.id ASC'
    );
    $stmt->execute([
        ':week_start' => $weekStart . ' 00:00:00',
        ':week_end' => $weekEnd . ' 00:00:00',
    ]);

    return $stmt->fetchAll();
}

function getUpcomingLessonCalendarByClass(): array
{
    $db = getDB();
    $stmt = $db->query(
        'SELECT calendar_events.*, classes.id AS class_id, classes.title AS class_title, classes.slug AS class_slug,
                materials.title AS material_title
         FROM classes
         LEFT JOIN calendar_events ON calendar_events.id = (
            SELECT ce.id
            FROM calendar_events ce
            WHERE ce.class_id = classes.id
              AND ce.event_type = "lesson"
              AND ce.is_published = 1
              AND ce.starts_at >= datetime("now")
            ORDER BY ce.starts_at ASC, ce.id ASC
            LIMIT 1
         )
         LEFT JOIN materials ON materials.id = calendar_events.material_id
         WHERE classes.is_active = 1
         ORDER BY CAST(classes.title AS INTEGER) ASC, classes.title ASC'
    );

    return $stmt->fetchAll();
}

function getCalendarEventById(int $id): array|false
{
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM calendar_events WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    return $stmt->fetch();
}

function createCalendarEvent(array $data): int
{
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO calendar_events
            (class_id, title, event_type, description, starts_at, ends_at, lesson_number, homework_content, material_id, recurrence_group, recurrence_rule, recurrence_source_id, is_published)
         VALUES
            (:class_id, :title, :event_type, :description, :starts_at, :ends_at, :lesson_number, :homework_content, :material_id, :recurrence_group, :recurrence_rule, :recurrence_source_id, :is_published)'
    );
    $stmt->execute(calendarEventParams($data));

    return (int)$db->lastInsertId();
}

function findDuplicateCalendarEventId(array $data, ?int $excludeId = null): int
{
    $db = getDB();
    $params = calendarEventParams($data);
    $duplicateParams = [
        ':class_id' => $params[':class_id'],
        ':event_type' => $params[':event_type'],
        ':starts_at' => $params[':starts_at'],
        ':lesson_number' => $params[':lesson_number'],
    ];

    $sql =
        'SELECT id
         FROM calendar_events
         WHERE class_id IS :class_id
           AND event_type = :event_type
           AND starts_at = :starts_at
           AND lesson_number IS :lesson_number';

    if ($excludeId !== null) {
        $sql .= ' AND id != :exclude_id';
        $duplicateParams[':exclude_id'] = $excludeId;
    }

    $sql .= ' ORDER BY id ASC LIMIT 1';

    $stmt = $db->prepare($sql);
    $stmt->execute($duplicateParams);

    return (int)($stmt->fetchColumn() ?: 0);
}

function createRecurringLessonEvents(array $data, int $weeks = 52): int
{
    $group = calendarEventGenerateRecurrenceGroup();
    $firstData = $data;
    $firstData['recurrence_group'] = $group;
    $firstData['recurrence_rule'] = 'weekly';
    $firstId = createCalendarEvent($firstData);

    $db = getDB();
    $db->prepare(
        'UPDATE calendar_events
         SET recurrence_source_id = :source_id
         WHERE id = :source_id'
    )->execute([':source_id' => $firstId]);

    $startTimestamp = strtotime(calendarEventNormalizeDateTime((string)$data['starts_at']));
    $endTimestamp = trim((string)($data['ends_at'] ?? '')) !== ''
        ? strtotime(calendarEventNormalizeDateTime((string)$data['ends_at']))
        : false;

    for ($week = 1; $week <= $weeks; $week++) {
        $nextData = $data;
        $nextData['starts_at'] = date('Y-m-d H:i', strtotime('+' . $week . ' weeks', $startTimestamp));
        $nextData['ends_at'] = $endTimestamp !== false
            ? date('Y-m-d H:i', strtotime('+' . $week . ' weeks', $endTimestamp))
            : '';
        $nextData['homework_content'] = '';
        $nextData['material_id'] = 0;
        $nextData['recurrence_group'] = $group;
        $nextData['recurrence_rule'] = 'weekly';
        $nextData['recurrence_source_id'] = $firstId;

        if (findDuplicateCalendarEventId($nextData) === 0) {
            createCalendarEvent($nextData);
        }
    }

    return $firstId;
}

function ensureWeeklyLessonSeries(int $sourceId, int $weeks = 52): int
{
    $source = getCalendarEventById($sourceId);
    if ($source === false || ($source['event_type'] ?? '') !== 'lesson' || (int)($source['class_id'] ?? 0) <= 0) {
        return 0;
    }

    $group = trim((string)($source['recurrence_group'] ?? ''));
    if ($group === '') {
        $group = calendarEventGenerateRecurrenceGroup();
    }

    $db = getDB();
    $db->prepare(
        'UPDATE calendar_events
         SET recurrence_group = :recurrence_group,
             recurrence_rule = "weekly",
             recurrence_source_id = :source_id,
             updated_at = datetime("now")
         WHERE id = :source_id'
    )->execute([
        ':recurrence_group' => $group,
        ':source_id' => $sourceId,
    ]);

    $createdCount = 0;
    $startTimestamp = strtotime(calendarEventNormalizeDateTime((string)$source['starts_at']));
    $endTimestamp = trim((string)($source['ends_at'] ?? '')) !== ''
        ? strtotime(calendarEventNormalizeDateTime((string)$source['ends_at']))
        : false;

    for ($week = 1; $week <= $weeks; $week++) {
        $nextData = [
            'class_id' => (int)$source['class_id'],
            'title' => $source['title'],
            'event_type' => $source['event_type'],
            'description' => $source['description'] ?? '',
            'lesson_number' => (int)($source['lesson_number'] ?? 0),
            'homework_content' => '',
            'material_id' => 0,
            'starts_at' => date('Y-m-d H:i', strtotime('+' . $week . ' weeks', $startTimestamp)),
            'ends_at' => $endTimestamp !== false
                ? date('Y-m-d H:i', strtotime('+' . $week . ' weeks', $endTimestamp))
                : '',
            'recurrence_group' => $group,
            'recurrence_rule' => 'weekly',
            'recurrence_source_id' => $sourceId,
            'is_published' => (int)$source['is_published'],
        ];

        if (findDuplicateCalendarEventId($nextData) === 0) {
            createCalendarEvent($nextData);
            $createdCount++;
        }
    }

    return $createdCount;
}

function updateCalendarEvent(int $id, array $data): void
{
    $db = getDB();
    $params = calendarEventParams($data);
    $params[':id'] = $id;

    $stmt = $db->prepare(
        'UPDATE calendar_events
         SET class_id = :class_id,
             title = :title,
             event_type = :event_type,
             description = :description,
             starts_at = :starts_at,
             ends_at = :ends_at,
             lesson_number = :lesson_number,
             homework_content = :homework_content,
             material_id = :material_id,
             recurrence_group = :recurrence_group,
             recurrence_rule = :recurrence_rule,
             recurrence_source_id = :recurrence_source_id,
             is_published = :is_published,
             updated_at = datetime("now")
         WHERE id = :id'
    );
    $stmt->execute($params);
}

function deleteCalendarEventById(int $id): void
{
    $db = getDB();
    $stmt = $db->prepare('DELETE FROM calendar_events WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

function deleteCalendarEventSeriesFromEvent(array $event): int
{
    $group = trim((string)($event['recurrence_group'] ?? ''));
    if ($group === '') {
        deleteCalendarEventById((int)$event['id']);
        return 1;
    }

    $db = getDB();
    $stmt = $db->prepare(
        'DELETE FROM calendar_events
         WHERE recurrence_group = :recurrence_group
           AND starts_at >= :starts_at'
    );
    $stmt->execute([
        ':recurrence_group' => $group,
        ':starts_at' => $event['starts_at'],
    ]);

    return $stmt->rowCount();
}

function deleteCalendarEventEntireSeriesFromEvent(array $event): int
{
    $group = trim((string)($event['recurrence_group'] ?? ''));
    if ($group === '') {
        deleteCalendarEventById((int)$event['id']);
        return 1;
    }

    $db = getDB();
    $stmt = $db->prepare(
        'DELETE FROM calendar_events
         WHERE recurrence_group = :recurrence_group'
    );
    $stmt->execute([':recurrence_group' => $group]);

    return $stmt->rowCount();
}

function getUpcomingPublicCalendarEvents(int $limit = 5): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT calendar_events.*, classes.title AS class_title, classes.slug AS class_slug,
                materials.title AS material_title
         FROM calendar_events
         LEFT JOIN classes ON classes.id = calendar_events.class_id
         LEFT JOIN materials ON materials.id = calendar_events.material_id
         WHERE calendar_events.is_published = 1
           AND calendar_events.class_id IS NULL
           AND calendar_events.starts_at >= datetime("now")
         ORDER BY calendar_events.starts_at ASC
         LIMIT :limit'
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function getUpcomingPublicLessonCalendarEventsByClassId(int $classId, int $limit = 2): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT calendar_events.*, classes.title AS class_title, classes.slug AS class_slug,
                materials.title AS material_title
         FROM calendar_events
         LEFT JOIN classes ON classes.id = calendar_events.class_id
         LEFT JOIN materials ON materials.id = calendar_events.material_id
         WHERE calendar_events.is_published = 1
           AND calendar_events.class_id = :class_id
           AND calendar_events.event_type = "lesson"
           AND calendar_events.starts_at >= datetime("now")
         ORDER BY calendar_events.starts_at ASC
         LIMIT :limit'
    );
    $stmt->bindValue(':class_id', $classId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function getUpcomingPublicCalendarEventsByClassId(int $classId, int $limit = 5): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT calendar_events.*, classes.title AS class_title, classes.slug AS class_slug,
                materials.title AS material_title
         FROM calendar_events
         LEFT JOIN classes ON classes.id = calendar_events.class_id
         LEFT JOIN materials ON materials.id = calendar_events.material_id
         WHERE calendar_events.is_published = 1
           AND calendar_events.class_id = :class_id
           AND calendar_events.starts_at >= datetime("now")
         ORDER BY calendar_events.starts_at ASC
         LIMIT :limit'
    );
    $stmt->bindValue(':class_id', $classId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function calendarEventParams(array $data): array
{
    $classId = (int)($data['class_id'] ?? 0);

    return [
        ':class_id' => $classId > 0 ? $classId : null,
        ':title' => $data['title'],
        ':event_type' => $data['event_type'],
        ':description' => $data['description'],
        ':starts_at' => calendarEventNormalizeDateTime((string)$data['starts_at']),
        ':ends_at' => trim((string)($data['ends_at'] ?? '')) !== ''
            ? calendarEventNormalizeDateTime((string)$data['ends_at'])
            : null,
        ':lesson_number' => (int)($data['lesson_number'] ?? 0) > 0 ? (int)$data['lesson_number'] : null,
        ':homework_content' => trim((string)($data['homework_content'] ?? '')),
        ':material_id' => (int)($data['material_id'] ?? 0) > 0 ? (int)$data['material_id'] : null,
        ':recurrence_group' => trim((string)($data['recurrence_group'] ?? '')),
        ':recurrence_rule' => trim((string)($data['recurrence_rule'] ?? '')) !== ''
            ? trim((string)$data['recurrence_rule'])
            : 'none',
        ':recurrence_source_id' => (int)($data['recurrence_source_id'] ?? 0) > 0
            ? (int)$data['recurrence_source_id']
            : null,
        ':is_published' => (int)$data['is_published'],
    ];
}

function calendarEventGenerateRecurrenceGroup(): string
{
    return 'weekly_' . bin2hex(random_bytes(8));
}

function calendarWeekStart(string $date): string
{
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        $timestamp = time();
    }

    return date('Y-m-d', strtotime('monday this week', $timestamp));
}

function calendarWeekDays(string $weekStart, int $daysCount = 7): array
{
    $days = [];
    $startTimestamp = strtotime($weekStart);

    for ($index = 0; $index < $daysCount; $index++) {
        $timestamp = strtotime('+' . $index . ' days', $startTimestamp);
        $days[] = [
            'date' => date('Y-m-d', $timestamp),
            'label' => calendarWeekDayLabel((int)date('N', $timestamp)),
            'day' => date('d.m', $timestamp),
        ];
    }

    return $days;
}

function calendarWeekDayLabel(int $dayNumber): string
{
    return [
        1 => 'Пн',
        2 => 'Вт',
        3 => 'Ср',
        4 => 'Чт',
        5 => 'Пт',
        6 => 'Сб',
        7 => 'Вс',
    ][$dayNumber] ?? '';
}

function calendarEventNormalizeDateTime(string $value): string
{
    return str_replace('T', ' ', trim($value));
}

function calendarEventInputDateTime(?string $value): string
{
    $value = trim((string)$value);

    if ($value === '') {
        return '';
    }

    return str_replace(' ', 'T', substr($value, 0, 16));
}
