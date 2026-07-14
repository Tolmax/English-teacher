<?php

function getAllLearningClasses(bool $activeOnly = false): array
{
    $db = getDB();
    $sql = 'SELECT * FROM classes';

    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }

    $sql .= ' ORDER BY CAST(title AS INTEGER) ASC, title ASC';

    return $db->query($sql)->fetchAll();
}

function getLearningClassById(int $id): array|false
{
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM classes WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    return $stmt->fetch();
}

function getLearningClassBySlug(string $slug): array|false
{
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM classes WHERE slug = :slug AND is_active = 1 LIMIT 1');
    $stmt->execute([':slug' => $slug]);

    return $stmt->fetch();
}

function createLearningClass(array $data): int
{
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO classes (title, slug, description, is_active)
         VALUES (:title, :slug, :description, :is_active)'
    );
    $stmt->execute([
        ':title' => $data['title'],
        ':slug' => $data['slug'],
        ':description' => $data['description'],
        ':is_active' => (int)$data['is_active'],
    ]);

    return (int)$db->lastInsertId();
}

function updateLearningClass(int $id, array $data): void
{
    $db = getDB();
    $stmt = $db->prepare(
        'UPDATE classes
         SET title = :title,
             slug = :slug,
             description = :description,
             is_active = :is_active,
             updated_at = datetime("now")
         WHERE id = :id'
    );
    $stmt->execute([
        ':id' => $id,
        ':title' => $data['title'],
        ':slug' => $data['slug'],
        ':description' => $data['description'],
        ':is_active' => (int)$data['is_active'],
    ]);
}

function countLearningClasses(): int
{
    $db = getDB();

    return (int)$db->query('SELECT COUNT(*) FROM classes')->fetchColumn();
}

function learningClassSlugExists(string $slug, ?int $excludeId = null): bool
{
    $db = getDB();
    $sql = 'SELECT COUNT(*) FROM classes WHERE slug = :slug';
    $params = [':slug' => $slug];

    if ($excludeId !== null) {
        $sql .= ' AND id != :id';
        $params[':id'] = $excludeId;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    return (int)$stmt->fetchColumn() > 0;
}
