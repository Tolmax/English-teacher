<?php

$materialId = requireNumericId($segments[2] ?? null);
$material = getAiTeachingMaterialById($materialId);
requireFound($material);

if (!isPost()) {
    redirectTo('admin/ai-materials/' . $materialId . '/edit');
}

unpublishAiTeachingMaterial($materialId);
setFlash('admin', 'Материал снят с публикации.');
redirectTo('admin/ai-materials/' . $materialId . '/edit');
