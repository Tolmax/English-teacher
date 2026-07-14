<?php

$materialId = requireNumericId($segments[2] ?? null);
$material = getAiTeachingMaterialById($materialId);
requireFound($material);

if (!isPost()) {
    redirectTo('admin/ai-materials');
}

restoreAiTeachingMaterial($materialId);
setFlash('admin', 'Материал восстановлен из архива.');
redirectTo('admin/ai-materials');
