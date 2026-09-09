<?php

require ROOT . 'app/models/extra-lesson-request.php';
require ROOT . 'app/validators/extra-lesson-request.php';

$requestId = $segments[2] ?? null;

if ($requestId !== null) {
    require ROOT . 'app/controllers/admin/extra-lessons/show.php';
    return;
}

$status = trim((string)($_GET['status'] ?? 'new'));
if (!isValidExtraLessonRequestStatus($status)) {
    $status = 'new';
}

renderTemplate('pages/admin/extra-lessons/index.tpl', [
    'requests' => getExtraLessonRequestsByStatus($status, 100),
    'activeStatus' => $status,
    'flash' => getFlash('admin'),
]);
