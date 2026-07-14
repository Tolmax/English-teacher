<?php
$pageTitle = 'ИИ-тесты — English Teacher';
$activeNav = 'ai-tests';
include ROOT . 'templates/partials/admin-header.tpl';
?>
      <section class="stack" aria-labelledby="ai-tests-title">
        <div class="section__header">
          <h1 id="ai-tests-title">ИИ-тесты</h1>
          <p class="section__lead">Интерактивные тесты создаются из ИИ-материалов и публикуются на странице выбранного класса.</p>
        </div>

        <?php if (!empty($flash)): ?>
          <div class="alert alert--success" role="status"><?= e($flash) ?></div>
        <?php endif; ?>

        <?php if (!empty($errorFlash)): ?>
          <div class="alert alert--warning" role="alert"><?= e($errorFlash) ?></div>
        <?php endif; ?>

        <section class="card" aria-labelledby="ai-tests-list-title">
          <h2 id="ai-tests-list-title">Список тестов</h2>
          <div class="table-wrap">
            <table class="table">
              <caption>Интерактивные тесты из ИИ-материалов</caption>
              <thead>
                <tr>
                  <th scope="col">Тест</th>
                  <th scope="col">Класс</th>
                  <th scope="col">Материал</th>
                  <th scope="col">Статус</th>
                  <th scope="col">Результаты</th>
                  <th scope="col">Действия</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($tests)): ?>
                  <tr>
                    <td colspan="6">Тестов пока нет. Откройте “ИИ-материалы” и нажмите “Тест” у нужного материала.</td>
                  </tr>
                <?php endif; ?>
                <?php foreach ($tests as $test): ?>
                  <tr>
                    <th scope="row">
                      Тест
                      <?php if (!empty($test['description'])): ?>
                        <p class="card__text"><?= e($test['description']) ?></p>
                      <?php endif; ?>
                    </th>
                    <td><?= e($test['class_title']) ?></td>
                    <td><?= e($test['material_title']) ?></td>
                    <td><span class="badge"><?= e(aiInteractiveTestStatusLabel($test['status'])) ?></span></td>
                    <td>
                      <span class="badge"><?= (int)$test['questions_count'] ?> вопросов</span>
                      <span class="badge"><?= (int)$test['submissions_count'] ?> ответов</span>
                    </td>
                    <td>
                      <div class="cluster">
                        <a href="<?= HOST ?>admin/ai-tests/<?= (int)$test['id'] ?>/edit">Редактировать</a>
                        <a href="<?= HOST ?>admin/ai-tests/<?= (int)$test['id'] ?>/submissions">Результаты</a>
                        <?php if (($test['status'] ?? '') === 'published'): ?>
                          <a href="<?= HOST ?>ai-tests/<?= e(rawurlencode($test['slug'])) ?>">Открыть</a>
                        <?php endif; ?>
                        <form action="<?= HOST ?>admin/ai-tests/<?= (int)$test['id'] ?>/delete" method="post" data-confirm="Удалить этот ИИ-тест и его результаты?">
                          <button class="button button--danger button--small" type="submit">Удалить</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>
      </section>
<?php include ROOT . 'templates/partials/admin-footer.tpl'; ?>
