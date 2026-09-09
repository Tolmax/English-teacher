<?php
$isEdit = ($mode ?? '') === 'edit';
$pageTitle = ($isEdit ? 'Редактировать источник' : 'Новый источник') . ' — English Teacher';
$activeNav = 'ai-knowledge';
$actionUrl = $isEdit ? HOST . 'admin/ai-knowledge/' . (int)$source['id'] . '/edit' : HOST . 'admin/ai-knowledge/create';
$sourceTypes = [
    'text' => 'Текст',
    'file' => 'Файл',
];
$statuses = [
    'active' => 'Активен',
    'draft' => 'Черновик',
    'archived' => 'В архиве',
];
$categories = $categories ?? [];
include ROOT . 'templates/partials/admin-header.tpl';
?>
      <section class="stack" aria-labelledby="ai-knowledge-form-title">
        <div class="section__header">
          <h1 id="ai-knowledge-form-title"><?= $isEdit ? 'Редактировать источник' : 'Новый источник базы знаний' ?></h1>
          <p class="section__lead">Добавьте учебный текст или файл. Источник хранится локально и может быть прикреплён к ИИ-материалу.</p>
        </div>

        <?php if (!empty($fileError)): ?>
          <div class="alert alert--warning" role="alert"><?= e($fileError) ?></div>
        <?php endif; ?>

        <form class="form card" action="<?= $actionUrl ?>" method="post" enctype="multipart/form-data" novalidate>
          <div class="form__field">
            <label class="form__label" for="title">Название <span aria-hidden="true">*</span></label>
            <input class="input" type="text" id="title" name="title" value="<?= e($old['title'] ?? '') ?>" required aria-describedby="title-error" aria-invalid="<?= !empty($errors['title']) ? 'true' : 'false' ?>">
            <?php if (!empty($errors['title'])): ?>
              <span class="form__error" id="title-error" role="alert"><?= e($errors['title']) ?></span>
            <?php endif; ?>
          </div>

          <div class="form__row">
            <div class="form__field">
              <label class="form__label" for="category_id">Категория</label>
              <select class="select" id="category_id" name="category_id" aria-describedby="category-id-error" aria-invalid="<?= !empty($errors['category_id']) ? 'true' : 'false' ?>">
                <option value="0">Без категории</option>
                <?php foreach ($categories as $category): ?>
                  <option value="<?= (int)$category['id'] ?>"<?= (int)($old['category_id'] ?? 0) === (int)$category['id'] ? ' selected' : '' ?>><?= e($category['title']) ?></option>
                <?php endforeach; ?>
              </select>
              <?php if (!empty($errors['category_id'])): ?>
                <span class="form__error" id="category-id-error" role="alert"><?= e($errors['category_id']) ?></span>
              <?php endif; ?>
            </div>

            <div class="form__field">
              <label class="form__label" for="source_type">Тип <span aria-hidden="true">*</span></label>
              <select class="select" id="source_type" name="source_type" required aria-describedby="source-type-error" aria-invalid="<?= !empty($errors['source_type']) ? 'true' : 'false' ?>">
                <?php foreach ($sourceTypes as $value => $label): ?>
                  <option value="<?= e($value) ?>"<?= ($old['source_type'] ?? 'text') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
              <?php if (!empty($errors['source_type'])): ?>
                <span class="form__error" id="source-type-error" role="alert"><?= e($errors['source_type']) ?></span>
              <?php endif; ?>
            </div>

            <div class="form__field">
              <label class="form__label" for="status">Статус <span aria-hidden="true">*</span></label>
              <select class="select" id="status" name="status" required aria-describedby="status-error" aria-invalid="<?= !empty($errors['status']) ? 'true' : 'false' ?>">
                <?php foreach ($statuses as $value => $label): ?>
                  <option value="<?= e($value) ?>"<?= ($old['status'] ?? 'active') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
              <?php if (!empty($errors['status'])): ?>
                <span class="form__error" id="status-error" role="alert"><?= e($errors['status']) ?></span>
              <?php endif; ?>
            </div>
          </div>

          <div class="form__field">
            <label class="form__label" for="description">Описание</label>
            <textarea class="textarea" id="description" name="description" rows="3" aria-describedby="description-error" aria-invalid="<?= !empty($errors['description']) ? 'true' : 'false' ?>"><?= e($old['description'] ?? '') ?></textarea>
            <?php if (!empty($errors['description'])): ?>
              <span class="form__error" id="description-error" role="alert"><?= e($errors['description']) ?></span>
            <?php endif; ?>
          </div>

          <div class="form__field">
            <label class="form__label" for="tags">Теги</label>
            <input class="input" type="text" id="tags" name="tags" value="<?= e($old['tags'] ?? '') ?>" placeholder="grammar, animals, grade-5" aria-describedby="tags-error" aria-invalid="<?= !empty($errors['tags']) ? 'true' : 'false' ?>">
            <?php if (!empty($errors['tags'])): ?>
              <span class="form__error" id="tags-error" role="alert"><?= e($errors['tags']) ?></span>
            <?php endif; ?>
          </div>

          <div class="form__field">
            <label class="form__label" for="extracted_text">Текст источника</label>
            <textarea class="textarea" id="extracted_text" name="extracted_text" rows="10" aria-describedby="extracted-text-hint extracted-text-error" aria-invalid="<?= !empty($errors['extracted_text']) ? 'true' : 'false' ?>"><?= e($old['extracted_text'] ?? '') ?></textarea>
            <span class="form__hint" id="extracted-text-hint">ИИ использует именно это поле. Загрузка PDF, Word или скана автоматически не извлекает текст: вставьте сюда текст нужных уроков. Каждый заголовок — с новой строки, например «Unit 3 — School life», далее текст урока; следующий урок начинайте с «Unit 4 — …». Удобнее хранить каждый урок отдельным источником. <a href="<?= HOST ?>admin/teacher-guide#guide-textbook">Инструкция по учебникам</a>.</span>
            <?php if (!empty($errors['extracted_text'])): ?>
              <span class="form__error" id="extracted-text-error" role="alert"><?= e($errors['extracted_text']) ?></span>
            <?php endif; ?>
          </div>

          <?php if (!$isEdit): ?>
            <div class="form__field">
              <label class="form__label" for="source_file">Файл источника</label>
              <input class="input" type="file" id="source_file" name="source_file" accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain,image/jpeg,image/png,image/webp" aria-describedby="source-file-hint source-file-error">
              <span class="form__hint" id="source-file-hint">PDF, DOC, DOCX, TXT, JPG, PNG или WebP. Размер берётся из `AI_MAX_SOURCE_FILE_SIZE_MB`.</span>
              <?php if (!empty($errors['source_file'])): ?>
                <span class="form__error" id="source-file-error" role="alert"><?= e($errors['source_file']) ?></span>
              <?php endif; ?>
            </div>
          <?php elseif (!empty($source['stored_name'])): ?>
            <section class="card" aria-labelledby="ai-knowledge-file-title">
              <h2 id="ai-knowledge-file-title">Прикреплённый файл</h2>
              <p class="card__text">
                <a href="<?= HOST . e($source['storage_path']) ?>"><?= e($source['original_name']) ?></a>
              </p>
              <p class="card__text"><?= e($source['mime_type']) ?> · <?= (int)ceil((int)$source['file_size'] / 1024) ?> КБ</p>
            </section>
          <?php endif; ?>

          <div class="card__actions">
            <button class="button button--primary" type="submit">Сохранить</button>
            <a class="button button--secondary" href="<?= HOST ?>admin/ai-knowledge">Отмена</a>
          </div>
        </form>
      </section>
<?php include ROOT . 'templates/partials/admin-footer.tpl'; ?>
