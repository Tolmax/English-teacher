<?php

function getRecentStudentQuestions(int $limit = 10): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT student_questions.*, classes.title AS class_title
         FROM student_questions
         INNER JOIN classes ON classes.id = student_questions.class_id
         ORDER BY student_questions.created_at DESC, student_questions.id DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function getRecentVisibleStudentQuestions(int $limit = 10): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT student_questions.*, classes.title AS class_title
         FROM student_questions
         INNER JOIN classes ON classes.id = student_questions.class_id
         WHERE lower(student_questions.status) NOT IN ("archive", "archived", "архив")
         ORDER BY student_questions.created_at DESC, student_questions.id DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function createStudentQuestion(array $data): int
{
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO student_questions (class_id, student_name, question, status)
         VALUES (:class_id, :student_name, :question, :status)'
    );
    $stmt->execute([
        ':class_id' => (int)$data['class_id'],
        ':student_name' => $data['student_name'],
        ':question' => $data['question'],
        ':status' => $data['status'] ?? 'new',
    ]);

    return (int)$db->lastInsertId();
}

function countNewStudentQuestions(): int
{
    $db = getDB();

    return (int)$db->query('SELECT COUNT(*) FROM student_questions WHERE status = "new"')->fetchColumn();
}
