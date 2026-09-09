<?php
$pageTitle = 'Создать копию ИИ-материала — English Teacher';
$activeNav = 'ai-materials';
$actionUrl = HOST . 'admin/ai-materials/' . (int)$material['id'] . '/duplicate';
$sourceKnowledgeSources = $sourceKnowledgeSources ?? [];
$activeSourceKnowledgeSourceIds = array_map('intval', $activeSourceKnowledgeSourceIds ?? []);
include ROOT . 'templates/partials/admin-header.tpl';
?>
      <section class="stack" aria-labelledby="ai-material-duplicate-title">
        <div class="section__header">
          <h1 id="ai-material-duplicate-title">Создать копию ИИ-материала</h1>
          <p class="section__lead">Возьмите готовый материал за основу, измените класс, тему или учебный год и сохраните новую версию. Оригинал останется без изменений.</p>
        </div>

        <?php if (!empty($flash)): ?>
          <div class="alert alert--success" role="status"><?= e($flash) ?></div>
        <?php endif; ?>

        <?php if (!empty($errorFlash)): ?>
          <div class="alert alert--warning" role="alert"><?= e($errorFlash) ?></div>
        <?php endif; ?>

        <section class="card" aria-labelledby="ai-material-source-title">
          <h2 id="ai-material-source-title">Оригинал</h2>
          <p class="card__text"><strong><?= e($material['title']) ?></strong></p>
          <p class="card__text">
            <span class="badge"><?= e($material['class_title']) ?> класс</span>
            <span class="badge"><?= e(aiTeachingMaterialTypeLabel($material['material_type'])) ?></span>
            <span class="badge"><?= e(aiTeachingMaterialStatusLabel($material['status'])) ?></span>
          </p>
          <?php if (!empty($material['topic'])): ?>
            <p class="card__text">Тема: <?= e($material['topic']) ?></p>
          <?php endif; ?>
          <?php if (!empty($material['school_year']) || !empty($material['period']) || !empty($material['tags'])): ?>
            <p class="card__text">
              <?php if (!empty($material['school_year'])): ?>
                Год: <?= e($material['school_year']) ?>.
              <?php endif; ?>
              <?php if (!empty($material['period'])): ?>
                Период: <?= e($material['period']) ?>.
              <?php endif; ?>
              <?php if (!empty($material['tags'])): ?>
                Теги: <?= e($material['tags']) ?>.
              <?php endif; ?>
            </p>
          <?php endif; ?>
        </section>

        <section class="card" aria-labelledby="ai-material-copy-help-title">
          <h2 id="ai-material-copy-help-title">Что будет скопировано</h2>
          <p class="card__text">Новая запись получит текст материала, инструкцию для ИИ, исходные заметки, тип, уровень и связанные файлы оригинала.</p>
          <p class="card__text">Публикация не копируется: новая версия будет доступна только в админке, пока вы не проверите её и не нажмёте “Опубликовать”.</p>
          <?php if (empty($sourceKnowledgeSources)): ?>
            <p class="card__text">У оригинала нет источников базы знаний.</p>
          <?php else: ?>
            <p class="card__text">Активные источники базы знаний будут привязаны к копии.</p>
            <ul class="material-list">
              <?php foreach ($sourceKnowledgeSources as $source): ?>
                <?php $isSourceActive = in_array((int)$source['id'], $activeSourceKnowledgeSourceIds, true); ?>
                <li>
                  <strong><?= e($source['title']) ?></strong>
                  <span class="badge"><?= e(aiKnowledgeSourceTypeLabel($source['source_type'])) ?></span>
                  <span class="badge"><?= $isSourceActive ? 'будет скопирован' : 'архивный, не будет скопирован' ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </section>

        <form class="form card" action="<?= $actionUrl ?>" method="post" novalidate>
          <div class="form__field">
            <label class="form__label" for="title">Название копии</label>
            <input class="input" type="text" id="title" name="title" value="<?= e($old['title'] ?? '') ?>" aria-describedby="title-hint title-error">
            <span class="form__hint" id="title-hint">Можно оставить пустым: название соберётся из типа и темы.</span>
            <?php if (!empty($errors['title'])): ?>
              <span class="form__error" id="title-error" role="alert"><?= e($errors['title']) ?></span>
            <?php endif; ?>
          </div>

          <div class="form__row">
            <div class="form__field">
              <label class="form__label" for="class_title">Класс <span class="form__required">обязательно</span></label>
              <select class="select" id="class_title" name="class_title" required aria-describedby="class-title-error" aria-invalid="<?= !empty($errors['class_title']) ? 'true' : 'false' ?>">
                <option value="">Выберите класс</option>
                <?php foreach ($classes as $class): ?>
                  <option value="<?= e($class['title']) ?>"<?= ($old['class_title'] ?? '') === $class['title'] ? ' selected' : '' ?>><?= e($class['title']) ?> класс</option>
                <?php endforeach; ?>
              </select>
              <?php if (!empty($errors['class_title'])): ?>
                <span class="form__error" id="class-title-error" role="alert"><?= e($errors['class_title']) ?></span>
              <?php endif; ?>
            </div>

            <div class="form__field">
              <label class="form__label" for="topic">Тема <span class="form__required">обязательно</span></label>
              <input class="input" type="text" id="topic" name="topic" value="<?= e($old['topic'] ?? '') ?>" required aria-describedby="topic-error" aria-invalid="<?= !empty($errors['topic']) ? 'true' : 'false' ?>">
              <?php if (!empty($errors['topic'])): ?>
                <span class="form__error" id="topic-error" role="alert"><?= e($errors['topic']) ?></span>
              <?php endif; ?>
            </div>
          </div>

          <div class="form__row">
            <div class="form__field">
              <label class="form__label" for="school_year">Учебный год</label>
              <input class="input" type="text" id="school_year" name="school_year" value="<?= e($old['school_year'] ?? '') ?>" placeholder="например: 2026/2027" aria-describedby="school-year-hint school-year-error" aria-invalid="<?= !empty($errors['school_year']) ? 'true' : 'false' ?>">
              <span class="form__hint" id="school-year-hint">Укажите новый год или сезон, для которого готовится копия.</span>
              <?php if (!empty($errors['school_year'])): ?>
                <span class="form__error" id="school-year-error" role="alert"><?= e($errors['school_year']) ?></span>
              <?php endif; ?>
            </div>

            <div class="form__field">
              <label class="form__label" for="period">Период</label>
              <input class="input" type="text" id="period" name="period" value="<?= e($old['period'] ?? '') ?>" placeholder="например: 1 четверть, Unit 3" aria-describedby="period-hint period-error" aria-invalid="<?= !empty($errors['period']) ? 'true' : 'false' ?>">
              <span class="form__hint" id="period-hint">Можно указать четверть, месяц или раздел учебника.</span>
              <?php if (!empty($errors['period'])): ?>
                <span class="form__error" id="period-error" role="alert"><?= e($errors['period']) ?></span>
              <?php endif; ?>
            </div>
          </div>

          <div class="form__field">
            <label class="form__label" for="tags">Теги библиотеки</label>
            <input class="input" type="text" id="tags" name="tags" value="<?= e($old['tags'] ?? '') ?>" placeholder="например: grammar, повторение, контрольная" aria-describedby="tags-hint tags-error" aria-invalid="<?= !empty($errors['tags']) ? 'true' : 'false' ?>">
            <span class="form__hint" id="tags-hint">Теги помогают быстрее найти копию в библиотеке.</span>
            <?php if (!empty($errors['tags'])): ?>
              <span class="form__error" id="tags-error" role="alert"><?= e($errors['tags']) ?></span>
            <?php endif; ?>
          </div>

          <div class="card__actions">
            <button class="button button--primary" type="submit">Создать копию</button>
            <a class="button button--secondary" href="<?= HOST ?>admin/ai-materials">Отмена</a>
            <a class="button button--secondary" href="<?= HOST ?>admin/ai-materials/<?= (int)$material['id'] ?>/edit">Открыть оригинал</a>
          </div>
        </form>
      </section>
<?php include ROOT . 'templates/partials/admin-footer.tpl'; ?>
