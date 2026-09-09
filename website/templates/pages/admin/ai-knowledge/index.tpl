<?php
$pageTitle = 'База знаний — English Teacher';
$activeNav = 'ai-knowledge';
$sourceTypes = [
    '' => 'Все типы',
    'text' => 'Текст',
    'file' => 'Файл',
];
$statuses = [
    '' => 'Все статусы',
    'active' => 'Активен',
    'draft' => 'Черновик',
    'archived' => 'В архиве',
];
$libraryViews = [
    'active' => 'Актуальные',
    'archive' => 'Архив',
    'all' => 'Все',
];
$filters = $filters ?? [];
$categories = $categories ?? [];
include ROOT . 'templates/partials/admin-header.tpl';
?>
      <section class="stack" aria-labelledby="ai-knowledge-title">
        <div class="section__header">
          <h1 id="ai-knowledge-title">База знаний</h1>
          <p class="section__lead">Храните учебники, конспекты, свои объяснения и примеры, которые позже можно прикреплять к ИИ-материалам.</p>
        </div>

        <?php if (!empty($flash)): ?>
          <div class="alert alert--success" role="status"><?= e($flash) ?></div>
        <?php endif; ?>

        <?php if (!empty($errorFlash)): ?>
          <div class="alert alert--warning" role="alert"><?= e($errorFlash) ?></div>
        <?php endif; ?>

        <div class="card__actions">
          <a class="button button--primary" href="<?= HOST ?>admin/ai-knowledge/create">Добавить источник</a>
        </div>

        <section class="card" aria-labelledby="ai-knowledge-filter-title">
          <h2 id="ai-knowledge-filter-title">Поиск и фильтры</h2>
          <form class="form" action="<?= HOST ?>admin/ai-knowledge" method="get">
            <div class="form__field">
              <label class="form__label" for="search">Поиск</label>
              <input class="input" type="search" id="search" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Название, описание, тег или текст источника">
            </div>

            <div class="form__row">
              <div class="form__field">
                <label class="form__label" for="library_view">Режим</label>
                <select class="select" id="library_view" name="library_view">
                  <?php foreach ($libraryViews as $value => $label): ?>
                    <option value="<?= e($value) ?>"<?= ($filters['library_view'] ?? 'active') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form__field">
                <label class="form__label" for="source_type">Тип</label>
                <select class="select" id="source_type" name="source_type">
                  <?php foreach ($sourceTypes as $value => $label): ?>
                    <option value="<?= e($value) ?>"<?= ($filters['source_type'] ?? '') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form__field">
                <label class="form__label" for="category_id">Категория</label>
                <select class="select" id="category_id" name="category_id">
                  <option value="0">Все категории</option>
                  <?php foreach ($categories as $category): ?>
                    <option value="<?= (int)$category['id'] ?>"<?= (int)($filters['category_id'] ?? 0) === (int)$category['id'] ? ' selected' : '' ?>><?= e($category['title']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form__field">
                <label class="form__label" for="status">Статус</label>
                <select class="select" id="status" name="status">
                  <?php foreach ($statuses as $value => $label): ?>
                    <option value="<?= e($value) ?>"<?= ($filters['status'] ?? '') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="card__actions">
              <button class="button button--secondary" type="submit">Показать</button>
              <a class="button button--ghost" href="<?= HOST ?>admin/ai-knowledge">Сбросить</a>
            </div>
          </form>
        </section>

        <section class="card" aria-labelledby="ai-knowledge-list-title">
          <h2 id="ai-knowledge-list-title">Источники</h2>
          <div class="table-wrap">
            <table class="table">
              <caption>Источники базы знаний для будущего использования при генерации ИИ-материалов</caption>
              <thead>
                <tr>
                  <th scope="col">Источник</th>
                  <th scope="col">Категория</th>
                  <th scope="col">Тип</th>
                  <th scope="col">Файл</th>
                  <th scope="col">Статус</th>
                  <th scope="col">Действия</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($sources)): ?>
                  <tr><td colspan="6">Источники базы знаний не найдены.</td></tr>
                <?php endif; ?>
                <?php foreach ($sources as $source): ?>
                  <?php $isArchived = !empty($source['archived_at']) || ($source['status'] ?? '') === 'archived'; ?>
                  <tr>
                    <th scope="row">
                      <?= e($source['title']) ?>
                      <?php if (!empty($source['description'])): ?>
                        <p class="card__text"><?= e($source['description']) ?></p>
                      <?php endif; ?>
                      <?php if (!empty($source['tags'])): ?>
                        <p class="card__text">Теги: <?= e($source['tags']) ?></p>
                      <?php endif; ?>
                    </th>
                    <td>
                      <?php if (!empty($source['category_title'])): ?>
                        <span class="badge"><?= e($source['category_title']) ?></span>
                      <?php else: ?>
                        <span class="badge">Без категории</span>
                      <?php endif; ?>
                    </td>
                    <td><?= e(aiKnowledgeSourceTypeLabel($source['source_type'])) ?></td>
                    <td>
                      <?php if (!empty($source['stored_name'])): ?>
                        <a href="<?= HOST . e($source['storage_path']) ?>"><?= e($source['original_name']) ?></a>
                        <p class="card__text"><?= e($source['mime_type']) ?> · <?= (int)ceil((int)$source['file_size'] / 1024) ?> КБ</p>
                      <?php else: ?>
                        <span class="badge">Без файла</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <span class="badge"><?= e(aiKnowledgeSourceStatusLabel($source['status'])) ?></span>
                      <?php if ($isArchived && !empty($source['archived_at'])): ?>
                        <p class="card__text">В архиве: <?= e($source['archived_at']) ?></p>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="cluster">
                        <a href="<?= HOST ?>admin/ai-knowledge/<?= (int)$source['id'] ?>">Просмотр</a>
                        <a href="<?= HOST ?>admin/ai-knowledge/<?= (int)$source['id'] ?>/edit">Редактировать</a>
                        <?php if ($isArchived): ?>
                          <form action="<?= HOST ?>admin/ai-knowledge/<?= (int)$source['id'] ?>/restore" method="post">
                            <button class="button button--link" type="submit">Восстановить</button>
                          </form>
                        <?php else: ?>
                          <form action="<?= HOST ?>admin/ai-knowledge/<?= (int)$source['id'] ?>/archive" method="post">
                            <button class="button button--link" type="submit">В архив</button>
                          </form>
                        <?php endif; ?>
                        <form action="<?= HOST ?>admin/ai-knowledge/<?= (int)$source['id'] ?>/delete" method="post" data-confirm="Удалить этот источник базы знаний?">
                          <button class="button button--link" type="submit">Удалить</button>
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
