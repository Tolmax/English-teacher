<?php
$pageTitle = 'Классы — English Teacher';
$activeNav = 'classes';
include ROOT . 'templates/partials/admin-header.tpl';
?>
      <section class="stack" aria-labelledby="classes-title">
        <div class="section__header">
          <h1 id="classes-title">Классы</h1>
          <p class="section__lead">Управление страницами классов, которые видят ученики.</p>
        </div>

        <?php if (!empty($flash)): ?>
          <div class="alert alert--success" role="status"><?= e($flash) ?></div>
        <?php endif; ?>

        <div class="card__actions">
          <a class="button button--primary" href="<?= HOST ?>admin/classes/create">Добавить класс</a>
          <a class="button button--secondary" href="<?= HOST ?>admin/materials/create">Добавить материал</a>
        </div>

        <div class="card-grid">
          <?php foreach ($classes as $class): ?>
            <article class="card">
              <div class="card__header">
                <h2><?= e($class['title']) ?></h2>
                <span class="badge"><?= (int)$class['is_active'] === 1 ? 'Активен' : 'Скрыт' ?></span>
              </div>
              <p class="card__text"><?= e($class['description'] ?? 'Описание пока не добавлено.') ?></p>
              <p class="card__text">Адрес: /class/<?= e($class['slug']) ?></p>
              <div class="card__actions">
                <a class="button button--secondary button--small" href="<?= HOST ?>admin/classes/<?= (int)$class['id'] ?>/edit">Редактировать</a>
                <a class="button button--primary button--small" href="<?= HOST ?>admin/materials/create?class_id=<?= (int)$class['id'] ?>">Материал</a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
<?php include ROOT . 'templates/partials/admin-footer.tpl'; ?>
