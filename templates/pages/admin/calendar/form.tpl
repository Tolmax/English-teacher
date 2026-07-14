<?php
$isEdit = ($mode ?? '') === 'edit';
$pageTitle = ($isEdit ? 'Редактировать запись календаря' : 'Новая запись календаря') . ' — English Teacher';
$activeNav = 'calendar';
$actionUrl = $isEdit ? HOST . 'admin/calendar/' . (int)$event['id'] . '/edit' : HOST . 'admin/calendar/create';
include ROOT . 'templates/partials/admin-header.tpl';
?>
      <section class="stack" aria-labelledby="calendar-form-title">
        <div class="section__header">
          <h1 id="calendar-form-title"><?= $isEdit ? 'Редактировать запись' : 'Новая запись календаря' ?></h1>
          <p class="section__lead">Уроки автоматически переносятся на следующие недели, если не включить “Однократно”. Домашнее задание сохраняется только для выбранной даты.</p>
        </div>

        <form class="form card" action="<?= $actionUrl ?>" method="post" novalidate>
          <div class="form__row">
            <div class="form__field">
              <label class="form__label" for="class_id">Класс</label>
              <select class="select" id="class_id" name="class_id">
                <option value="0">Все классы</option>
                <?php foreach ($classes as $class): ?>
                  <option value="<?= (int)$class['id'] ?>"<?= (int)($old['class_id'] ?? 0) === (int)$class['id'] ? ' selected' : '' ?>><?= e($class['title']) ?> класс</option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form__field">
              <label class="form__label" for="event_type">Тип <span class="form__required">*</span></label>
              <select class="select" id="event_type" name="event_type" required aria-describedby="event-type-error" aria-invalid="<?= !empty($errors['event_type']) ? 'true' : 'false' ?>">
                <?php foreach ($eventTypes as $value => $label): ?>
                  <option value="<?= e($value) ?>"<?= ($old['event_type'] ?? '') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
              <?php if (!empty($errors['event_type'])): ?>
                <span class="form__error" id="event-type-error" role="alert"><?= e($errors['event_type']) ?></span>
              <?php endif; ?>
            </div>
          </div>

          <div class="form__row">
            <div class="form__field">
              <label class="form__label" for="starts_at">Начало <span class="form__required">*</span></label>
              <input class="input" type="datetime-local" id="starts_at" name="starts_at" value="<?= e($old['starts_at'] ?? '') ?>" required aria-describedby="starts-at-error" aria-invalid="<?= !empty($errors['starts_at']) ? 'true' : 'false' ?>">
              <?php if (!empty($errors['starts_at'])): ?>
                <span class="form__error" id="starts-at-error" role="alert"><?= e($errors['starts_at']) ?></span>
              <?php endif; ?>
            </div>

            <div class="form__field">
              <label class="form__label" for="ends_at">Окончание</label>
              <input class="input" type="datetime-local" id="ends_at" name="ends_at" value="<?= e($old['ends_at'] ?? '') ?>" aria-describedby="ends-at-error" aria-invalid="<?= !empty($errors['ends_at']) ? 'true' : 'false' ?>">
              <?php if (!empty($errors['ends_at'])): ?>
                <span class="form__error" id="ends-at-error" role="alert"><?= e($errors['ends_at']) ?></span>
              <?php endif; ?>
            </div>

            <div class="form__field">
              <label class="form__label" for="lesson_number">Урок</label>
              <select class="select" id="lesson_number" name="lesson_number" aria-describedby="lesson-number-error" aria-invalid="<?= !empty($errors['lesson_number']) ? 'true' : 'false' ?>">
                <option value="0">Не урок</option>
                <?php for ($lessonNumber = 1; $lessonNumber <= 10; $lessonNumber++): ?>
                  <option value="<?= (int)$lessonNumber ?>"<?= (int)($old['lesson_number'] ?? 0) === $lessonNumber ? ' selected' : '' ?>><?= (int)$lessonNumber ?></option>
                <?php endfor; ?>
              </select>
              <?php if (!empty($errors['lesson_number'])): ?>
                <span class="form__error" id="lesson-number-error" role="alert"><?= e($errors['lesson_number']) ?></span>
              <?php endif; ?>
            </div>
          </div>

          <div class="form__field">
            <label class="form__label" for="title">Название <span class="form__required">*</span></label>
            <input class="input" type="text" id="title" name="title" value="<?= e($old['title'] ?? '') ?>" placeholder="Например: Урок английского" required aria-describedby="calendar-title-error" aria-invalid="<?= !empty($errors['title']) ? 'true' : 'false' ?>">
            <?php if (!empty($errors['title'])): ?>
              <span class="form__error" id="calendar-title-error" role="alert"><?= e($errors['title']) ?></span>
            <?php endif; ?>
          </div>

          <div class="form__field">
            <label class="form__label" for="homework_content">Домашнее задание</label>
            <textarea class="textarea" id="homework_content" name="homework_content" rows="3"><?= e($old['homework_content'] ?? '') ?></textarea>
          </div>

          <div class="form__field">
            <label class="form__label" for="material_id">Прикрепить материал</label>
            <select class="select" id="material_id" name="material_id">
              <option value="0">Без материала</option>
              <?php foreach ($materials as $material): ?>
                <option value="<?= (int)$material['id'] ?>"<?= (int)($old['material_id'] ?? 0) === (int)$material['id'] ? ' selected' : '' ?>>
                  <?= e($material['class_title']) ?> класс — <?= e($material['title']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form__field">
            <label class="form__label" for="description">Заметка учителя</label>
            <textarea class="textarea" id="description" name="description" rows="4"><?= e($old['description'] ?? '') ?></textarea>
          </div>

          <label class="toggle" for="is_published">
            <input class="toggle__input" type="checkbox" id="is_published" name="is_published" value="1"<?= (int)($old['is_published'] ?? 0) === 1 ? ' checked' : '' ?>>
            <span>Опубликовать для учеников и панели учителя</span>
          </label>

          <?php if (!$isEdit): ?>
            <label class="toggle" for="is_single">
              <input class="toggle__input" type="checkbox" id="is_single" name="is_single" value="1"<?= (int)($old['is_single'] ?? 0) === 1 ? ' checked' : '' ?>>
              <span>Однократно: создать только на выбранную дату, без еженедельных повторов</span>
            </label>
          <?php endif; ?>

          <div class="card__actions">
            <button class="button button--primary" type="submit">Сохранить</button>
            <?php if ($isEdit): ?>
              <button
                class="button button--secondary"
                type="submit"
                formaction="<?= HOST ?>admin/calendar/<?= (int)$event['id'] ?>/cancel"
                formmethod="post"
                formnovalidate
                data-confirm="Отменить урок только для этой даты?"
              >Разово</button>
              <button
                class="button button--secondary"
                type="submit"
                formaction="<?= HOST ?>admin/calendar/<?= (int)$event['id'] ?>/cancel-series"
                formmethod="post"
                formnovalidate
                data-confirm="Отменить этот урок и все будущие повторы?"
              >Навсегда</button>
              <?php if (trim((string)($event['recurrence_group'] ?? '')) !== ''): ?>
                <button
                  class="button button--danger"
                  type="submit"
                  formaction="<?= HOST ?>admin/calendar/<?= (int)$event['id'] ?>/delete-series"
                  formmethod="post"
                  formnovalidate
                  data-confirm="Удалить весь ряд уроков, включая прошлые и будущие даты?"
                >Удалить весь ряд</button>
              <?php endif; ?>
            <?php else: ?>
              <a class="button button--secondary" href="<?= HOST ?>admin/calendar">Отмена</a>
            <?php endif; ?>
          </div>
        </form>
      </section>
<?php include ROOT . 'templates/partials/admin-footer.tpl'; ?>
