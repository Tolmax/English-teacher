<?php
$pageTitle = 'Ответы учеников — English Teacher';
$activeNav = 'submissions';
$activeStatus = $activeStatus ?? 'new';
$statusTitle = $activeStatus === 'processed' ? 'Обработанные ответы' : 'Новые ответы';
include ROOT . 'templates/partials/admin-header.tpl';
?>
      <section class="stack" aria-labelledby="submissions-title">
        <div class="section__header section__header--inline">
          <div>
            <h1 id="submissions-title">Ответы учеников</h1>
            <p class="section__lead">Короткий журнал результатов: ученик, класс, тест и статус обработки.</p>
          </div>
          <div class="admin-filter" aria-label="Фильтр ответов">
            <a class="button button--small <?= $activeStatus === 'new' ? 'button--primary' : 'button--secondary' ?>" href="<?= HOST ?>admin/submissions?status=new">Новые</a>
            <a class="button button--small <?= $activeStatus === 'processed' ? 'button--primary' : 'button--secondary' ?>" href="<?= HOST ?>admin/submissions?status=processed">Обработанные</a>
          </div>
        </div>

        <?php if (!empty($flash)): ?>
          <div class="alert alert--success" role="status"><?= e($flash) ?></div>
        <?php endif; ?>

        <section class="card" aria-labelledby="submissions-list-title">
          <div class="section__header section__header--inline">
            <h2 id="submissions-list-title"><?= e($statusTitle) ?></h2>
            <span class="badge"><?= count($submissions) ?></span>
          </div>

          <?php if (empty($submissions)): ?>
            <p class="card__text">В этом списке пока нет ответов.</p>
          <?php else: ?>
            <div class="request-list" role="list">
              <?php foreach ($submissions as $submission): ?>
                <article class="request-row request-row--static" role="listitem">
                  <div class="request-row__student">
                    <strong><?= e($submission['student_name']) ?></strong>
                  </div>
                  <div class="request-row__topic">
                    <?= e($submission['class_title']) ?>
                  </div>
                  <div class="request-row__contact">
                    <?= e($submission['task_title']) ?> · Балл: <?= (int)$submission['score'] ?>
                  </div>
                  <form class="request-row__status" action="<?= HOST ?>admin/submissions/<?= (int)$submission['id'] ?>" method="post">
                    <input type="hidden" name="return_status" value="<?= e($activeStatus) ?>">
                    <button
                      class="status-toggle <?= empty($submission['reviewed_at']) ? 'status-toggle--active' : '' ?>"
                      type="submit"
                      name="status"
                      value="new"
                      <?= empty($submission['reviewed_at']) ? 'disabled aria-pressed="true"' : 'aria-pressed="false"' ?>
                    >
                      Новое
                    </button>
                    <button
                      class="status-toggle <?= !empty($submission['reviewed_at']) ? 'status-toggle--active' : '' ?>"
                      type="submit"
                      name="status"
                      value="processed"
                      <?= !empty($submission['reviewed_at']) ? 'disabled aria-pressed="true"' : 'aria-pressed="false"' ?>
                    >
                      Обработано
                    </button>
                  </form>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>
      </section>
<?php include ROOT . 'templates/partials/admin-footer.tpl'; ?>
