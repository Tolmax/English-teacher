<?php

require ROOT . 'app/models/ai-interactive-test.php';
require ROOT . 'app/models/class.php';
require ROOT . 'app/validators/ai-interactive-test.php';

$action = $segments[2] ?? 'index';

if (is_numeric($action) && ($segments[3] ?? '') === 'edit') {
    require ROOT . 'app/controllers/admin/ai-tests/edit.php';
    return;
}

if (is_numeric($action) && ($segments[3] ?? '') === 'publish') {
    require ROOT . 'app/controllers/admin/ai-tests/publish.php';
    return;
}

if (is_numeric($action) && ($segments[3] ?? '') === 'unpublish') {
    require ROOT . 'app/controllers/admin/ai-tests/unpublish.php';
    return;
}

if (is_numeric($action) && ($segments[3] ?? '') === 'submissions') {
    require ROOT . 'app/controllers/admin/ai-tests/submissions.php';
    return;
}

if (is_numeric($action) && ($segments[3] ?? '') === 'delete') {
    require ROOT . 'app/controllers/admin/ai-tests/delete.php';
    return;
}

if ($action !== 'index') {
    abort404();
}

renderTemplate('pages/admin/ai-tests/index.tpl', [
    'tests' => getAiInteractiveTestsForAdmin(),
    'flash' => getFlash('admin'),
    'errorFlash' => getFlash('admin_error'),
]);
