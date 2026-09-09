<?php

$sourceId = requireNumericId($segments[2] ?? null);
$source = getAiKnowledgeSourceById($sourceId);
requireFound($source);

if (!isPost()) {
    redirectTo('admin/ai-knowledge');
}

restoreAiKnowledgeSource($sourceId);
setFlash('admin', 'Источник базы знаний восстановлен.');
redirectTo('admin/ai-knowledge');
