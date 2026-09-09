<?php

function getAiKnowledgeSources(array $filters = []): array
{
    $db = getDB();
    $where = ['1 = 1'];
    $params = [];

    $libraryView = $filters['library_view'] ?? 'active';
    if ($libraryView === 'archive') {
        $where[] = 'aks.archived_at IS NOT NULL';
    } elseif ($libraryView !== 'all') {
        $where[] = 'aks.archived_at IS NULL';
    }

    $search = trim((string)($filters['search'] ?? ''));
    if ($search !== '') {
        $where[] = '(aks.title LIKE :search OR aks.description LIKE :search OR aks.tags LIKE :search OR aks.extracted_text LIKE :search OR aks.original_name LIKE :search)';
        $params[':search'] = '%' . $search . '%';
    }

    $sourceType = trim((string)($filters['source_type'] ?? ''));
    if ($sourceType !== '') {
        $where[] = 'aks.source_type = :source_type';
        $params[':source_type'] = $sourceType;
    }

    $status = trim((string)($filters['status'] ?? ''));
    if ($status !== '') {
        $where[] = 'aks.status = :status';
        $params[':status'] = $status;
    }

    $categoryId = (int)($filters['category_id'] ?? 0);
    if ($categoryId > 0) {
        $where[] = 'aks.category_id = :category_id';
        $params[':category_id'] = $categoryId;
    }

    $whereSql = 'WHERE ' . implode(' AND ', $where);
    $stmt = $db->prepare(
        'SELECT aks.*, akc.title AS category_title, akc.slug AS category_slug
         FROM ai_knowledge_sources aks
         LEFT JOIN ai_knowledge_categories akc ON akc.id = aks.category_id
         ' . $whereSql . '
         ORDER BY aks.created_at DESC, aks.id DESC'
    );
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function getAiTestKnowledgeSourceOptions(): array
{
    $stmt = getDB()->query("SELECT id, title FROM ai_knowledge_sources WHERE status = 'active' AND archived_at IS NULL ORDER BY title, id");
    return $stmt->fetchAll();
}

function getActiveAiKnowledgeSources(): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT aks.*, akc.title AS category_title, akc.slug AS category_slug
         FROM ai_knowledge_sources aks
         LEFT JOIN ai_knowledge_categories akc ON akc.id = aks.category_id
         WHERE aks.archived_at IS NULL
           AND aks.status = \'active\'
         ORDER BY aks.title ASC, aks.id ASC'
    );
    $stmt->execute();

    return $stmt->fetchAll();
}

function filterActiveAiKnowledgeSourceIds(array $sourceIds): array
{
    $sourceIds = array_values(array_unique(array_filter(array_map('intval', $sourceIds))));

    if ($sourceIds === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($sourceIds), '?'));
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT id
         FROM ai_knowledge_sources
         WHERE id IN (' . $placeholders . ')
           AND status = \'active\'
           AND archived_at IS NULL
         ORDER BY title ASC, id ASC'
    );
    $stmt->execute($sourceIds);

    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function getAiKnowledgeSourceById(int $id): array|false
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT aks.*, akc.title AS category_title, akc.slug AS category_slug
         FROM ai_knowledge_sources aks
         LEFT JOIN ai_knowledge_categories akc ON akc.id = aks.category_id
         WHERE aks.id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => $id]);

    return $stmt->fetch();
}

function createAiKnowledgeSource(array $data): int
{
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO ai_knowledge_sources
            (category_id, title, source_type, original_name, stored_name, storage_path, mime_type,
             file_size, extracted_text, description, tags, status, openai_file_id,
             vector_store_id, created_by)
         VALUES
            (:category_id, :title, :source_type, :original_name, :stored_name, :storage_path, :mime_type,
             :file_size, :extracted_text, :description, :tags, :status, :openai_file_id,
             :vector_store_id, :created_by)'
    );
    $stmt->execute(aiKnowledgeSourceParams($data));

    return (int)$db->lastInsertId();
}

function updateAiKnowledgeSource(int $id, array $data): void
{
    $db = getDB();
    $params = aiKnowledgeSourceParams($data);
    unset(
        $params[':original_name'],
        $params[':stored_name'],
        $params[':storage_path'],
        $params[':mime_type'],
        $params[':file_size'],
        $params[':openai_file_id'],
        $params[':vector_store_id'],
        $params[':created_by']
    );
    $params[':id'] = $id;
    $params[':archive_status'] = $data['status'] ?? 'active';

    $stmt = $db->prepare(
        'UPDATE ai_knowledge_sources
         SET title = :title,
             category_id = :category_id,
             source_type = :source_type,
             extracted_text = :extracted_text,
             description = :description,
             tags = :tags,
             status = :status,
             archived_at = CASE
                WHEN :archive_status = \'archived\' THEN COALESCE(archived_at, datetime(\'now\'))
                 ELSE NULL
             END,
             updated_at = datetime("now")
         WHERE id = :id'
    );
    $stmt->execute($params);
}

function archiveAiKnowledgeSource(int $id): void
{
    $db = getDB();
    $stmt = $db->prepare(
        'UPDATE ai_knowledge_sources
         SET status = \'archived\',
             archived_at = datetime(\'now\'),
             updated_at = datetime("now")
         WHERE id = :id'
    );
    $stmt->execute([':id' => $id]);
}

function restoreAiKnowledgeSource(int $id): void
{
    $db = getDB();
    $stmt = $db->prepare(
        'UPDATE ai_knowledge_sources
         SET status = \'active\',
             archived_at = NULL,
             updated_at = datetime("now")
         WHERE id = :id'
    );
    $stmt->execute([':id' => $id]);
}

function deleteAiKnowledgeSource(int $id): void
{
    $db = getDB();
    $db->beginTransaction();

    try {
        $stmt = $db->prepare('DELETE FROM ai_material_knowledge_sources WHERE knowledge_source_id = :source_id');
        $stmt->execute([':source_id' => $id]);

        $stmt = $db->prepare('DELETE FROM ai_knowledge_sources WHERE id = :id');
        $stmt->execute([':id' => $id]);

        $db->commit();
    } catch (Throwable $exception) {
        $db->rollBack();
        throw $exception;
    }
}

function aiKnowledgeSourceTypeLabel(string $type): string
{
    return [
        'text' => 'Текст',
        'file' => 'Файл',
    ][$type] ?? $type;
}

function aiKnowledgeSourceStatusLabel(string $status): string
{
    return [
        'active' => 'Активен',
        'draft' => 'Черновик',
        'archived' => 'В архиве',
    ][$status] ?? $status;
}

function aiKnowledgeSourceParams(array $data): array
{
    $createdBy = (int)($data['created_by'] ?? 0);

    return [
        ':category_id' => (int)($data['category_id'] ?? 0) > 0 ? (int)$data['category_id'] : null,
        ':title' => $data['title'] ?? '',
        ':source_type' => $data['source_type'] ?? 'text',
        ':original_name' => $data['original_name'] ?? '',
        ':stored_name' => $data['stored_name'] ?? '',
        ':storage_path' => $data['storage_path'] ?? '',
        ':mime_type' => $data['mime_type'] ?? '',
        ':file_size' => (int)($data['file_size'] ?? 0),
        ':extracted_text' => $data['extracted_text'] ?? '',
        ':description' => $data['description'] ?? '',
        ':tags' => $data['tags'] ?? '',
        ':status' => $data['status'] ?? 'active',
        ':openai_file_id' => $data['openai_file_id'] ?? '',
        ':vector_store_id' => $data['vector_store_id'] ?? '',
        ':created_by' => $createdBy > 0 ? $createdBy : null,
    ];
}
