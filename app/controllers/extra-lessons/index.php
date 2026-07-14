<?php

require ROOT . 'app/models/class.php';
require ROOT . 'app/models/extra-lesson-request.php';
require ROOT . 'app/validators/extra-lesson-request.php';

$classes = getAllLearningClasses(true);
$errors = [];
$old = [
    'student_name' => '',
    'student_contact' => '',
    'class_title' => '',
    'lesson_format' => 'individual',
    'topic' => '',
    'message' => '',
];

if (isPost()) {
    $old = [
        'student_name' => trim((string)($_POST['student_name'] ?? '')),
        'student_contact' => trim((string)($_POST['student_contact'] ?? '')),
        'class_title' => trim((string)($_POST['class_title'] ?? '')),
        'lesson_format' => trim((string)($_POST['lesson_format'] ?? '')) !== ''
            ? trim((string)$_POST['lesson_format'])
            : 'individual',
        'topic' => trim((string)($_POST['topic'] ?? '')),
        'message' => trim((string)($_POST['message'] ?? '')),
    ];

    $errors = validateExtraLessonRequestData($old);

    if (empty($errors)) {
        $requestId = createExtraLessonRequest($old);
        setFlash('extra_lessons', 'Заявка отправлена учителю. Номер заявки: ' . $requestId . '.');
        redirectTo('extra-lessons?sent=' . $requestId);
    }
}

renderTemplate('pages/extra-lessons/index.tpl', [
    'classes' => $classes,
    'errors' => $errors,
    'old' => $old,
    'flash' => getFlash('extra_lessons'),
    'sent' => (int)($_GET['sent'] ?? 0),
]);
