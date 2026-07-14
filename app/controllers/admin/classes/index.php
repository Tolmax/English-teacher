<?php

require ROOT . 'app/models/class.php';

$action = $segments[2] ?? 'index';

if ($action === 'create') {
    require ROOT . 'app/controllers/admin/classes/create.php';
    return;
}

if (is_numeric($action) && ($segments[3] ?? '') === 'edit') {
    require ROOT . 'app/controllers/admin/classes/edit.php';
    return;
}

if ($action !== 'index') {
    abort404();
}

renderTemplate('pages/admin/classes/index.tpl', [
    'classes' => getAllLearningClasses(),
    'flash'   => getFlash('admin'),
]);
