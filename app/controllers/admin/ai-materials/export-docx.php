<?php

require ROOT . 'app/services/docx-export.php';

$materialId = requireNumericId($segments[2] ?? null);
$material = getAiTeachingMaterialById($materialId);
requireFound($material);

if (trim((string)($material['edited_content'] ?? '')) === '') {
    setFlash('admin_error', 'Заполните текст материала перед экспортом DOCX.');
    redirectTo('admin/ai-materials/' . $materialId . '/edit');
}

try {
    $docx = buildAiMaterialDocx($material);
} catch (Throwable $exception) {
    setFlash('admin_error', 'Не удалось создать DOCX. Проверьте настройки PHP ZipArchive.');
    redirectTo('admin/ai-materials/' . $materialId . '/edit');
}

header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $docx['filename'] . '"');
header('Content-Length: ' . filesize($docx['path']));
header('Cache-Control: private, max-age=0, must-revalidate');
readfile($docx['path']);
@unlink($docx['path']);
exit;
