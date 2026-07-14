<?php

require ROOT . 'app/models/ai-knowledge-source.php';
require ROOT . 'app/models/ai-knowledge-category.php';
require ROOT . 'app/models/ai-material-knowledge-source.php';

$action = $segments[2] ?? 'index';

if ($action === 'create') {
    require ROOT . 'app/controllers/admin/ai-knowledge/create.php';
    return;
}

if (is_numeric($action) && ($segments[3] ?? '') === 'edit') {
    require ROOT . 'app/controllers/admin/ai-knowledge/edit.php';
    return;
}

if (is_numeric($action) && ($segments[3] ?? '') === 'archive') {
    require ROOT . 'app/controllers/admin/ai-knowledge/archive.php';
    return;
}

if (is_numeric($action) && ($segments[3] ?? '') === 'restore') {
    require ROOT . 'app/controllers/admin/ai-knowledge/restore.php';
    return;
}

if (is_numeric($action) && ($segments[3] ?? '') === 'delete') {
    require ROOT . 'app/controllers/admin/ai-knowledge/delete.php';
    return;
}

if (is_numeric($action) && ($segments[3] ?? '') === '') {
    require ROOT . 'app/controllers/admin/ai-knowledge/show.php';
    return;
}

if ($action !== 'index') {
    abort404();
}

$filters = [
    'search' => trim((string)($_GET['search'] ?? '')),
    'category_id' => (int)($_GET['category_id'] ?? 0),
    'source_type' => trim((string)($_GET['source_type'] ?? '')),
    'status' => trim((string)($_GET['status'] ?? '')),
    'library_view' => trim((string)($_GET['library_view'] ?? 'active')),
];

if (!in_array($filters['library_view'], ['active', 'archive', 'all'], true)) {
    $filters['library_view'] = 'active';
}

renderTemplate('pages/admin/ai-knowledge/index.tpl', [
    'sources' => getAiKnowledgeSources($filters),
    'categories' => getAiKnowledgeCategories(),
    'filters' => $filters,
    'flash' => getFlash('admin'),
    'errorFlash' => getFlash('admin_error'),
]);
