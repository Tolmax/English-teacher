<?php

require ROOT . 'app/models/class.php';
require ROOT . 'app/models/ai-word-presentation.php';
require ROOT . 'app/validators/ai-word-presentation.php';
require_once ROOT . 'app/services/yandex-service.php';

$action = $segments[2] ?? 'index';

if ($action === 'create') {
    require ROOT . 'app/controllers/admin/ai-presentations/create.php';
    return;
}

if (is_numeric($action) && ($segments[3] ?? '') === 'edit') {
    require ROOT . 'app/controllers/admin/ai-presentations/edit.php';
    return;
}

if (is_numeric($action) && ($segments[3] ?? '') === 'generate') {
    require ROOT . 'app/controllers/admin/ai-presentations/generate.php';
    return;
}

if (is_numeric($action) && ($segments[3] ?? '') === 'build') {
    require ROOT . 'app/controllers/admin/ai-presentations/build.php';
    return;
}

if (is_numeric($action) && ($segments[3] ?? '') === 'show') {
    require ROOT . 'app/controllers/admin/ai-presentations/show.php';
    return;
}

if (is_numeric($action) && ($segments[3] ?? '') === 'download') {
    require ROOT . 'app/controllers/admin/ai-presentations/download.php';
    return;
}

if (is_numeric($action) && ($segments[3] ?? '') === 'deck-publish') {
    require ROOT . 'app/controllers/admin/ai-presentations/deck-publish.php';
    return;
}

if (is_numeric($action) && ($segments[3] ?? '') === 'delete') {
    require ROOT . 'app/controllers/admin/ai-presentations/delete.php';
    return;
}

if ($action !== 'index') {
    abort404();
}

renderTemplate('pages/admin/ai-presentations/index.tpl', [
    'presentations' => getAiWordPresentationsForAdmin(),
    'flash' => getFlash('admin'),
    'errorFlash' => getFlash('admin_error'),
]);
