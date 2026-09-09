<?php
$pageTitle = 'Ответ ученика — English Teacher';
$activeNav = 'submissions';
include ROOT . 'templates/partials/admin-header.tpl';
?>
      <section class="stack" aria-labelledby="submission-title">
        <div class="section__header">
          <h1 id="submission-title">Ответ ученика</h1>
          <p class="section__lead"><?= e($submission['student_name']) ?>, <?= e($submission['class_title']) ?></p>
        </div>

        <section class="card stack" aria-labelledby="submission-detail-title">
          <h2 id="submission-detail-title"><?= e($submission['task_title']) ?></h2>
          <p><strong>Результат:</strong> <?= (int)$submission['score'] ?> баллов</p>
          <p><strong>Отправлено:</strong> <?= e($submission['submitted_at']) ?></p>
          <?php if (!empty($submission['student_contact'])): ?>
            <p><strong>Контакт:</strong> <?= e($submission['student_contact']) ?></p>
          <?php endif; ?>

          <section aria-labelledby="answers-title">
            <h3 id="answers-title">Ответы</h3>
            <ul class="material-list">
              <?php foreach ($answers as $key => $answer): ?>
                <li class="material-item">
                  <div>
                    <h4><?= e((string)$key) ?></h4>
                    <p class="card__text"><?= e(is_scalar($answer) ? (string)$answer : json_encode($answer, JSON_UNESCAPED_UNICODE)) ?></p>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          </section>

          <form action="<?= HOST ?>admin/submissions/<?= (int)$submission['id'] ?>" method="post">
            <button class="button button--primary" type="submit">Отметить просмотренным</button>
            <a class="button button--secondary" href="<?= HOST ?>admin/submissions">К списку</a>
          </form>
        </section>
      </section>
<?php include ROOT . 'templates/partials/admin-footer.tpl'; ?>
