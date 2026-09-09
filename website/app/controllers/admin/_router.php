<?php

$adminSection = $segments[1] ?? 'dashboard';

if ($adminSection === 'login') {
    require ROOT . 'app/controllers/admin/login.php';
    return;
}

if ($adminSection === 'logout') {
    require ROOT . 'app/controllers/admin/logout.php';
    return;
}

requireAuth();

match ($adminSection) {
    'teacher-guide' => require ROOT . 'app/controllers/admin/teacher-guide.php',
    'dashboard'   => require ROOT . 'app/controllers/admin/dashboard.php',
    'system-check' => require ROOT . 'app/controllers/admin/system-check.php',
    'classes'     => require ROOT . 'app/controllers/admin/classes/index.php',
    'materials'   => require ROOT . 'app/controllers/admin/materials/index.php',
    'calendar'    => require ROOT . 'app/controllers/admin/calendar/index.php',
    'ai-materials' => require ROOT . 'app/controllers/admin/ai-materials/index.php',
    'ai-tests' => require ROOT . 'app/controllers/admin/ai-tests/index.php',
    'ai-presentations' => require ROOT . 'app/controllers/admin/ai-presentations/index.php',
    'ai-knowledge' => require ROOT . 'app/controllers/admin/ai-knowledge/index.php',
    'extra-lessons' => require ROOT . 'app/controllers/admin/extra-lessons/index.php',
    'submissions' => require ROOT . 'app/controllers/admin/submissions/index.php',
    default       => abort404(),
};
