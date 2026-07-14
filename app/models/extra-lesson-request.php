<?php

function getExtraLessonRequests(int $limit = 100): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT *
         FROM extra_lesson_requests
         ORDER BY created_at DESC, id DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function getExtraLessonRequestsByStatus(string $status, int $limit = 100): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT *
         FROM extra_lesson_requests
         WHERE status = :status
         ORDER BY created_at DESC, id DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':status', $status);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function getVisibleExtraLessonRequests(int $limit = 10): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT *
         FROM extra_lesson_requests
         WHERE lower(status) NOT IN ("archive", "archived", "архив")
         ORDER BY created_at DESC,
                  CASE WHEN status = "new" THEN 0 ELSE 1 END,
                  id DESC'
    );
    $stmt->execute();

    $uniqueRequests = [];
    $seen = [];

    foreach ($stmt->fetchAll() as $request) {
        $key = implode('|', [
            normalizeExtraLessonDuplicateKey((string)($request['student_name'] ?? '')),
            normalizeExtraLessonDuplicateKey((string)($request['student_contact'] ?? '')),
            normalizeExtraLessonDuplicateKey((string)($request['class_title'] ?? '')),
            normalizeExtraLessonDuplicateKey((string)($request['lesson_format'] ?? '')),
            normalizeExtraLessonDuplicateKey((string)($request['topic'] ?? '')),
            normalizeExtraLessonDuplicateKey((string)($request['message'] ?? '')),
            (string)($request['created_at'] ?? ''),
        ]);

        if (isset($seen[$key])) {
            continue;
        }

        $seen[$key] = true;
        $uniqueRequests[] = $request;

        if (count($uniqueRequests) >= $limit) {
            break;
        }
    }

    return $uniqueRequests;
}

function getExtraLessonRequestById(int $id): array|false
{
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM extra_lesson_requests WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    return $stmt->fetch();
}

function createExtraLessonRequest(array $data): int
{
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO extra_lesson_requests (student_name, student_contact, class_title, lesson_format, topic, message)
         VALUES (:student_name, :student_contact, :class_title, :lesson_format, :topic, :message)'
    );
    $stmt->execute([
        ':student_name' => $data['student_name'],
        ':student_contact' => $data['student_contact'],
        ':class_title' => $data['class_title'],
        ':lesson_format' => $data['lesson_format'],
        ':topic' => $data['topic'],
        ':message' => $data['message'],
    ]);

    return (int)$db->lastInsertId();
}

function updateExtraLessonRequestStatus(int $id, string $status): void
{
    $db = getDB();
    $processedAtSql = $status === 'processed' ? 'datetime("now")' : 'NULL';
    $stmt = $db->prepare(
        'UPDATE extra_lesson_requests
         SET status = :status,
             processed_at = ' . $processedAtSql . '
         WHERE id = :id'
    );
    $stmt->execute([
        ':id' => $id,
        ':status' => $status,
    ]);
}

function countExtraLessonRequests(): int
{
    $db = getDB();

    return (int)$db->query('SELECT COUNT(*) FROM extra_lesson_requests')->fetchColumn();
}

function countNewExtraLessonRequests(): int
{
    $db = getDB();

    return (int)$db->query('SELECT COUNT(*) FROM extra_lesson_requests WHERE status = "new"')->fetchColumn();
}

function normalizeExtraLessonDuplicateKey(string $value): string
{
    $value = trim($value);
    $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

    return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
}
