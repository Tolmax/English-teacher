<?php
$pageTitle = 'Заявки на допзанятия — English Teacher';
$activeNav = 'extra-lessons';
$activeStatus = $activeStatus ?? 'new';
$statusTitle = $activeStatus === 'processed' ? 'Обработанные заявки' : 'Новые заявки';
include ROOT . 'templates/partials/admin-header.tpl';
?>
      <section class="stack" aria-labelledby="extra-requests-title">
        <div class="section__header section__header--inline">
          <div>
            <h1 id="extra-requests-title">Заявки на допзанятия</h1>
            <p class="section__lead">Короткий список заявок: откройте строку для деталей или смените статус сразу.</p>
          </div>
          <div class="admin-filter" aria-label="Фильтр заявок">
            <a class="button button--small <?= $activeStatus === 'new' ? 'button--primary' : 'button--secondary' ?>" href="<?= HOST ?>admin/extra-lessons?status=new">Новые</a>
            <a class="button button--small <?= $activeStatus === 'processed' ? 'button--primary' : 'button--secondary' ?>" href="<?= HOST ?>admin/extra-lessons?status=processed">Обработанные</a>
          </div>
        </div>

        <?php if (!empty($flash)): ?>
          <div class="alert alert--success" role="status"><?= e($flash) ?></div>
        <?php endif; ?>

        <section class="card" aria-labelledby="extra-requests-list-title">
          <div class="section__header section__header--inline">
            <h2 id="extra-requests-list-title"><?= e($statusTitle) ?></h2>
            <span class="badge"><?= count($requests) ?></span>
          </div>

          <?php if (empty($requests)): ?>
            <p class="card__text">В этом списке пока нет заявок.</p>
          <?php else: ?>
            <div class="request-list" role="list">
              <?php foreach ($requests as $request): ?>
                <article class="request-row" role="listitem" tabindex="0" data-row-href="<?= HOST ?>admin/extra-lessons/<?= (int)$request['id'] ?>">
                  <div class="request-row__student">
                    <strong><?= e($request['student_name']) ?></strong>
                  </div>
                  <div class="request-row__topic">
                    <?= e($request['topic']) ?>
                  </div>
                  <div class="request-row__contact">
                    <?= e($request['student_contact']) ?>
                  </div>
                  <form class="request-row__status" action="<?= HOST ?>admin/extra-lessons/<?= (int)$request['id'] ?>" method="post">
                    <input type="hidden" name="return_status" value="<?= e($activeStatus) ?>">
                    <button
                      class="status-toggle <?= $request['status'] === 'new' ? 'status-toggle--active' : '' ?>"
                      type="submit"
                      name="status"
                      value="new"
                      <?= $request['status'] === 'new' ? 'disabled aria-pressed="true"' : 'aria-pressed="false"' ?>
                    >
                      Новая
                    </button>
                    <button
                      class="status-toggle <?= $request['status'] === 'processed' ? 'status-toggle--active' : '' ?>"
                      type="submit"
                      name="status"
                      value="processed"
                      <?= $request['status'] === 'processed' ? 'disabled aria-pressed="true"' : 'aria-pressed="false"' ?>
                    >
                      Обработана
                    </button>
                  </form>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>
      </section>
<?php include ROOT . 'templates/partials/admin-footer.tpl'; ?>
