<?php

require ROOT . 'app/models/material-file.php';

$presentationId = requireNumericId($segments[2] ?? null);
$presentation = getAiWordPresentationById($presentationId);
requireFound($presentation);

$fileId = (int)($presentation['pptx_file_id'] ?? 0);
if ($fileId <= 0) {
    abort404();
}

$file = getMaterialFileById($fileId);
requireFound($file);

$path = ROOT . 'uploads/materials/' . (int)$file['material_id'] . '/' . (string)$file['stored_name'];
if (!is_file($path)) {
    abort404();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.presentationml.presentation');
header('Content-Disposition: attachment; filename="' . basename((string)$file['original_name']) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
