<?php

require ROOT . 'app/models/class.php';
require ROOT . 'app/models/material.php';
require ROOT . 'app/models/material-file.php';

$action = $segments[2] ?? 'index';

if ($action === 'create') {
    require ROOT . 'app/controllers/admin/materials/create.php';
    return;
}

if (is_numeric($action) && ($segments[3] ?? '') === 'edit') {
    require ROOT . 'app/controllers/admin/materials/edit.php';
    return;
}

if (is_numeric($action) && ($segments[3] ?? '') === 'files') {
    require ROOT . 'app/controllers/admin/materials/files.php';
    return;
}

if (is_numeric($action) && ($segments[3] ?? '') === 'delete') {
    require ROOT . 'app/controllers/admin/materials/delete.php';
    return;
}

if ($action !== 'index') {
    abort404();
}

$filters = [
    'class_id' => (int)($_GET['class_id'] ?? 0),
    'type' => trim((string)($_GET['type'] ?? '')),
    'archive' => trim((string)($_GET['archive'] ?? 'active')),
];

renderTemplate('pages/admin/materials/index.tpl', [
    'materials' => getMaterialsForAdmin($filters),
    'classes' => getAllLearningClasses(),
    'filters' => $filters,
    'flash' => getFlash('admin'),
]);
