<?php

require_once ROOT . 'app/services/ai-test-templates.php';

function getAiInteractiveTestsForAdmin(): array
{
    $db = getDB();

    return $db->query(
        'SELECT ai_interactive_tests.*,
                ai_teaching_materials.title AS material_title,
                COUNT(DISTINCT ai_test_questions.id) AS questions_count,
                COUNT(DISTINCT ai_test_submissions.id) AS submissions_count
         FROM ai_interactive_tests
         INNER JOIN ai_teaching_materials ON ai_teaching_materials.id = ai_interactive_tests.ai_material_id
         LEFT JOIN ai_test_questions ON ai_test_questions.test_id = ai_interactive_tests.id
         LEFT JOIN ai_test_submissions ON ai_test_submissions.test_id = ai_interactive_tests.id
         GROUP BY ai_interactive_tests.id
         ORDER BY ai_interactive_tests.updated_at DESC, ai_interactive_tests.id DESC'
    )->fetchAll();
}

function getAiInteractiveTestById(int $id): array|false
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT ai_interactive_tests.*,
                ai_teaching_materials.title AS material_title,
                ai_teaching_materials.topic AS material_topic
         FROM ai_interactive_tests
         INNER JOIN ai_teaching_materials ON ai_teaching_materials.id = ai_interactive_tests.ai_material_id
         WHERE ai_interactive_tests.id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => $id]);

    return $stmt->fetch();
}

function getAiInteractiveTestByMaterialId(int $materialId): array|false
{
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM ai_interactive_tests WHERE ai_material_id = :material_id LIMIT 1');
    $stmt->execute([':material_id' => $materialId]);

    return $stmt->fetch();
}

function getPublishedAiInteractiveTestBySlug(string $slug): array|false
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT ai_interactive_tests.*,
                ai_teaching_materials.topic AS material_topic
         FROM ai_interactive_tests
         INNER JOIN ai_teaching_materials ON ai_teaching_materials.id = ai_interactive_tests.ai_material_id
         WHERE ai_interactive_tests.slug = :slug
           AND ai_interactive_tests.status = "published"
         LIMIT 1'
    );
    $stmt->execute([':slug' => $slug]);

    return $stmt->fetch();
}

function getPublishedAiInteractiveTestsByClassTitle(string $classTitle): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT ai_interactive_tests.*,
                COUNT(ai_test_questions.id) AS questions_count
         FROM ai_interactive_tests
         LEFT JOIN ai_test_questions ON ai_test_questions.test_id = ai_interactive_tests.id
         WHERE ai_interactive_tests.class_title = :class_title
           AND ai_interactive_tests.status = "published"
         GROUP BY ai_interactive_tests.id
         ORDER BY ai_interactive_tests.published_at DESC, ai_interactive_tests.id DESC'
    );
    $stmt->execute([':class_title' => $classTitle]);

    return $stmt->fetchAll();
}

function createAiInteractiveTestFromMaterial(array $material): int
{
    $db = getDB();
    $title = 'Тест';
    $stmt = $db->prepare(
        'INSERT INTO ai_interactive_tests (ai_material_id, class_title, template_key, title, slug, description, status)
         VALUES (:ai_material_id, :class_title, :template_key, :title, :slug, :description, "draft")'
    );
    $stmt->execute([
        ':ai_material_id' => (int)$material['id'],
        ':class_title' => (string)$material['class_title'],
        ':template_key' => normalizeAiTestTemplateKey($material['test_template'] ?? null),
        ':title' => $title,
        ':slug' => createUniqueAiInteractiveTestSlug($title),
        ':description' => (string)($material['topic'] ?? ''),
    ]);

    return (int)$db->lastInsertId();
}

function updateAiInteractiveTest(int $id, array $data): void
{
    $db = getDB();
    $stmt = $db->prepare(
        'UPDATE ai_interactive_tests
         SET title = :title,
             class_title = :class_title,
             template_key = COALESCE(:template_key, template_key),
             description = :description,
             updated_at = datetime("now")
         WHERE id = :id'
    );
    $stmt->execute([
        ':title' => $data['title'],
        ':class_title' => $data['class_title'],
        ':template_key' => array_key_exists('template_key', $data) ? normalizeAiTestTemplateKey($data['template_key']) : null,
        ':description' => $data['description'],
        ':id' => $id,
    ]);
}

function publishAiInteractiveTest(int $id): void
{
    $db = getDB();
    $stmt = $db->prepare(
        'UPDATE ai_interactive_tests
         SET status = "published",
             published_at = COALESCE(published_at, datetime("now")),
             updated_at = datetime("now")
         WHERE id = :id'
    );
    $stmt->execute([':id' => $id]);
}

function unpublishAiInteractiveTest(int $id): void
{
    $db = getDB();
    $stmt = $db->prepare(
        'UPDATE ai_interactive_tests
         SET status = "draft",
             updated_at = datetime("now")
         WHERE id = :id'
    );
    $stmt->execute([':id' => $id]);
}

function deleteAiInteractiveTest(int $id): void
{
    $db = getDB();
    $test = getAiInteractiveTestById($id);

    if ($test === false) {
        return;
    }

    $materialId = (int)$test['ai_material_id'];

    $db->beginTransaction();

    try {
        $stmt = $db->prepare(
            'DELETE FROM ai_test_options
             WHERE question_id IN (
                SELECT id FROM ai_test_questions WHERE test_id = :test_id
             )'
        );
        $stmt->execute([':test_id' => $id]);

        $stmt = $db->prepare('DELETE FROM ai_test_questions WHERE test_id = :test_id');
        $stmt->execute([':test_id' => $id]);

        $stmt = $db->prepare('DELETE FROM ai_test_submissions WHERE test_id = :test_id');
        $stmt->execute([':test_id' => $id]);

        $stmt = $db->prepare('DELETE FROM ai_interactive_tests WHERE id = :id');
        $stmt->execute([':id' => $id]);

        deleteUnusedDraftAiTestMaterial($db, $materialId);

        $db->commit();
    } catch (Throwable $exception) {
        $db->rollBack();
        throw $exception;
    }
}

function deleteUnusedDraftAiTestMaterial(PDO $db, int $materialId): void
{
    if ($materialId <= 0) {
        return;
    }

    $stmt = $db->prepare('SELECT COUNT(*) FROM ai_interactive_tests WHERE ai_material_id = :material_id');
    $stmt->execute([':material_id' => $materialId]);

    if ((int)$stmt->fetchColumn() > 0) {
        return;
    }

    $stmt = $db->prepare(
        'SELECT status, material_type
         FROM ai_teaching_materials
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => $materialId]);
    $material = $stmt->fetch();

    if ($material === false || (string)$material['status'] === 'published') {
        return;
    }

    if ((string)$material['material_type'] !== 'quiz') {
        return;
    }

    $stmt = $db->prepare('DELETE FROM ai_material_knowledge_sources WHERE material_id = :material_id');
    $stmt->execute([':material_id' => $materialId]);

    $stmt = $db->prepare('DELETE FROM ai_material_files WHERE material_id = :material_id');
    $stmt->execute([':material_id' => $materialId]);

    $stmt = $db->prepare('DELETE FROM ai_teaching_materials WHERE id = :id');
    $stmt->execute([':id' => $materialId]);
}

function getAiInteractiveTestQuestions(int $testId): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT * FROM ai_test_questions
         WHERE test_id = :test_id
         ORDER BY sort_order ASC, id ASC'
    );
    $stmt->execute([':test_id' => $testId]);
    $questions = $stmt->fetchAll();

    if (empty($questions)) {
        return [];
    }

    $questionIds = array_map(static fn(array $question): int => (int)$question['id'], $questions);
    $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
    $optionsStmt = $db->prepare(
        'SELECT * FROM ai_test_options
         WHERE question_id IN (' . $placeholders . ')
         ORDER BY sort_order ASC, id ASC'
    );
    $optionsStmt->execute($questionIds);

    $optionsByQuestion = [];
    foreach ($optionsStmt->fetchAll() as $option) {
        $optionsByQuestion[(int)$option['question_id']][] = $option;
    }

    foreach ($questions as &$question) {
        $question['options'] = $optionsByQuestion[(int)$question['id']] ?? [];
    }
    unset($question);

    return $questions;
}

function replaceAiInteractiveTestQuestions(int $testId, array $questions): void
{
    $db = getDB();
    $db->beginTransaction();

    try {
        $deleteStmt = $db->prepare('DELETE FROM ai_test_questions WHERE test_id = :test_id');
        $deleteStmt->execute([':test_id' => $testId]);

        $questionStmt = $db->prepare(
            'INSERT INTO ai_test_questions (test_id, question_text, question_type, sort_order)
             VALUES (:test_id, :question_text, "single_choice", :sort_order)'
        );
        $optionStmt = $db->prepare(
            'INSERT INTO ai_test_options (question_id, option_text, is_correct, sort_order)
             VALUES (:question_id, :option_text, :is_correct, :sort_order)'
        );

        foreach (array_values($questions) as $questionIndex => $question) {
            $questionStmt->execute([
                ':test_id' => $testId,
                ':question_text' => $question['question_text'],
                ':sort_order' => $questionIndex + 1,
            ]);
            $questionId = (int)$db->lastInsertId();

            foreach (array_values($question['options']) as $optionIndex => $optionText) {
                $optionStmt->execute([
                    ':question_id' => $questionId,
                    ':option_text' => $optionText,
                    ':is_correct' => $optionIndex === (int)$question['correct_option'] ? 1 : 0,
                    ':sort_order' => $optionIndex + 1,
                ]);
            }
        }

        $touchStmt = $db->prepare('UPDATE ai_interactive_tests SET updated_at = datetime("now") WHERE id = :id');
        $touchStmt->execute([':id' => $testId]);
        $db->commit();
    } catch (Throwable $exception) {
        $db->rollBack();
        throw $exception;
    }
}

function createAiTestSubmission(int $testId, string $studentName, int $score, int $totalQuestions, array $answers): int
{
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO ai_test_submissions (test_id, student_name, score, total_questions, answers_json)
         VALUES (:test_id, :student_name, :score, :total_questions, :answers_json)'
    );
    $stmt->execute([
        ':test_id' => $testId,
        ':student_name' => $studentName,
        ':score' => $score,
        ':total_questions' => $totalQuestions,
        ':answers_json' => json_encode($answers, JSON_UNESCAPED_UNICODE),
    ]);

    return (int)$db->lastInsertId();
}

function getAiTestSubmissionsByTestId(int $testId): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT * FROM ai_test_submissions
         WHERE test_id = :test_id
         ORDER BY submitted_at DESC, id DESC'
    );
    $stmt->execute([':test_id' => $testId]);

    return $stmt->fetchAll();
}

function scoreAiInteractiveTest(array $questions, array $answers): array
{
    $score = 0;
    $details = [];

    foreach ($questions as $question) {
        $questionId = (int)$question['id'];
        $selectedOptionId = (int)($answers[$questionId] ?? 0);
        $correctOptionId = 0;
        $selectedText = '';
        $correctText = '';

        foreach ($question['options'] as $option) {
            if ((int)$option['is_correct'] === 1) {
                $correctOptionId = (int)$option['id'];
                $correctText = (string)$option['option_text'];
            }
            if ((int)$option['id'] === $selectedOptionId) {
                $selectedText = (string)$option['option_text'];
            }
        }

        $isCorrect = $selectedOptionId > 0 && $selectedOptionId === $correctOptionId;
        if ($isCorrect) {
            $score++;
        }

        $details[] = [
            'question_id' => $questionId,
            'question' => (string)$question['question_text'],
            'selected_option_id' => $selectedOptionId,
            'selected_option' => $selectedText,
            'correct_option_id' => $correctOptionId,
            'correct_option' => $correctText,
            'is_correct' => $isCorrect,
        ];
    }

    return [
        'score' => $score,
        'total' => count($questions),
        'details' => $details,
    ];
}

function aiInteractiveTestStatusLabel(string $status): string
{
    return match ($status) {
        'published' => 'Опубликован',
        default => 'Черновик',
    };
}

function createUniqueAiInteractiveTestSlug(string $title): string
{
    $db = getDB();
    $baseSlug = slugifyAiInteractiveTestTitle($title);
    $slug = $baseSlug;
    $counter = 2;

    while (true) {
        $stmt = $db->prepare('SELECT COUNT(*) FROM ai_interactive_tests WHERE slug = :slug');
        $stmt->execute([':slug' => $slug]);

        if ((int)$stmt->fetchColumn() === 0) {
            return $slug;
        }

        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }
}

function slugifyAiInteractiveTestTitle(string $title): string
{
    $slug = function_exists('mb_strtolower') ? mb_strtolower($title) : strtolower($title);
    $slug = trim($slug);
    $slug = preg_replace('/[^a-z0-9а-яё]+/u', '-', $slug) ?? '';
    $slug = trim($slug, '-');

    return $slug !== '' ? $slug : 'ai-test';
}
