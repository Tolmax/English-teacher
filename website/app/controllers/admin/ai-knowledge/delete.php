<?php

$sourceId = requireNumericId($segments[2] ?? null);
$source = getAiKnowledgeSourceById($sourceId);
requireFound($source);

if (!isPost()) {
    redirectTo('admin/ai-knowledge');
}

$storagePath = trim((string)($source['storage_path'] ?? ''));

deleteAiKnowledgeSource($sourceId);

if ($storagePath !== '' && str_starts_with($storagePath, 'uploads/ai-knowledge/')) {
    $absolutePath = ROOT . $storagePath;
    if (is_file($absolutePath)) {
        unlink($absolutePath);
    }
}

setFlash('admin', 'Источник базы знаний удалён.');
redirectTo('admin/ai-knowledge');
