<?php

require ROOT . 'app/validators/class.php';

$classId = requireNumericId($segments[2] ?? null);
$learningClass = getLearningClassById($classId);
requireFound($learningClass);

$errors = [];
$old = [
    'title' => $learningClass['title'],
    'slug' => $learningClass['slug'],
    'description' => $learningClass['description'] ?? '',
    'is_active' => (int)$learningClass['is_active'],
];

if (isPost()) {
    $old = [
        'title' => trim((string)($_POST['title'] ?? '')),
        'slug' => normalizeLearningClassSlug((string)($_POST['slug'] ?? '')),
        'description' => trim((string)($_POST['description'] ?? '')),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];

    $errors = validateLearningClassData($old);

    if (empty($errors) && learningClassSlugExists($old['slug'], $classId)) {
        $errors['slug'] = 'Такой адрес уже используется.';
    }

    if (empty($errors)) {
        updateLearningClass($classId, $old);
        setFlash('admin', 'Класс обновлен.');
        redirectTo('admin/classes');
    }
}

renderTemplate('pages/admin/classes/form.tpl', [
    'mode' => 'edit',
    'learningClass' => $learningClass,
    'errors' => $errors,
    'old' => $old,
]);
