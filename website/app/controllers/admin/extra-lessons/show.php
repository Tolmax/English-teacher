<?php

require_once ROOT . 'app/validators/extra-lesson-request.php';

$requestId = requireNumericId($segments[2] ?? null);
$extraLessonRequest = getExtraLessonRequestById($requestId);
requireFound($extraLessonRequest);

if (isPost()) {
    $status = trim((string)($_POST['status'] ?? ''));
    $returnStatus = trim((string)($_POST['return_status'] ?? $status));

    if (isValidExtraLessonRequestStatus($status)) {
        updateExtraLessonRequestStatus($requestId, $status);
        setFlash('admin', 'Статус заявки обновлён.');
    }

    if (!isValidExtraLessonRequestStatus($returnStatus)) {
        $returnStatus = 'new';
    }

    redirectTo('admin/extra-lessons?status=' . $returnStatus);
}

renderTemplate('pages/admin/extra-lessons/show.tpl', [
    'request' => $extraLessonRequest,
]);
