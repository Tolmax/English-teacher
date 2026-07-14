<?php

function getMaterialsForAdmin(array $filters = []): array
{
    $db = getDB();
    $where = [];
    $params = [];
    $sql =
        'SELECT materials.*, classes.title AS class_title,
                COUNT(material_files.id) AS files_count
         FROM materials
         INNER JOIN classes ON classes.id = materials.class_id
         LEFT JOIN material_files ON material_files.material_id = materials.id';
    $where[] = 'materials.type != "presentation"';

    if ((int)($filters['class_id'] ?? 0) > 0) {
        $where[] = 'materials.class_id = :class_id';
        $params[':class_id'] = (int)$filters['class_id'];
    }

    if (($filters['type'] ?? '') !== '') {
        $where[] = 'materials.type = :type';
        $params[':type'] = $filters['type'];
    }

    $archiveView = (string)($filters['archive'] ?? 'active');
    if ($archiveView === 'archive') {
        $where[] = 'materials.deadline_at IS NOT NULL AND materials.deadline_at != "" AND materials.deadline_at < date("now")';
    } elseif ($archiveView !== 'all') {
        $where[] = '(materials.deadline_at IS NULL OR materials.deadline_at = "" OR materials.deadline_at >= date("now"))';
    }

    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' GROUP BY materials.id ORDER BY materials.created_at DESC, materials.id DESC';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function getMaterialsByClassId(int $classId): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT * FROM materials
         WHERE class_id = :class_id
           AND is_published = 1
           AND type != "presentation"
         ORDER BY deadline_at IS NULL, deadline_at ASC, created_at DESC'
    );
    $stmt->execute([':class_id' => $classId]);

    return $stmt->fetchAll();
}

function getActiveMaterialsByClassId(int $classId): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT * FROM materials
         WHERE class_id = :class_id
           AND is_published = 1
           AND type != "presentation"
           AND (deadline_at IS NULL OR deadline_at = "" OR deadline_at >= date("now"))
         ORDER BY deadline_at IS NULL, deadline_at ASC, created_at DESC'
    );
    $stmt->execute([':class_id' => $classId]);

    return $stmt->fetchAll();
}

function getArchivedMaterialsByClassId(int $classId): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT * FROM materials
         WHERE class_id = :class_id
           AND is_published = 1
           AND type != "presentation"
           AND deadline_at IS NOT NULL
           AND deadline_at != ""
           AND deadline_at < date("now")
         ORDER BY deadline_at DESC, created_at DESC'
    );
    $stmt->execute([':class_id' => $classId]);

    return $stmt->fetchAll();
}

function getMaterialById(int $id): array|false
{
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM materials WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    return $stmt->fetch();
}

function createMaterial(array $data): int
{
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO materials (class_id, type, title, description, content, status, deadline_at, is_published)
         VALUES (:class_id, :type, :title, :description, :content, :status, :deadline_at, :is_published)'
    );
    $stmt->execute(materialParams($data));

    return (int)$db->lastInsertId();
}

function updateMaterial(int $id, array $data): void
{
    $db = getDB();
    $params = materialParams($data);
    $params[':id'] = $id;

    $stmt = $db->prepare(
        'UPDATE materials
         SET class_id = :class_id,
             type = :type,
             title = :title,
             description = :description,
             content = :content,
             status = :status,
             deadline_at = :deadline_at,
             is_published = :is_published,
             updated_at = datetime("now")
         WHERE id = :id'
    );
    $stmt->execute($params);
}

function deleteMaterial(int $id): void
{
    $db = getDB();
    $stmt = $db->prepare('DELETE FROM materials WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

function countMaterials(): int
{
    $db = getDB();

    return (int)$db->query('SELECT COUNT(*) FROM materials')->fetchColumn();
}

function materialTypeLabel(string $type): string
{
    return match ($type) {
        'lesson' => 'Урок',
        'homework' => 'Домашка',
        'announcement' => 'Объявление',
        default => 'Материал',
    };
}

function materialPublicationLabel(array $material): string
{
    return (int)($material['is_published'] ?? 0) === 1 ? 'Опубликован' : 'Не опубликован';
}

function materialIsArchived(array $material): bool
{
    $deadline = trim((string)($material['deadline_at'] ?? ''));

    return $deadline !== '' && $deadline < date('Y-m-d');
}

function materialParams(array $data): array
{
    return [
        ':class_id' => (int)$data['class_id'],
        ':type' => $data['type'],
        ':title' => $data['title'],
        ':description' => $data['description'],
        ':content' => $data['content'],
        ':status' => $data['status'],
        ':deadline_at' => $data['deadline_at'] !== '' ? $data['deadline_at'] : null,
        ':is_published' => (int)$data['is_published'],
    ];
}
