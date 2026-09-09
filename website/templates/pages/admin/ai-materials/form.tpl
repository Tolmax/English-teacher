<?php
$isEdit = ($mode ?? '') === 'edit';
$pageTitle = ($isEdit ? 'Редактировать ИИ-материал' : 'Новый ИИ-материал') . ' — English Teacher';
$activeNav = 'ai-materials';
$actionUrl = $isEdit ? HOST . 'admin/ai-materials/' . (int)$material['id'] . '/edit' : HOST . 'admin/ai-materials/create';
$materialTypes = [
    'worksheet' => 'Рабочий лист',
    'quiz' => 'Мини-тест',
    'lesson_plan' => 'План урока',
    'homework' => 'Домашнее задание',
    'explanation' => 'Объяснение',
];
$languages = ['ru' => 'Русский', 'en' => 'English'];
$statuses = ['draft' => 'Черновик', 'ready' => 'Готово', 'published' => 'Опубликовано', 'archived' => 'В архиве', 'error' => 'Ошибка'];
$knowledgeSources = $knowledgeSources ?? [];
$selectedKnowledgeSourceIds = array_map('intval', $selectedKnowledgeSourceIds ?? []);
include ROOT . 'templates/partials/admin-header.tpl';
?>
      <section class="stack" aria-labelledby="ai-material-form-title">
        <div class="section__header">
          <h1 id="ai-material-form-title"><?= $isEdit ? 'Редактировать ИИ-материал' : 'Новый ИИ-материал' ?></h1>
          <p class="section__lead">Заполните основу будущего материала. На странице редактирования можно запустить генерацию через OpenAI или mock-режим без API-ключа.</p>
        </div>

        <?php if (!empty($flash)): ?>
          <div class="alert alert--success" role="status"><?= e($flash) ?></div>
        <?php endif; ?>

        <?php if (!empty($errorFlash)): ?>
          <div class="alert alert--warning" role="alert"><?= e($errorFlash) ?></div>
        <?php endif; ?>

        <?php if (!empty($aiGenerationMode)): ?>
          <section class="card" aria-labelledby="ai-generation-mode-title">
            <h2 id="ai-generation-mode-title">Режим генерации</h2>
            <p class="<?= e($aiGenerationMode['alert_class']) ?>" role="status">
              <?= e($aiGenerationMode['message']) ?>
            </p>
            <p class="card__text">
              <span class="badge"><?= e($aiGenerationMode['badge']) ?></span>
              <span class="badge">Модель: <?= e($aiGenerationMode['model']) ?></span>
            </p>
            <p class="card__text"><?= e($aiGenerationMode['hint']) ?></p>
          </section>
        <?php endif; ?>

        <?php if ($isEdit): ?>
          <section class="card" aria-labelledby="ai-generation-title">
            <h2 id="ai-generation-title">Генерация материала</h2>
            <p class="card__text">Запустите генерацию после заполнения темы, типа материала и инструкции. Если ключ OpenAI не задан, будет создан mock-материал для проверки интерфейса.</p>

            <?php if (!empty($material['generation_error'])): ?>
              <div class="alert alert--warning" role="alert"><?= e($material['generation_error']) ?></div>
            <?php endif; ?>

            <?php if (!empty($material['generated_content'])): ?>
              <div class="form__field">
                <span class="form__label">Последний результат генерации</span>
                <pre class="ai-generated-preview"><?= e($material['generated_content']) ?></pre>
              </div>
            <?php endif; ?>

            <form class="card__actions" action="<?= HOST ?>admin/ai-materials/<?= (int)$material['id'] ?>/generate" method="post">
              <button class="button button--primary" type="submit">Сгенерировать материал</button>
            </form>
          </section>
        <?php endif; ?>

        <?php if ($isEdit): ?>
          <section class="card" aria-labelledby="ai-publication-title">
            <h2 id="ai-publication-title">Публикация для учеников</h2>
            <p class="card__text">
              Текущий статус: <span class="badge"><?= e(aiTeachingMaterialStatusLabel($material['status'])) ?></span>
              <?php if (!empty($material['published_at'])): ?>
                опубликован <?= e($material['published_at']) ?>
              <?php else: ?>
                не опубликован
              <?php endif; ?>
            </p>
            <p class="card__text">Перед публикацией проверьте и сохраните поле “Текст материала”. Ученики увидят только опубликованный материал.</p>
            <?php if (trim((string)($old['edited_content'] ?? '')) === ''): ?>
              <div class="alert alert--warning" role="status">Чтобы опубликовать материал, сначала заполните текст материала.</div>
            <?php endif; ?>
            <div class="card__actions">
              <a class="button button--secondary" href="<?= HOST ?>admin/ai-materials/<?= (int)$material['id'] ?>/duplicate">Создать копию</a>
              <a class="button button--secondary" href="<?= HOST ?>admin/ai-materials/<?= (int)$material['id'] ?>/export-docx">Скачать DOCX</a>
              <form action="<?= HOST ?>admin/ai-materials/<?= (int)$material['id'] ?>/publish" method="post">
                <button class="button button--primary" type="submit">Опубликовать</button>
              </form>
              <form action="<?= HOST ?>admin/ai-materials/<?= (int)$material['id'] ?>/unpublish" method="post">
                <button class="button button--secondary" type="submit">Снять с публикации</button>
              </form>
              <?php if (($material['status'] ?? '') === 'published' && !empty($material['published_at'])): ?>
                <a class="button button--secondary" href="<?= HOST ?>ai-materials/<?= e($material['slug']) ?>">Открыть публично</a>
              <?php endif; ?>
            </div>
          </section>
        <?php endif; ?>

        <section class="card" aria-labelledby="ai-material-help-title">
          <h2 id="ai-material-help-title">Как заполнить поля</h2>
          <p class="card__text">Поля с пометкой <span class="form__required">обязательно</span> нужно заполнить перед сохранением.</p>
          <ul class="material-list">
            <li>Выберите класс и тему урока: например, <strong>7 класс, Past Simple</strong>.</li>
            <li>Укажите тип материала: рабочий лист, мини-тест, домашнее задание или объяснение.</li>
            <li>В инструкции напишите, что именно нужно получить: количество заданий, формат ответов, уровень сложности.</li>
            <li>В исходные заметки добавьте свои примеры, слова, правила или текст, на который должен опираться ИИ.</li>
            <li>Поле “Текст материала” можно оставить пустым или заполнить вручную как черновик.</li>
          </ul>
        </section>

        <form class="form card" action="<?= $actionUrl ?>" method="post" novalidate>
          <div class="form__row">
            <div class="form__field">
              <label class="form__label" for="subject">Предмет <span class="form__required">обязательно</span></label>
              <input class="input" type="text" id="subject" name="subject" value="<?= e($old['subject'] ?? '') ?>" required aria-describedby="subject-error" aria-invalid="<?= !empty($errors['subject']) ? 'true' : 'false' ?>">
              <?php if (!empty($errors['subject'])): ?>
                <span class="form__error" id="subject-error" role="alert"><?= e($errors['subject']) ?></span>
              <?php endif; ?>
            </div>

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
          </div>

          <div class="form__field">
            <label class="form__label" for="topic">Тема <span class="form__required">обязательно</span></label>
            <input class="input" type="text" id="topic" name="topic" value="<?= e($old['topic'] ?? '') ?>" required aria-describedby="topic-error" aria-invalid="<?= !empty($errors['topic']) ? 'true' : 'false' ?>">
            <?php if (!empty($errors['topic'])): ?>
              <span class="form__error" id="topic-error" role="alert"><?= e($errors['topic']) ?></span>
            <?php endif; ?>
          </div>

          <div class="form__field">
            <label class="form__label" for="title">Название</label>
            <input class="input" type="text" id="title" name="title" value="<?= e($old['title'] ?? '') ?>" aria-describedby="title-hint title-error">
            <span class="form__hint" id="title-hint">Можно оставить пустым: название соберётся из типа и темы.</span>
            <?php if (!empty($errors['title'])): ?>
              <span class="form__error" id="title-error" role="alert"><?= e($errors['title']) ?></span>
            <?php endif; ?>
          </div>

          <div class="form__row">
            <div class="form__field">
              <label class="form__label" for="material_type">Тип материала <span class="form__required">обязательно</span></label>
              <select class="select" id="material_type" name="material_type" required>
                <?php foreach ($materialTypes as $value => $label): ?>
                  <option value="<?= e($value) ?>"<?= ($old['material_type'] ?? '') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
              <?php if (!empty($errors['material_type'])): ?>
                <span class="form__error" role="alert"><?= e($errors['material_type']) ?></span>
              <?php endif; ?>
            </div>

            <div class="form__field">
              <label class="form__label" for="language">Язык <span class="form__required">обязательно</span></label>
              <select class="select" id="language" name="language" required>
                <?php foreach ($languages as $value => $label): ?>
                  <option value="<?= e($value) ?>"<?= ($old['language'] ?? '') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form__field">
            <label class="form__label" for="estimated_duration">Время выполнения</label>
            <input class="input" type="text" id="estimated_duration" name="estimated_duration" value="<?= e($old['estimated_duration'] ?? '') ?>" placeholder="например: 15 минут">
          </div>

          <div class="form__row">
            <div class="form__field">
              <label class="form__label" for="school_year">Учебный год</label>
              <input class="input" type="text" id="school_year" name="school_year" value="<?= e($old['school_year'] ?? '') ?>" placeholder="например: 2026/2027" aria-describedby="school-year-hint school-year-error" aria-invalid="<?= !empty($errors['school_year']) ? 'true' : 'false' ?>">
              <span class="form__hint" id="school-year-hint">Поможет найти материал в следующем году.</span>
              <?php if (!empty($errors['school_year'])): ?>
                <span class="form__error" id="school-year-error" role="alert"><?= e($errors['school_year']) ?></span>
              <?php endif; ?>
            </div>

            <div class="form__field">
              <label class="form__label" for="period">Период</label>
              <input class="input" type="text" id="period" name="period" value="<?= e($old['period'] ?? '') ?>" placeholder="например: 1 четверть, сентябрь, Unit 3" aria-describedby="period-hint period-error" aria-invalid="<?= !empty($errors['period']) ? 'true' : 'false' ?>">
              <span class="form__hint" id="period-hint">Можно указать четверть, месяц, раздел учебника или тему блока.</span>
              <?php if (!empty($errors['period'])): ?>
                <span class="form__error" id="period-error" role="alert"><?= e($errors['period']) ?></span>
              <?php endif; ?>
            </div>
          </div>

          <div class="form__field">
            <label class="form__label" for="tags">Теги библиотеки</label>
            <input class="input" type="text" id="tags" name="tags" value="<?= e($old['tags'] ?? '') ?>" placeholder="например: grammar, irregular verbs, контрольная" aria-describedby="tags-hint tags-error" aria-invalid="<?= !empty($errors['tags']) ? 'true' : 'false' ?>">
            <span class="form__hint" id="tags-hint">Указывайте через запятую: это заготовка для будущего поиска в библиотеке.</span>
            <?php if (!empty($errors['tags'])): ?>
              <span class="form__error" id="tags-error" role="alert"><?= e($errors['tags']) ?></span>
            <?php endif; ?>
          </div>

          <input type="hidden" name="source_material_id" value="<?= (int)($old['source_material_id'] ?? 0) ?>">

          <div class="form__field" aria-labelledby="ai-material-knowledge-title">
            <h2 id="ai-material-knowledge-title">Источники базы знаний</h2>
            <p class="card__text">Выберите учебники, заметки или файлы, на которые ИИ должен опираться. Активные неархивные источники будут учтены при следующей генерации этого материала.</p>

            <?php if (empty($knowledgeSources)): ?>
              <p class="card__text">Активных источников пока нет.</p>
              <div class="card__actions">
                <a class="button button--secondary" href="<?= HOST ?>admin/ai-knowledge/create">Добавить источник</a>
              </div>
            <?php else: ?>
              <ul class="material-list">
                <?php foreach ($knowledgeSources as $source): ?>
                  <?php $sourceId = (int)$source['id']; ?>
                  <li>
                    <label class="control" for="knowledge_source_<?= $sourceId ?>">
                      <input class="control__box" type="checkbox" id="knowledge_source_<?= $sourceId ?>" name="knowledge_source_ids[]" value="<?= $sourceId ?>"<?= in_array($sourceId, $selectedKnowledgeSourceIds, true) ? ' checked' : '' ?>>
                      <span>
                        <strong><?= e($source['title']) ?></strong>
                        <span class="badge"><?= e(aiKnowledgeSourceTypeLabel($source['source_type'])) ?></span>
                      </span>
                    </label>
                    <?php if (!empty($source['description'])): ?>
                      <p class="card__text"><?= e($source['description']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($source['tags'])): ?>
                      <p class="card__text">Теги: <?= e($source['tags']) ?></p>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>

          <div class="form__field">
            <label class="form__label" for="instructions">Инструкция для ИИ</label>
            <textarea class="textarea" id="instructions" name="instructions" rows="4"><?= e($old['instructions'] ?? '') ?></textarea>
          </div>

          <div class="form__field">
            <label class="form__label" for="source_notes">Исходные заметки учителя</label>
            <textarea class="textarea" id="source_notes" name="source_notes" rows="4"><?= e($old['source_notes'] ?? '') ?></textarea>
          </div>

          <div class="form__field">
            <label class="form__label" for="edited_content">Текст материала</label>
            <textarea class="textarea" id="edited_content" name="edited_content" rows="8"><?= e($old['edited_content'] ?? '') ?></textarea>
          </div>

          <div class="form__field">
            <label class="form__label" for="status">Статус <span class="form__required">обязательно</span></label>
            <select class="select" id="status" name="status" required>
              <?php foreach ($statuses as $value => $label): ?>
                <option value="<?= e($value) ?>"<?= ($old['status'] ?? '') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['status'])): ?>
              <span class="form__error" role="alert"><?= e($errors['status']) ?></span>
            <?php endif; ?>
          </div>

          <div class="card__actions">
            <button class="button button--primary" type="submit">Сохранить</button>
            <a class="button button--secondary" href="<?= HOST ?>admin/ai-materials">Отмена</a>
          </div>
        </form>
      </section>
<?php include ROOT . 'templates/partials/admin-footer.tpl'; ?>
