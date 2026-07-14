<?php

$sourceId = requireNumericId($segments[2] ?? null);
$source = getAiKnowledgeSourceById($sourceId);
requireFound($source);

if (!isPost()) {
    redirectTo('admin/ai-knowledge');
}

archiveAiKnowledgeSource($sourceId);
setFlash('admin', 'Источник базы знаний перемещён в архив.');
redirectTo('admin/ai-knowledge?library_view=archive');
