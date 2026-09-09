<?php
$isEdit = ($mode ?? '') === 'edit';
$pageTitle = ($isEdit ? 'Редактировать класс' : 'Новый класс') . ' — English Teacher';
$activeNav = 'classes';
$actionUrl = $isEdit ? HOST . 'admin/classes/' . (int)$learningClass['id'] . '/edit' : HOST . 'admin/classes/create';
include ROOT . 'templates/partials/admin-header.tpl';
?>
      <section class="stack" aria-labelledby="class-form-title">
        <div class="section__header">
          <h1 id="class-form-title"><?= $isEdit ? 'Редактировать класс' : 'Новый класс' ?></h1>
          <p class="section__lead">Название и адрес используются на публичной странице класса.</p>
        </div>

        <form class="form card" action="<?= $actionUrl ?>" method="post" novalidate>
          <div class="form__row">
            <div class="form__field">
              <label class="form__label" for="title">Название класса</label>
              <input class="input" type="text" id="title" name="title" value="<?= e($old['title'] ?? '') ?>" required aria-describedby="title-error" aria-invalid="<?= !empty($errors['title']) ? 'true' : 'false' ?>">
              <?php if (!empty($errors['title'])): ?>
                <span class="form__error" id="title-error" role="alert"><?= e($errors['title']) ?></span>
              <?php endif; ?>
            </div>
            <div class="form__field">
              <label class="form__label" for="slug">Адрес страницы</label>
              <input class="input" type="text" id="slug" name="slug" value="<?= e($old['slug'] ?? '') ?>" required aria-describedby="slug-hint slug-error" aria-invalid="<?= !empty($errors['slug']) ? 'true' : 'false' ?>">
              <span class="form__hint" id="slug-hint">Например: 7b или 9v.</span>
              <?php if (!empty($errors['slug'])): ?>
                <span class="form__error" id="slug-error" role="alert"><?= e($errors['slug']) ?></span>
              <?php endif; ?>
            </div>
          </div>

          <div class="form__field">
            <label class="form__label" for="description">Краткое описание</label>
            <textarea class="textarea" id="description" name="description" rows="5"><?= e($old['description'] ?? '') ?></textarea>
          </div>

          <label class="toggle" for="is_active">
            <input class="toggle__input" type="checkbox" id="is_active" name="is_active" value="1"<?= (int)($old['is_active'] ?? 0) === 1 ? ' checked' : '' ?>>
            <span>Показывать класс ученикам</span>
          </label>

          <div class="card__actions">
            <button class="button button--primary" type="submit">Сохранить</button>
            <a class="button button--secondary" href="<?= HOST ?>admin/classes">Отмена</a>
          </div>
        </form>
      </section>
<?php include ROOT . 'templates/partials/admin-footer.tpl'; ?>
