<?php

require ROOT . 'app/models/class.php';
require ROOT . 'app/models/ai-teaching-material.php';
require ROOT . 'app/models/ai-interactive-test.php';
require ROOT . 'app/models/ai-word-presentation.php';
require ROOT . 'app/models/calendar-event.php';
require ROOT . 'app/models/material.php';
require ROOT . 'app/models/material-file.php';
require ROOT . 'app/models/student-question.php';
require ROOT . 'app/validators/student-question.php';

$slug = trim((string)($segments[1] ?? ''));
$learningClass = getLearningClassBySlug($slug);
requireFound($learningClass);
$classDisplayTitle = trim((string)preg_replace('/\s*класс\s*$/iu', '', (string)$learningClass['title']));

$errors = [];
$old = [
    'student_name' => '',
    'question' => '',
];

if (isPost()) {
    $old = [
        'student_name' => trim((string)($_POST['student_name'] ?? '')),
        'question' => trim((string)($_POST['question'] ?? '')),
    ];

    $errors = validateStudentQuestionData($old);

    if (empty($errors)) {
        createStudentQuestion([
            'class_id' => (int)$learningClass['id'],
            'student_name' => $old['student_name'],
            'question' => $old['question'],
            'status' => 'new',
        ]);
        setFlash('class', 'Вопрос отправлен учителю.');
        redirectTo('class/' . $learningClass['slug']);
    }
}

$materials = getActiveMaterialsByClassId((int)$learningClass['id']);
$archivedMaterials = getArchivedMaterialsByClassId((int)$learningClass['id']);
$archivedMaterials = array_values(array_filter($archivedMaterials, static function (array $material): bool {
    return (int)$material['id'] !== 3;
}));
$materialIds = array_merge(
    array_column($materials, 'id'),
    array_column($archivedMaterials, 'id')
);
$filesByMaterial = getMaterialFilesByMaterialIds($materialIds);
$homework = [];
$announcements = [];
$lessonMaterials = [];

foreach ($materials as $material) {
    if ($material['type'] === 'homework') {
        $homework[] = $material;
    } elseif ($material['type'] === 'announcement') {
        $announcements[] = $material;
    } else {
        $lessonMaterials[] = $material;
    }
}

$calendarEvents = getUpcomingPublicLessonCalendarEventsByClassId((int)$learningClass['id'], 2);

renderTemplate('pages/class/show.tpl', [
    'learningClass' => $learningClass,
    'classDisplayTitle' => $classDisplayTitle !== '' ? $classDisplayTitle : (string)$learningClass['title'],
    'homework' => $homework,
    'lessonMaterials' => $lessonMaterials,
    'aiLessonMaterials' => getPublishedAiTeachingMaterialsByClassTitle((string)$learningClass['title']),
    'aiTests' => getPublishedAiInteractiveTestsByClassTitle((string)$learningClass['title']),
    'flashcardDecks' => getPublishedAiWordPresentationDecksByClassId((int)$learningClass['id']),
    'announcements' => $announcements,
    'archivedMaterials' => $archivedMaterials,
    'filesByMaterial' => $filesByMaterial,
    'calendarEvents' => $calendarEvents,
    'errors' => $errors,
    'old' => $old,
    'flash' => getFlash('class'),
]);
