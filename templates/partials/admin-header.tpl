<?php
$pageTitle = $pageTitle ?? 'Панель учителя — English Teacher';
$activeNav = $activeNav ?? '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Панель учителя английского для управления классами, материалами, ИИ-тестами и ответами учеников.">
  <title><?= e($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= HOST ?>assets/css/main.css?v=20260620-4">
</head>
<body>
  <div class="admin-shell" data-admin-shell>
    <button class="admin-sidebar-toggle" type="button" aria-controls="admin-sidebar" aria-expanded="true" data-admin-sidebar-toggle>
      ‹
    </button>
    <aside class="admin-sidebar" id="admin-sidebar">
      <a class="nav__brand" href="<?= HOST ?>admin/dashboard">Teacher Admin</a>
      <nav class="admin-sidebar__nav" aria-label="Админская навигация">
        <a class="admin-sidebar__link" href="<?= HOST ?>admin/dashboard"<?= $activeNav === 'dashboard' ? ' aria-current="page"' : '' ?>>Сводка</a>
        <a class="admin-sidebar__link" href="<?= HOST ?>admin/classes"<?= $activeNav === 'classes' ? ' aria-current="page"' : '' ?>>Классы</a>
        <a class="admin-sidebar__link" href="<?= HOST ?>admin/materials"<?= $activeNav === 'materials' ? ' aria-current="page"' : '' ?>>Материалы</a>
        <a class="admin-sidebar__link" href="<?= HOST ?>admin/calendar"<?= $activeNav === 'calendar' ? ' aria-current="page"' : '' ?>>Календарь</a>
        <a class="admin-sidebar__link" href="<?= HOST ?>admin/system-check"<?= $activeNav === 'system-check' ? ' aria-current="page"' : '' ?>>Диагностика</a>
        <a class="admin-sidebar__link" href="<?= HOST ?>admin/ai-materials"<?= $activeNav === 'ai-materials' ? ' aria-current="page"' : '' ?>>ИИ-материалы</a>
        <a class="admin-sidebar__link" href="<?= HOST ?>admin/ai-tests"<?= $activeNav === 'ai-tests' ? ' aria-current="page"' : '' ?>>ИИ-тесты</a>
        <a class="admin-sidebar__link" href="<?= HOST ?>admin/ai-presentations"<?= $activeNav === 'ai-presentations' ? ' aria-current="page"' : '' ?>>ИИ-презентации</a>
        <a class="admin-sidebar__link" href="<?= HOST ?>admin/ai-knowledge"<?= $activeNav === 'ai-knowledge' ? ' aria-current="page"' : '' ?>>База знаний</a>
        <a class="admin-sidebar__link" href="<?= HOST ?>admin/extra-lessons"<?= $activeNav === 'extra-lessons' ? ' aria-current="page"' : '' ?>>Допзанятия</a>
        <a class="admin-sidebar__link" href="<?= HOST ?>admin/submissions"<?= $activeNav === 'submissions' ? ' aria-current="page"' : '' ?>>Ответы учеников</a>
        <a class="admin-sidebar__link" href="<?= HOST ?>">На сайт</a>
        <a class="admin-sidebar__link" href="<?= HOST ?>admin/logout">Выйти</a>
      </nav>
    </aside>
    <main class="admin-main">
