<?php
$pageTitle = $pageTitle ?? 'English Teacher';
$pageDescription = $pageDescription ?? 'Учебный сайт учителя английского с материалами для классов.';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= e($pageDescription) ?>">
  <title><?= e($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= HOST ?>assets/css/main.css?v=20260704-7">
</head>
<body class="public-site">
  <header class="site-header">
    <div class="container nav">
      <a class="nav__brand" href="<?= HOST ?>">English Teacher</a>
      <button class="nav__toggle" type="button" aria-expanded="false" aria-controls="public-site-menu" data-mobile-menu-toggle>
        <span class="nav__toggle-line" aria-hidden="true"></span>
        <span class="nav__toggle-line" aria-hidden="true"></span>
        <span class="nav__toggle-line" aria-hidden="true"></span>
        <span class="sr-only">Открыть меню</span>
      </button>
      <nav class="nav__menu" id="public-site-menu" aria-label="Основная навигация" data-mobile-menu hidden>
        <ul class="nav__list">
          <li><a class="nav__link" href="<?= HOST ?>">Главная</a></li>
          <li><a class="nav__link" href="<?= HOST ?>">Классы</a></li>
          <li><a class="nav__link" href="<?= HOST ?>extra-lessons">Допзанятия</a></li>
          <li><a class="nav__link" href="<?= HOST ?>#questions">Вопросы</a></li>
        </ul>
      </nav>
    </div>
  </header>
