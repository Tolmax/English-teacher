<?php

function getRecentSubmissions(int $limit = 10): array
{
    return getSubmissionsByReviewStatus('all', $limit);
}

function getSubmissionsByReviewStatus(string $status, int $limit = 100): array
{
    $db = getDB();
    $where = '';

    if ($status === 'processed') {
        $where = 'WHERE ai_test_submissions.reviewed_at IS NOT NULL';
    } elseif ($status === 'new') {
        $where = 'WHERE ai_test_submissions.reviewed_at IS NULL';
    }

    $stmt = $db->prepare(
        'SELECT ai_test_submissions.id,
                ai_test_submissions.student_name,
                ai_test_submissions.score,
                ai_test_submissions.total_questions,
                ai_test_submissions.answers_json,
                ai_test_submissions.submitted_at,
                ai_test_submissions.reviewed_at,
                ai_interactive_tests.title AS task_title,
                ai_interactive_tests.class_title AS class_title
         FROM ai_test_submissions
         INNER JOIN ai_interactive_tests ON ai_interactive_tests.id = ai_test_submissions.test_id
         ' . $where . '
         ORDER BY ai_test_submissions.submitted_at DESC, ai_test_submissions.id DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function getSubmissionById(int $id): array|false
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT ai_test_submissions.id,
                ai_test_submissions.student_name,
                ai_test_submissions.score,
                ai_test_submissions.total_questions,
                ai_test_submissions.answers_json,
                ai_test_submissions.submitted_at,
                ai_test_submissions.reviewed_at,
                ai_interactive_tests.title AS task_title,
                ai_interactive_tests.class_title AS class_title
         FROM ai_test_submissions
         INNER JOIN ai_interactive_tests ON ai_interactive_tests.id = ai_test_submissions.test_id
         WHERE ai_test_submissions.id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => $id]);

    return $stmt->fetch();
}

function createSubmission(array $data): int
{
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO submissions (task_id, class_id, student_name, student_contact, score, answers_json)
         VALUES (:task_id, :class_id, :student_name, :student_contact, :score, :answers_json)'
    );
    $stmt->execute([
        ':task_id' => (int)$data['task_id'],
        ':class_id' => (int)$data['class_id'],
        ':student_name' => $data['student_name'],
        ':student_contact' => $data['student_contact'] ?? '',
        ':score' => (int)$data['score'],
        ':answers_json' => $data['answers_json'] ?? '[]',
    ]);

    return (int)$db->lastInsertId();
}

function markSubmissionReviewed(int $id): void
{
    updateSubmissionReviewStatus($id, 'processed');
}

function updateSubmissionReviewStatus(int $id, string $status): void
{
    $db = getDB();
    $reviewedAtSql = $status === 'processed' ? 'datetime("now")' : 'NULL';
    $stmt = $db->prepare(
        'UPDATE ai_test_submissions
         SET reviewed_at = ' . $reviewedAtSql . '
         WHERE id = :id'
    );
    $stmt->execute([':id' => $id]);
}

function countSubmissions(): int
{
    $db = getDB();

    return (int)$db->query('SELECT COUNT(*) FROM ai_test_submissions')->fetchColumn();
}

function countNewSubmissions(): int
{
    $db = getDB();

    return (int)$db->query('SELECT COUNT(*) FROM ai_test_submissions WHERE reviewed_at IS NULL')->fetchColumn();
}
