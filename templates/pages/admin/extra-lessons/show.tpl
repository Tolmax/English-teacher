<?php
$pageTitle = 'Заявка на допзанятие — English Teacher';
$activeNav = 'extra-lessons';
include ROOT . 'templates/partials/admin-header.tpl';
?>
      <section class="stack" aria-labelledby="extra-request-title">
        <div class="section__header">
          <h1 id="extra-request-title">Заявка на допзанятие</h1>
          <p class="section__lead">Подробности заявки ученика. Статус меняется в общем списке заявок.</p>
        </div>

        <article class="card">
          <div class="card__header">
            <h2><?= e($request['student_name']) ?></h2>
            <?php if ($request['status'] === 'new'): ?>
              <span class="badge badge--warning">Новая</span>
            <?php else: ?>
              <span class="badge badge--success">Обработана</span>
            <?php endif; ?>
          </div>

          <ul class="material-list">
            <li class="material-item"><strong>Контакт:</strong> <span><?= e($request['student_contact']) ?></span></li>
            <li class="material-item"><strong>Класс:</strong> <span><?= e($request['class_title'] !== '' ? $request['class_title'] : 'Не указан') ?></span></li>
            <li class="material-item"><strong>Формат:</strong> <span><?= e(getExtraLessonFormatLabel($request['lesson_format'] ?? '')) ?></span></li>
            <li class="material-item"><strong>Тема:</strong> <span><?= e($request['topic']) ?></span></li>
            <li class="material-item"><strong>Создана:</strong> <span><?= e($request['created_at']) ?></span></li>
            <?php if (!empty($request['processed_at'])): ?>
              <li class="material-item"><strong>Обработана:</strong> <span><?= e($request['processed_at']) ?></span></li>
            <?php endif; ?>
          </ul>

          <?php if (!empty($request['message'])): ?>
            <section class="stack" aria-labelledby="extra-request-message-title">
              <h3 id="extra-request-message-title">Комментарий</h3>
              <p class="card__text"><?= e($request['message']) ?></p>
            </section>
          <?php endif; ?>

          <div class="card__actions">
            <a class="button button--secondary" href="<?= HOST ?>admin/extra-lessons?status=<?= e($request['status'] === 'processed' ? 'processed' : 'new') ?>">Все заявки</a>
          </div>
        </article>
      </section>
<?php include ROOT . 'templates/partials/admin-footer.tpl'; ?>
