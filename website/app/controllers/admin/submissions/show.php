<?php

$submission = getSubmissionById(requireNumericId($submissionId));
requireFound($submission);

if (isPost()) {
    $status = trim((string)($_POST['status'] ?? 'processed'));
    $returnStatus = trim((string)($_POST['return_status'] ?? $status));

    if (in_array($status, ['new', 'processed'], true)) {
        updateSubmissionReviewStatus((int)$submission['id'], $status);
        setFlash('admin', 'Статус ответа обновлён.');
    }

    if (!in_array($returnStatus, ['new', 'processed'], true)) {
        $returnStatus = 'new';
    }

    redirectTo('admin/submissions?status=' . $returnStatus);
}

redirectTo('admin/submissions');
