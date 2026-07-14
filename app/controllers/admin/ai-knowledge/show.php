<?php

$sourceId = requireNumericId($segments[2] ?? null);
$source = getAiKnowledgeSourceById($sourceId);
requireFound($source);

renderTemplate('pages/admin/ai-knowledge/show.tpl', [
    'source' => $source,
]);
