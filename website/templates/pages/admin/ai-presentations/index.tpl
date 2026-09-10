<?php
$pageTitle = 'ИИ-презентации — English Teacher';
$activeNav = 'ai-presentations';
include ROOT . 'templates/partials/admin-header.tpl';
?>
      <section class="stack" aria-labelledby="ai-presentations-title">
        <div class="section__header">
          <h1 id="ai-presentations-title">ИИ-презентации</h1>
          <p class="section__lead">Словарные презентации можно собрать через ИИ или загрузить готовым PPTX-файлом из локального генератора.</p>
        </div>

        <?php if (!empty($flash)): ?>
          <div class="alert alert--success" role="status"><?= e($flash) ?></div>
        <?php endif; ?>

        <?php if (!empty($errorFlash)): ?>
          <div class="alert alert--warning" role="alert"><?= e($errorFlash) ?></div>
        <?php endif; ?>

        <section class="card" aria-labelledby="ai-presentations-list-title">
          <div class="section__header section__header--inline">
            <div>
              <h2 id="ai-presentations-list-title">Список презентаций</h2>
              <p class="section__lead">Здесь можно создать презентацию, опубликовать или скрыть связанную колоду карточек и удалить весь комплект.</p>
            </div>
            <a class="button button--primary" href="<?= HOST ?>admin/ai-presentations/create">Новая презентация</a>
          </div>

          <div class="table-wrap">
            <table class="table">
              <caption>ИИ-презентации слов</caption>
              <thead>
                <tr>
                  <th scope="col">Название</th>
                  <th scope="col">Класс</th>
                  <th scope="col">Содержимое</th>
                  <th scope="col">Статус</th>
                  <th scope="col">Обновлена</th>
                  <th scope="col">Действия</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($presentations)): ?>
                  <tr>
                    <td colspan="6">Презентаций пока нет. Создайте первую презентацию из английских слов или загрузите готовый PPTX.</td>
                  </tr>
                <?php endif; ?>
                <?php foreach ($presentations as $presentation): ?>
                  <?php
                    $cards = aiWordPresentationCards($presentation);
                    $wordsCount = aiWordPresentationWordsCount($presentation);
                    $hasPptx = !empty($presentation['pptx_file_id']);
                    $isUploadedPptx = $hasPptx && empty($cards) && $wordsCount === 0;
                  ?>
                  <tr>
                    <th scope="row"><?= e($presentation['title']) ?></th>
                    <td><?= e($presentation['class_title']) ?></td>
                    <td>
                      <?php if ($isUploadedPptx): ?>
                        <span class="badge">Готовый PPTX</span>
                      <?php else: ?>
                        <span class="badge"><?= (int)$wordsCount ?> слов</span>
                      <?php endif; ?>
                    </td>
                    <td><span class="badge"><?= e(aiWordPresentationStatusLabel((string)$presentation['status'])) ?></span></td>
                    <td><?= e($presentation['updated_at']) ?></td>
                    <td>
                      <div class="cluster">
                        <a href="<?= HOST ?>admin/ai-presentations/<?= (int)$presentation['id'] ?>/edit">Редактировать</a>
                        <?php if (!empty($cards) || $hasPptx): ?>
                          <a href="<?= HOST ?>admin/ai-presentations/<?= (int)$presentation['id'] ?>/show">Показать</a>
                        <?php endif; ?>
                        <?php if ($hasPptx): ?>
                          <a href="<?= HOST ?>admin/ai-presentations/<?= (int)$presentation['id'] ?>/download">Скачать</a>
                        <?php endif; ?>
                        <form action="<?= HOST ?>admin/ai-presentations/<?= (int)$presentation['id'] ?>/delete" method="post" data-confirm="Удалить презентацию, материал, PPTX и картинки?">
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
