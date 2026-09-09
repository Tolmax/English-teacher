<?php

function createMaterialFile(array $data): int
{
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO material_files (material_id, original_name, stored_name, mime_type, file_size)
         VALUES (:material_id, :original_name, :stored_name, :mime_type, :file_size)'
    );
    $stmt->execute([
        ':material_id' => (int)$data['material_id'],
        ':original_name' => $data['original_name'],
        ':stored_name' => $data['stored_name'],
        ':mime_type' => $data['mime_type'],
        ':file_size' => (int)$data['file_size'],
    ]);

    return (int)$db->lastInsertId();
}

function getMaterialFileById(int $id): array|false
{
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM material_files WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    return $stmt->fetch();
}

function getMaterialFilesByMaterialId(int $materialId): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT * FROM material_files
         WHERE material_id = :material_id
         ORDER BY created_at DESC, id DESC'
    );
    $stmt->execute([':material_id' => $materialId]);

    return $stmt->fetchAll();
}

function getMaterialFilesByMaterialIds(array $materialIds): array
{
    $materialIds = array_values(array_unique(array_map('intval', $materialIds)));

    if (empty($materialIds)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($materialIds), '?'));
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT * FROM material_files
         WHERE material_id IN (' . $placeholders . ')
         ORDER BY created_at DESC, id DESC'
    );
    $stmt->execute($materialIds);

    $filesByMaterial = [];
    foreach ($stmt->fetchAll() as $file) {
        $filesByMaterial[(int)$file['material_id']][] = $file;
    }

    return $filesByMaterial;
}

function deleteMaterialFile(int $id): void
{
    $db = getDB();
    $stmt = $db->prepare('DELETE FROM material_files WHERE id = :id');
    $stmt->execute([':id' => $id]);
}
