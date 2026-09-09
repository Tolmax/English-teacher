<?php

require ROOT . 'app/validators/class.php';

$errors = [];
$old = [
    'title' => '',
    'slug' => '',
    'description' => '',
    'is_active' => 1,
];

if (isPost()) {
    $old = [
        'title' => trim((string)($_POST['title'] ?? '')),
        'slug' => normalizeLearningClassSlug((string)($_POST['slug'] ?? '')),
        'description' => trim((string)($_POST['description'] ?? '')),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];

    $errors = validateLearningClassData($old);

    if (empty($errors) && learningClassSlugExists($old['slug'])) {
        $errors['slug'] = 'Такой адрес уже используется.';
    }

    if (empty($errors)) {
        createLearningClass($old);
        setFlash('admin', 'Класс добавлен.');
        redirectTo('admin/classes');
    }
}

renderTemplate('pages/admin/classes/form.tpl', [
    'mode' => 'create',
    'errors' => $errors,
    'old' => $old,
]);
