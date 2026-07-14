<?php

$materialId = requireNumericId($segments[2] ?? null);
$material = getAiTeachingMaterialById($materialId);
requireFound($material);

if (!isPost()) {
    redirectTo('admin/ai-materials/' . $materialId . '/edit');
}

if (trim((string)($material['edited_content'] ?? '')) === '') {
    setFlash('admin_error', 'Заполните текст материала перед публикацией.');
    redirectTo('admin/ai-materials/' . $materialId . '/edit');
}

if (!empty($material['archived_at']) || ($material['status'] ?? '') === 'archived') {
    setFlash('admin_error', 'Архивный материал нужно сначала восстановить.');
    redirectTo('admin/ai-materials/' . $materialId . '/edit');
}

publishAiTeachingMaterial($materialId);
setFlash('admin', 'Материал опубликован.');
redirectTo('admin/ai-materials/' . $materialId . '/edit');
