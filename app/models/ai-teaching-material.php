<?php

require_once ROOT . 'app/services/ai-test-templates.php';

function getAiTeachingMaterials(array $filters = []): array
{
    $db = getDB();
    $where = [];
    $params = [];

    $libraryView = $filters['library_view'] ?? 'active';

    if ($libraryView === 'archive') {
        $where[] = 'archived_at IS NOT NULL';
    } elseif ($libraryView !== 'all') {
        $where[] = 'archived_at IS NULL';
    }

    $search = trim((string)($filters['search'] ?? ''));
    if ($search !== '') {
        $where[] = '(title LIKE :search OR topic LIKE :search OR tags LIKE :search OR edited_content LIKE :search OR generated_content LIKE :search)';
        $params[':search'] = '%' . $search . '%';
    }

    $classTitle = trim((string)($filters['class_title'] ?? ''));
    if ($classTitle !== '') {
        $where[] = 'class_title = :class_title';
        $params[':class_title'] = $classTitle;
    }

    $materialType = trim((string)($filters['material_type'] ?? ''));
    if ($materialType !== '') {
        $where[] = 'material_type = :material_type';
        $params[':material_type'] = $materialType;
    }

    $status = trim((string)($filters['status'] ?? ''));
    if ($status !== '') {
        $where[] = 'status = :status';
        $params[':status'] = $status;
    }

    $schoolYear = trim((string)($filters['school_year'] ?? ''));
    if ($schoolYear !== '') {
        $where[] = 'school_year = :school_year';
        $params[':school_year'] = $schoolYear;
    }

    $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

    $stmt = $db->prepare(
        'SELECT *
         FROM ai_teaching_materials
         ' . $whereSql . '
         ORDER BY created_at DESC, id DESC'
    );
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function getAiTeachingMaterialSchoolYears(): array
{
    $db = getDB();

    return $db->query(
        'SELECT DISTINCT school_year
         FROM ai_teaching_materials
         WHERE school_year != ""
         ORDER BY school_year DESC'
    )->fetchAll(PDO::FETCH_COLUMN);
}

function getAiTeachingMaterialById(int $id): array|false
{
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM ai_teaching_materials WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    return $stmt->fetch();
}

function getPublishedAiTeachingMaterials(): array
{
    $db = getDB();

    $stmt = $db->prepare(
        'SELECT *
         FROM ai_teaching_materials
         WHERE status = "published"
           AND published_at IS NOT NULL
           AND published_at != ""
           AND edited_content != ""
           AND archived_at IS NULL
         ORDER BY published_at DESC, id DESC'
    );
    $stmt->execute();

    return $stmt->fetchAll();
}

function getPublishedAiTeachingMaterialsByClassTitle(string $classTitle): array
{
    $db = getDB();

    $stmt = $db->prepare(
        'SELECT *
         FROM ai_teaching_materials
         WHERE class_title = :class_title
           AND status = "published"
           AND published_at IS NOT NULL
           AND published_at != ""
           AND edited_content != ""
           AND archived_at IS NULL
         ORDER BY published_at DESC, id DESC'
    );
    $stmt->execute([':class_title' => $classTitle]);

    return $stmt->fetchAll();
}

function getPublishedAiTeachingMaterialBySlug(string $slug): array|false
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT *
         FROM ai_teaching_materials
         WHERE slug = :slug
           AND status = "published"
           AND published_at IS NOT NULL
           AND published_at != ""
           AND edited_content != ""
           AND archived_at IS NULL
         LIMIT 1'
    );
    $stmt->execute([':slug' => $slug]);

    return $stmt->fetch();
}

function createAiTeachingMaterial(array $data): int
{
    $db = getDB();
    $slug = createUniqueAiTeachingMaterialSlug($data['title']);

    $stmt = $db->prepare(
        'INSERT INTO ai_teaching_materials
            (title, slug, subject, class_title, topic, material_type, difficulty,
             test_template, estimated_duration, language, instructions, source_notes, edited_content,
             status, school_year, period, tags, source_material_id, created_by)
         VALUES
            (:title, :slug, :subject, :class_title, :topic, :material_type, :difficulty,
             :test_template, :estimated_duration, :language, :instructions, :source_notes, :edited_content,
             :status, :school_year, :period, :tags, :source_material_id, :created_by)'
    );
    $stmt->execute(aiTeachingMaterialParams($data, $slug));

    return (int)$db->lastInsertId();
}

function duplicateAiTeachingMaterial(array $sourceMaterial, array $data): int
{
    $db = getDB();
    $slug = createUniqueAiTeachingMaterialSlug($data['title']);

    $stmt = $db->prepare(
        'INSERT INTO ai_teaching_materials
            (title, slug, subject, class_title, topic, material_type, difficulty,
             test_template, estimated_duration, language, instructions, source_notes, generated_content,
             edited_content, status, generation_error, openai_response_id, model_used,
             prompt_version, school_year, period, tags, archived_at, source_material_id,
             published_at, created_by)
         VALUES
            (:title, :slug, :subject, :class_title, :topic, :material_type, :difficulty,
             :test_template, :estimated_duration, :language, :instructions, :source_notes, :generated_content,
             :edited_content, :status, "", :openai_response_id, :model_used,
             :prompt_version, :school_year, :period, :tags, NULL, :source_material_id,
             NULL, :created_by)'
    );
    $stmt->execute([
        ':title' => $data['title'],
        ':slug' => $slug,
        ':subject' => $sourceMaterial['subject'],
        ':class_title' => $data['class_title'],
        ':topic' => $data['topic'],
        ':material_type' => $sourceMaterial['material_type'],
        ':difficulty' => '',
        ':test_template' => normalizeAiTestTemplateKey($data['test_template'] ?? ($sourceMaterial['test_template'] ?? null)),
        ':estimated_duration' => $sourceMaterial['estimated_duration'] ?? '',
        ':language' => $sourceMaterial['language'],
        ':instructions' => $sourceMaterial['instructions'] ?? '',
        ':source_notes' => $sourceMaterial['source_notes'] ?? '',
        ':generated_content' => $sourceMaterial['generated_content'] ?? '',
        ':edited_content' => $sourceMaterial['edited_content'] ?? '',
        ':status' => trim((string)($sourceMaterial['edited_content'] ?? '')) !== '' ? 'ready' : 'draft',
        ':openai_response_id' => $sourceMaterial['openai_response_id'] ?? '',
        ':model_used' => $sourceMaterial['model_used'] ?? '',
        ':prompt_version' => $sourceMaterial['prompt_version'] ?? 'v1',
        ':school_year' => $data['school_year'] ?? '',
        ':period' => $data['period'] ?? '',
        ':tags' => $data['tags'] ?? '',
        ':source_material_id' => (int)$sourceMaterial['id'],
        ':created_by' => !empty($data['created_by']) ? (int)$data['created_by'] : null,
    ]);

    return (int)$db->lastInsertId();
}

function updateAiTeachingMaterial(int $id, array $data): void
{
    $db = getDB();
    $params = aiTeachingMaterialParams($data);
    unset($params[':created_by']);
    $params[':id'] = $id;

    $stmt = $db->prepare(
        'UPDATE ai_teaching_materials
         SET title = :title,
             subject = :subject,
             class_title = :class_title,
             topic = :topic,
             material_type = :material_type,
             difficulty = :difficulty,
             test_template = :test_template,
             estimated_duration = :estimated_duration,
             language = :language,
             instructions = :instructions,
             source_notes = :source_notes,
             edited_content = :edited_content,
             status = :status,
             school_year = :school_year,
             period = :period,
             tags = :tags,
             source_material_id = :source_material_id,
             updated_at = datetime("now")
         WHERE id = :id'
    );
    $stmt->execute($params);
}

function updateAiTeachingMaterialGenerationSuccess(int $id, array $data): void
{
    $db = getDB();
    $stmt = $db->prepare(
        'UPDATE ai_teaching_materials
         SET generated_content = :generated_content,
             edited_content = :edited_content,
             generation_error = "",
             model_used = :model_used,
             openai_response_id = :openai_response_id,
             status = "ready",
             updated_at = datetime("now")
         WHERE id = :id'
    );
    $stmt->execute([
        ':id' => $id,
        ':generated_content' => $data['content'],
        ':edited_content' => $data['content'],
        ':model_used' => $data['model'],
        ':openai_response_id' => $data['response_id'],
    ]);
}

function updateAiTeachingMaterialGenerationError(int $id, string $message): void
{
    $db = getDB();
    $stmt = $db->prepare(
        'UPDATE ai_teaching_materials
         SET generation_error = :generation_error,
             status = "error",
             updated_at = datetime("now")
         WHERE id = :id'
    );
    $stmt->execute([
        ':id' => $id,
        ':generation_error' => $message,
    ]);
}

function publishAiTeachingMaterial(int $id): void
{
    $db = getDB();
    $stmt = $db->prepare(
        'UPDATE ai_teaching_materials
         SET status = "published",
             published_at = datetime("now"),
             archived_at = NULL,
             updated_at = datetime("now")
         WHERE id = :id'
    );
    $stmt->execute([':id' => $id]);
}

function unpublishAiTeachingMaterial(int $id): void
{
    $db = getDB();
    $stmt = $db->prepare(
        'UPDATE ai_teaching_materials
         SET status = "ready",
             published_at = NULL,
             updated_at = datetime("now")
         WHERE id = :id'
    );
    $stmt->execute([':id' => $id]);
}

function archiveAiTeachingMaterial(int $id): void
{
    $db = getDB();
    $stmt = $db->prepare(
        'UPDATE ai_teaching_materials
         SET status = "archived",
             archived_at = datetime("now"),
             published_at = NULL,
             updated_at = datetime("now")
         WHERE id = :id'
    );
    $stmt->execute([':id' => $id]);
}

function restoreAiTeachingMaterial(int $id): void
{
    $db = getDB();
    $stmt = $db->prepare(
        'UPDATE ai_teaching_materials
         SET status = CASE
                 WHEN edited_content != "" THEN "ready"
                 ELSE "draft"
             END,
             archived_at = NULL,
             updated_at = datetime("now")
         WHERE id = :id'
    );
    $stmt->execute([':id' => $id]);
}

function aiTeachingMaterialStatusLabel(string $status): string
{
    return [
        'draft' => 'Черновик',
        'ready' => 'Сгенерирован',
        'published' => 'Опубликован',
        'archived' => 'В архиве',
        'error' => 'Ошибка',
    ][$status] ?? $status;
}

function aiTeachingMaterialSlugExists(string $slug): bool
{
    $db = getDB();
    $stmt = $db->prepare('SELECT COUNT(*) FROM ai_teaching_materials WHERE slug = :slug');
    $stmt->execute([':slug' => $slug]);

    return (int)$stmt->fetchColumn() > 0;
}

function createUniqueAiTeachingMaterialSlug(string $title): string
{
    $baseSlug = slugifyAiTeachingMaterialTitle($title);
    $slug = $baseSlug;
    $counter = 2;

    while (aiTeachingMaterialSlugExists($slug)) {
        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }

    return $slug;
}

function slugifyAiTeachingMaterialTitle(string $title): string
{
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9а-яё]+/iu', '-', $slug) ?? '';
    $slug = trim($slug, '-');

    if ($slug === '') {
        return 'ai-material-' . date('YmdHis');
    }

    return $slug;
}

function aiTeachingMaterialTypeLabel(string $type): string
{
    return [
        'worksheet' => 'Рабочий лист',
        'quiz' => 'Мини-тест',
        'lesson_plan' => 'План урока',
        'homework' => 'Домашнее задание',
        'explanation' => 'Объяснение',
    ][$type] ?? 'Материал';
}

function aiTeachingMaterialParams(array $data, ?string $slug = null): array
{
    $createdBy = (int)($data['created_by'] ?? 0);

    $params = [
        ':title' => $data['title'],
        ':subject' => $data['subject'],
        ':class_title' => $data['class_title'],
        ':topic' => $data['topic'],
        ':material_type' => $data['material_type'],
        ':difficulty' => $data['difficulty'],
        ':test_template' => normalizeAiTestTemplateKey($data['test_template'] ?? null),
        ':estimated_duration' => $data['estimated_duration'],
        ':language' => $data['language'],
        ':instructions' => $data['instructions'],
        ':source_notes' => $data['source_notes'],
        ':edited_content' => $data['edited_content'],
        ':status' => $data['status'],
        ':school_year' => $data['school_year'] ?? '',
        ':period' => $data['period'] ?? '',
        ':tags' => $data['tags'] ?? '',
        ':source_material_id' => !empty($data['source_material_id']) ? (int)$data['source_material_id'] : null,
        ':created_by' => $createdBy > 0 ? $createdBy : null,
    ];

    if ($slug !== null) {
        $params[':slug'] = $slug;
    }

    return $params;
}
