<?php
$pageTitle = 'Дополнительные занятия — English Teacher';
$pageDescription = 'Форма заявки на дополнительные занятия по английскому языку для учеников.';
$formatOptions = [
    'individual' => 'Индивидуально',
    'mini_group' => 'Мини-группа',
    'before_test' => 'Перед контрольной',
];
include ROOT . 'templates/partials/header.tpl';
?>
  <main>
    <section class="page-hero page-hero--plain page-hero--extra-lessons" aria-labelledby="extra-title">
      <div class="container page-hero__inner">
        <div class="page-hero__content">
          <h1 id="extra-title">Дополнительные занятия по английскому</h1>
          <p class="page-hero__text">Если тема не усвоилась на уроке, можно разобрать её спокойно: индивидуально, в мини-группе или перед контрольной.</p>

          <fieldset class="format-picker" aria-describedby="lesson-format-error">
            <legend class="format-picker__legend">Выберите формат занятия</legend>
            <div class="cluster" aria-label="Форматы занятий">
              <?php foreach ($formatOptions as $value => $label): ?>
                <label class="format-picker__option">
                  <input class="format-picker__input" type="radio" name="lesson_format" value="<?= e($value) ?>" form="extra-lesson-form"<?= ($old['lesson_format'] ?? '') === $value ? ' checked' : '' ?>>
                  <span class="badge format-picker__badge"><?= e($label) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
            <?php if (!empty($errors['lesson_format'])): ?>
              <span class="form__error" id="lesson-format-error" role="alert"><?= e($errors['lesson_format']) ?></span>
            <?php endif; ?>
          </fieldset>
        </div>

        <aside class="card card--accent">
          <h2>Нужна помощь?</h2>

          <?php if (!empty($flash)): ?>
            <div class="alert alert--success" role="status"><?= e($flash) ?></div>
          <?php endif; ?>

          <form class="form" id="extra-lesson-form" action="<?= HOST ?>extra-lessons" method="post" novalidate>
            <div class="form__field">
              <label class="form__label" for="student_name">Имя ученика</label>
              <input class="input" id="student_name" name="student_name" type="text" value="<?= e($old['student_name'] ?? '') ?>" required aria-describedby="student-name-error" aria-invalid="<?= !empty($errors['student_name']) ? 'true' : 'false' ?>">
              <?php if (!empty($errors['student_name'])): ?>
                <span class="form__error" id="student-name-error" role="alert"><?= e($errors['student_name']) ?></span>
              <?php endif; ?>
            </div>

            <div class="form__field">
              <label class="form__label" for="student_contact">Контакт для связи</label>
              <input class="input" id="student_contact" name="student_contact" type="text" value="<?= e($old['student_contact'] ?? '') ?>" required aria-describedby="student-contact-error" aria-invalid="<?= !empty($errors['student_contact']) ? 'true' : 'false' ?>">
              <?php if (!empty($errors['student_contact'])): ?>
                <span class="form__error" id="student-contact-error" role="alert"><?= e($errors['student_contact']) ?></span>
              <?php endif; ?>
            </div>

            <div class="form__field">
              <label class="form__label" for="class_title">Класс</label>
              <select class="select" id="class_title" name="class_title">
                <option value="">Выберите класс</option>
                <?php foreach ($classes as $class): ?>
                  <option value="<?= e($class['title']) ?>"<?= ($old['class_title'] ?? '') === $class['title'] ? ' selected' : '' ?>><?= e($class['title']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form__field">
              <label class="form__label" for="topic">Что нужно разобрать</label>
              <textarea class="textarea" id="topic" name="topic" rows="3" required aria-describedby="topic-error" aria-invalid="<?= !empty($errors['topic']) ? 'true' : 'false' ?>"><?= e($old['topic'] ?? '') ?></textarea>
              <?php if (!empty($errors['topic'])): ?>
                <span class="form__error" id="topic-error" role="alert"><?= e($errors['topic']) ?></span>
              <?php endif; ?>
            </div>

            <div class="form__field">
              <label class="form__label" for="message">Комментарий</label>
              <textarea class="textarea" id="message" name="message" rows="3"><?= e($old['message'] ?? '') ?></textarea>
            </div>

            <button class="button button--primary" type="submit">Отправить заявку</button>
          </form>
        </aside>
      </div>
    </section>

    <section class="section" aria-labelledby="formats-title">
      <div class="container">
        <div class="section__header">
          <h2 id="formats-title">Форматы помощи</h2>
          <p class="section__lead">Занятия подбираются по цели ученика: подтянуть тему, подготовиться к работе или закрыть пробелы.</p>
        </div>

        <div class="card-grid">
          <article class="card">
            <h3>Разбор темы</h3>
            <p class="card__text">Короткое занятие по конкретной теме урока: времена, лексика, чтение или письмо.</p>
          </article>
          <article class="card">
            <h3>Подготовка</h3>
            <p class="card__text">Повторение перед контрольной, диктантом или самостоятельной работой.</p>
          </article>
          <article class="card">
            <h3>Практика</h3>
            <p class="card__text">Разбор грамматики, словаря и заданий, которые вызывают трудности.</p>
          </article>
        </div>
      </div>
    </section>
  </main>
<?php include ROOT . 'templates/partials/footer.tpl'; ?>
