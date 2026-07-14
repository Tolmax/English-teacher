<?php

$materialId = requireNumericId($segments[2] ?? null);
$material = getAiTeachingMaterialById($materialId);
requireFound($material);

if (!isPost()) {
    redirectTo('admin/ai-materials');
}

archiveAiTeachingMaterial($materialId);
setFlash('admin', 'Материал перемещён в архив.');
redirectTo('admin/ai-materials?library_view=archive');
