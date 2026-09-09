<?php
$isEdit = ($mode ?? '') === 'edit';
$pageTitle = ($isEdit ? 'Редактировать материал' : 'Новый материал') . ' — English Teacher';
$activeNav = 'materials';
$actionUrl = $isEdit ? HOST . 'admin/materials/' . (int)$material['id'] . '/edit' : HOST . 'admin/materials/create';
$typeOptions = ['lesson' => 'Урок', 'homework' => 'Домашка', 'announcement' => 'Объявление'];
include ROOT . 'templates/partials/admin-header.tpl';
?>
      <section class="stack" aria-labelledby="material-form-title">
        <div class="section__header">
          <h1 id="material-form-title"><?= $isEdit ? 'Редактировать материал' : 'Новый материал' ?></h1>
          <p class="section__lead">Материал появится на странице выбранного класса, если он опубликован. Когда дата урока прошла, материал автоматически считается архивным.</p>
        </div>

        <?php if (!empty($fileErrors)): ?>
          <div class="alert alert--warning" role="alert">
            <?php foreach ($fileErrors as $fileError): ?>
              <p><?= e($fileError) ?></p>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form class="form card" action="<?= $actionUrl ?>" method="post" enctype="multipart/form-data" novalidate>
          <div class="form__row">
            <div class="form__field">
              <label class="form__label" for="class_id">Класс</label>
              <select class="select" id="class_id" name="class_id" required aria-describedby="class-id-error" aria-invalid="<?= !empty($errors['class_id']) ? 'true' : 'false' ?>">
                <option value="">Выберите класс</option>
                <?php foreach ($classes as $class): ?>
                  <option value="<?= (int)$class['id'] ?>"<?= (int)($old['class_id'] ?? 0) === (int)$class['id'] ? ' selected' : '' ?>><?= e($class['title']) ?></option>
                <?php endforeach; ?>
              </select>
              <?php if (!empty($errors['class_id'])): ?>
                <span class="form__error" id="class-id-error" role="alert"><?= e($errors['class_id']) ?></span>
              <?php endif; ?>
            </div>

            <div class="form__field">
              <label class="form__label" for="type">Тип</label>
              <select class="select" id="type" name="type" required aria-describedby="type-error" aria-invalid="<?= !empty($errors['type']) ? 'true' : 'false' ?>">
                <?php foreach ($typeOptions as $value => $label): ?>
                  <option value="<?= e($value) ?>"<?= ($old['type'] ?? '') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
              <?php if (!empty($errors['type'])): ?>
                <span class="form__error" id="type-error" role="alert"><?= e($errors['type']) ?></span>
              <?php endif; ?>
            </div>
          </div>

          <div class="form__field">
            <label class="form__label" for="content">Текст материала</label>
            <textarea class="textarea" id="content" name="content" rows="8"><?= e($old['content'] ?? '') ?></textarea>
          </div>

          <div class="form__field">
            <label class="form__label" for="deadline_at">Дата урока</label>
            <input class="input" type="date" id="deadline_at" name="deadline_at" value="<?= e($old['deadline_at'] ?? '') ?>" aria-describedby="deadline-error" aria-invalid="<?= !empty($errors['deadline_at']) ? 'true' : 'false' ?>">
            <span class="form__hint">Если дата прошла, материал попадёт в архив и будет показан только при выборе архива.</span>
            <?php if (!empty($errors['deadline_at'])): ?>
              <span class="form__error" id="deadline-error" role="alert"><?= e($errors['deadline_at']) ?></span>
            <?php endif; ?>
          </div>

          <div class="form__field">
            <label class="form__label" for="files">Файлы материала</label>
            <input class="input" type="file" id="files" name="files[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.txt,application/pdf,image/jpeg,image/png,image/webp,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain">
            <span class="form__hint">Можно загрузить PDF, JPG, PNG, WebP, DOC, DOCX или TXT до 10 МБ каждый.</span>
          </div>

          <?php if ($isEdit && !empty($files)): ?>
            <section aria-labelledby="attached-files-title">
              <h2 id="attached-files-title">Прикреплённые файлы</h2>
              <ul class="material-list">
                <?php foreach ($files as $file): ?>
                  <li class="material-item">
                    <div>
                      <h3><?= e($file['original_name']) ?></h3>
                      <p class="card__text"><?= e($file['mime_type']) ?> · <?= (int)ceil((int)$file['file_size'] / 1024) ?> КБ</p>
                    </div>
                    <div class="card__actions">
                      <a class="button button--secondary button--small" href="<?= HOST ?>uploads/materials/<?= (int)$file['material_id'] ?>/<?= e($file['stored_name']) ?>">Открыть</a>
                      <button class="button button--danger button--small" type="submit" name="file_id" value="<?= (int)$file['id'] ?>" formaction="<?= HOST ?>admin/materials/<?= (int)$file['material_id'] ?>/files" formmethod="post">Удалить</button>
                    </div>
                  </li>
                <?php endforeach; ?>
              </ul>
            </section>
          <?php endif; ?>

          <label class="toggle" for="is_published">
            <input class="toggle__input" type="checkbox" id="is_published" name="is_published" value="1"<?= (int)($old['is_published'] ?? 0) === 1 ? ' checked' : '' ?>>
            <span>Опубликовать для учеников</span>
          </label>

          <div class="card__actions">
            <button class="button button--primary" type="submit">Сохранить</button>
            <a class="button button--secondary" href="<?= HOST ?>admin/materials">Отмена</a>
          </div>
        </form>
      </section>
<?php include ROOT . 'templates/partials/admin-footer.tpl'; ?>
