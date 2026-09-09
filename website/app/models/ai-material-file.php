<?php

function getAiMaterialFilesByMaterialId(int $materialId): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT *
         FROM ai_material_files
         WHERE material_id = :material_id
         ORDER BY created_at DESC, id DESC'
    );
    $stmt->execute([':material_id' => $materialId]);

    return $stmt->fetchAll();
}

function createAiMaterialFile(array $data): int
{
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO ai_material_files
            (material_id, file_type, original_name, stored_name, storage_path, mime_type, file_size)
         VALUES
            (:material_id, :file_type, :original_name, :stored_name, :storage_path, :mime_type, :file_size)'
    );
    $stmt->execute([
        ':material_id' => (int)$data['material_id'],
        ':file_type' => $data['file_type'],
        ':original_name' => $data['original_name'],
        ':stored_name' => $data['stored_name'],
        ':storage_path' => $data['storage_path'],
        ':mime_type' => $data['mime_type'],
        ':file_size' => (int)$data['file_size'],
    ]);

    return (int)$db->lastInsertId();
}

function copyAiMaterialFiles(int $sourceMaterialId, int $targetMaterialId): void
{
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO ai_material_files
            (material_id, file_type, original_name, stored_name, storage_path, mime_type, file_size)
         SELECT
            :target_material_id, file_type, original_name, stored_name, storage_path, mime_type, file_size
         FROM ai_material_files
         WHERE material_id = :source_material_id'
    );
    $stmt->execute([
        ':target_material_id' => $targetMaterialId,
        ':source_material_id' => $sourceMaterialId,
    ]);
}
