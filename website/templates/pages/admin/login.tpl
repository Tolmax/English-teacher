<?php
$pageTitle = 'Вход учителя — English Teacher';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Вход в панель учителя английского.">
  <title><?= e($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= HOST ?>assets/css/main.css?v=20260620-4">
</head>
<body>
  <main class="login-page">
    <section class="card stack login-card" aria-labelledby="login-title">
      <div class="section__header">
        <h1 id="login-title">Вход учителя</h1>
        <p class="section__lead">Введите логин и пароль, чтобы управлять классами и ответами учеников.</p>
      </div>

      <?php if (!empty($flash)): ?>
        <div class="alert alert--success" role="status"><?= e($flash) ?></div>
      <?php endif; ?>

      <?php if (!empty($errors['form'])): ?>
        <div class="alert alert--warning" role="alert"><?= e($errors['form']) ?></div>
      <?php endif; ?>

      <form class="form" action="<?= HOST ?>admin/login" method="post" novalidate>
        <div class="form__field">
          <label class="form__label" for="email">Логин</label>
          <input class="input" type="text" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" autocomplete="username" maxlength="255" required aria-describedby="email-error" aria-invalid="<?= !empty($errors['email']) ? 'true' : 'false' ?>">
          <?php if (!empty($errors['email'])): ?>
            <span class="form__error" id="email-error" role="alert"><?= e($errors['email']) ?></span>
          <?php endif; ?>
        </div>

        <div class="form__field">
          <label class="form__label" for="password">Пароль</label>
          <input class="input" type="password" id="password" name="password" autocomplete="current-password" required aria-describedby="password-error" aria-invalid="<?= !empty($errors['password']) ? 'true' : 'false' ?>">
          <?php if (!empty($errors['password'])): ?>
            <span class="form__error" id="password-error" role="alert"><?= e($errors['password']) ?></span>
          <?php endif; ?>
        </div>

        <button class="button button--primary" type="submit">Войти</button>
      </form>
    </section>
  </main>
</body>
</html>
