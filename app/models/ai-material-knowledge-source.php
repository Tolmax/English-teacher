<?php

function getAiKnowledgeSourcesByMaterialId(int $materialId): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT aks.*
         FROM ai_material_knowledge_sources amks
         INNER JOIN ai_knowledge_sources aks ON aks.id = amks.knowledge_source_id
         WHERE amks.material_id = :material_id
         ORDER BY aks.title ASC, aks.id ASC'
    );
    $stmt->execute([':material_id' => $materialId]);

    return $stmt->fetchAll();
}

function getAiKnowledgeSourceIdsByMaterialId(int $materialId): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT knowledge_source_id
         FROM ai_material_knowledge_sources
         WHERE material_id = :material_id
         ORDER BY knowledge_source_id ASC'
    );
    $stmt->execute([':material_id' => $materialId]);

    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function getActiveAiKnowledgeSourceIdsByMaterialId(int $materialId): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT aks.id
         FROM ai_material_knowledge_sources amks
         INNER JOIN ai_knowledge_sources aks ON aks.id = amks.knowledge_source_id
         WHERE amks.material_id = :material_id
           AND aks.status = \'active\'
           AND aks.archived_at IS NULL
         ORDER BY aks.title ASC, aks.id ASC'
    );
    $stmt->execute([':material_id' => $materialId]);

    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function getActiveAiKnowledgeSourcesByMaterialId(int $materialId): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT aks.*
         FROM ai_material_knowledge_sources amks
         INNER JOIN ai_knowledge_sources aks ON aks.id = amks.knowledge_source_id
         WHERE amks.material_id = :material_id
           AND aks.status = \'active\'
           AND aks.archived_at IS NULL
         ORDER BY aks.title ASC, aks.id ASC'
    );
    $stmt->execute([':material_id' => $materialId]);

    return $stmt->fetchAll();
}

function getAiKnowledgeSourcesByMaterialIds(array $materialIds): array
{
    $materialIds = array_values(array_unique(array_filter(array_map('intval', $materialIds))));

    if ($materialIds === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($materialIds), '?'));
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT amks.material_id, aks.id, aks.title, aks.source_type, aks.status, aks.archived_at
         FROM ai_material_knowledge_sources amks
         INNER JOIN ai_knowledge_sources aks ON aks.id = amks.knowledge_source_id
         WHERE amks.material_id IN (' . $placeholders . ')
         ORDER BY aks.title ASC, aks.id ASC'
    );
    $stmt->execute($materialIds);

    $sourcesByMaterial = [];
    foreach ($stmt->fetchAll() as $source) {
        $sourcesByMaterial[(int)$source['material_id']][] = $source;
    }

    return $sourcesByMaterial;
}

function replaceAiMaterialKnowledgeSources(int $materialId, array $sourceIds): void
{
    $db = getDB();
    $sourceIds = array_values(array_unique(array_filter(array_map('intval', $sourceIds))));

    $db->beginTransaction();
    try {
        $deleteStmt = $db->prepare('DELETE FROM ai_material_knowledge_sources WHERE material_id = :material_id');
        $deleteStmt->execute([':material_id' => $materialId]);

        $insertStmt = $db->prepare(
            'INSERT OR IGNORE INTO ai_material_knowledge_sources (material_id, knowledge_source_id)
             VALUES (:material_id, :knowledge_source_id)'
        );

        foreach ($sourceIds as $sourceId) {
            $insertStmt->execute([
                ':material_id' => $materialId,
                ':knowledge_source_id' => $sourceId,
            ]);
        }

        $db->commit();
    } catch (Throwable $exception) {
        $db->rollBack();
        throw $exception;
    }
}
