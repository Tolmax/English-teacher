<?php

require ROOT . 'app/models/submission.php';

$submissionId = $segments[2] ?? null;

if ($submissionId !== null) {
    require ROOT . 'app/controllers/admin/submissions/show.php';
    return;
}

$status = trim((string)($_GET['status'] ?? 'new'));
if (!in_array($status, ['new', 'processed'], true)) {
    $status = 'new';
}

renderTemplate('pages/admin/submissions/index.tpl', [
    'submissions' => getSubmissionsByReviewStatus($status, 100),
    'activeStatus' => $status,
    'flash'       => getFlash('admin'),
]);
