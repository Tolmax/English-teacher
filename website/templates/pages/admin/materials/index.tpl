<?php
$pageTitle = 'Материалы — English Teacher';
$activeNav = 'materials';
$typeOptions = ['lesson' => 'Урок', 'homework' => 'Домашка', 'announcement' => 'Объявление'];
$archiveOptions = ['active' => 'Текущие', 'archive' => 'Архив', 'all' => 'Все'];
include ROOT . 'templates/partials/admin-header.tpl';
?>
      <section class="stack" aria-labelledby="materials-title">
        <div class="section__header">
          <h1 id="materials-title">Материалы</h1>
          <p class="section__lead">Уроки, домашка и объявления для страниц классов. Презентации находятся в отдельном разделе.</p>
        </div>

        <?php if (!empty($flash)): ?>
          <div class="alert alert--success" role="status"><?= e($flash) ?></div>
        <?php endif; ?>

        <div class="card__actions">
          <a class="button button--primary" href="<?= HOST ?>admin/materials/create">Добавить материал</a>
        </div>

        <section class="card" aria-labelledby="materials-filters-title">
          <h2 id="materials-filters-title">Фильтры</h2>
          <form class="form" action="<?= HOST ?>admin/materials" method="get">
            <div class="form__row">
              <div class="form__field">
                <label class="form__label" for="filter_class_id">Класс</label>
                <select class="select" id="filter_class_id" name="class_id">
                  <option value="0">Все классы</option>
                  <?php foreach ($classes as $class): ?>
                    <option value="<?= (int)$class['id'] ?>"<?= (int)($filters['class_id'] ?? 0) === (int)$class['id'] ? ' selected' : '' ?>><?= e($class['title']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form__field">
                <label class="form__label" for="filter_type">Тип</label>
                <select class="select" id="filter_type" name="type">
                  <option value="">Все типы</option>
                  <?php foreach ($typeOptions as $value => $label): ?>
                    <option value="<?= e($value) ?>"<?= ($filters['type'] ?? '') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form__field">
                <label class="form__label" for="filter_archive">Показать</label>
                <select class="select" id="filter_archive" name="archive">
                  <?php foreach ($archiveOptions as $value => $label): ?>
                    <option value="<?= e($value) ?>"<?= ($filters['archive'] ?? 'active') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="card__actions">
              <button class="button button--secondary" type="submit">Показать</button>
              <a class="button button--ghost" href="<?= HOST ?>admin/materials">Сбросить</a>
            </div>
          </form>
        </section>

        <section class="card" aria-labelledby="materials-list-title">
          <h2 id="materials-list-title">Список материалов</h2>
          <div class="table-wrap">
            <table class="table">
              <caption>Материалы для классов</caption>
              <thead>
                <tr>
                  <th scope="col">Материал</th>
                  <th scope="col">Класс</th>
                  <th scope="col">Тип</th>
                  <th scope="col">Дата урока</th>
                  <th scope="col">Файлы</th>
                  <th scope="col">Статус</th>
                  <th scope="col">Действия</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($materials)): ?>
                  <tr><td colspan="7">Материалы не найдены.</td></tr>
                <?php endif; ?>
                <?php foreach ($materials as $material): ?>
                  <tr>
                    <th scope="row">
                      <?= e($material['title']) ?>
                      <?php if (materialIsArchived($material)): ?>
                        <span class="badge">Архив</span>
                      <?php endif; ?>
                    </th>
                    <td><?= e($material['class_title']) ?></td>
                    <td><?= e(materialTypeLabel((string)$material['type'])) ?></td>
                    <td><?= e((string)($material['deadline_at'] ?? '')) ?></td>
                    <td><?= (int)$material['files_count'] ?></td>
                    <td><span class="badge"><?= e(materialPublicationLabel($material)) ?></span></td>
                    <td>
                      <div class="cluster">
                        <a href="<?= HOST ?>admin/materials/<?= (int)$material['id'] ?>/edit">Редактировать</a>
                        <form action="<?= HOST ?>admin/materials/<?= (int)$material['id'] ?>/delete" method="post" data-confirm="Удалить материал и все прикреплённые файлы?">
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
