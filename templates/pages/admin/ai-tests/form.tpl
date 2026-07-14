<?php
$pageTitle = 'Редактирование ИИ-теста — English Teacher';
$activeNav = 'ai-tests';
$formQuestions = $old['questions'] ?? [];
$testTemplate = $testTemplate ?? null;
while (count($formQuestions) < 10) {
    $formQuestions[] = [
        'question_text' => '',
        'options' => ['', ''],
        'correct_option' => 0,
    ];
}
include ROOT . 'templates/partials/admin-header.tpl';
?>
      <section class="stack" aria-labelledby="ai-test-edit-title">
        <div class="section__header">
          <h1 id="ai-test-edit-title">Интерактивный тест</h1>
          <p class="section__lead">Материал: <?= e($test['material_title']) ?>. Класс: <?= e($test['class_title']) ?>.</p>
        </div>

        <?php if (!empty($flash)): ?>
          <div class="alert alert--success" role="status"><?= e($flash) ?></div>
        <?php endif; ?>

        <?php if (!empty($errors['questions'])): ?>
          <div class="alert alert--warning" role="alert"><?= e($errors['questions']) ?></div>
        <?php endif; ?>

        <div class="card__actions">
          <a class="button button--secondary" href="<?= HOST ?>admin/ai-tests">Все ИИ-тесты</a>
          <a class="button button--secondary" href="<?= HOST ?>admin/ai-tests/<?= (int)$test['id'] ?>/submissions">Результаты</a>
          <?php if (($test['status'] ?? '') === 'published'): ?>
            <a class="button button--primary" href="<?= HOST ?>ai-tests/<?= e(rawurlencode($test['slug'])) ?>">Открыть для ученика</a>
            <form action="<?= HOST ?>admin/ai-tests/<?= (int)$test['id'] ?>/unpublish" method="post">
              <button class="button button--secondary" type="submit">Снять с публикации</button>
            </form>
          <?php else: ?>
            <form action="<?= HOST ?>admin/ai-tests/<?= (int)$test['id'] ?>/publish" method="post">
              <button class="button button--primary" type="submit">Опубликовать</button>
            </form>
          <?php endif; ?>
          <form action="<?= HOST ?>admin/ai-tests/<?= (int)$test['id'] ?>/delete" method="post" data-confirm="Удалить этот ИИ-тест и его результаты?">
            <button class="button button--danger" type="submit">Удалить тест</button>
          </form>
        </div>

        <form class="form stack" action="<?= HOST ?>admin/ai-tests/<?= (int)$test['id'] ?>/edit" method="post" novalidate>
          <section class="card" aria-labelledby="ai-test-settings-title">
            <h2 id="ai-test-settings-title">Настройки</h2>
            <p class="card__text">Стандартный тест состоит из 10 вопросов. У каждого вопроса два варианта ответа и один правильный вариант.</p>
            <input type="hidden" name="title" value="Тест">
            <p class="card__text"><strong>Название:</strong> Тест</p>
            <?php if (!empty($testTemplate)): ?>
              <p class="card__text"><strong>Шаблон:</strong> <?= e($testTemplate['title']) ?></p>
              <p class="card__text"><?= e($testTemplate['description']) ?></p>
              <p class="card__text"><strong>Правила:</strong> <?= e($testTemplate['rules']) ?></p>
            <?php endif; ?>
            <div class="form__field">
              <label class="form__label" for="class_title">Класс <span aria-hidden="true">*</span></label>
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
              <label class="form__label" for="description">Короткое описание</label>
              <textarea class="textarea" id="description" name="description" rows="3"><?= e($old['description'] ?? '') ?></textarea>
            </div>
          </section>

          <?php foreach ($formQuestions as $questionIndex => $question): ?>
            <?php
            $options = array_values($question['options'] ?? []);
            $options = array_slice($options, 0, 2);
            while (count($options) < 2) {
                $options[] = '';
            }
            ?>
            <section class="card ai-test-editor" aria-labelledby="question-<?= (int)$questionIndex ?>-title">
              <h2 id="question-<?= (int)$questionIndex ?>-title">Вопрос <?= (int)$questionIndex + 1 ?></h2>
              <p class="card__text">Заполните вопрос, два варианта ответа и отметьте правильный вариант.</p>
              <div class="form__field">
                <label class="form__label" for="question-<?= (int)$questionIndex ?>">Текст вопроса</label>
                <textarea class="textarea" id="question-<?= (int)$questionIndex ?>" name="questions[<?= (int)$questionIndex ?>][question_text]" rows="3" aria-describedby="question-<?= (int)$questionIndex ?>-error" aria-invalid="<?= !empty($errors['question_' . $questionIndex]) ? 'true' : 'false' ?>"><?= e($question['question_text'] ?? '') ?></textarea>
                <?php if (!empty($errors['question_' . $questionIndex])): ?>
                  <span class="form__error" id="question-<?= (int)$questionIndex ?>-error" role="alert"><?= e($errors['question_' . $questionIndex]) ?></span>
                <?php endif; ?>
              </div>

              <div class="form__row">
                <?php foreach ($options as $optionIndex => $option): ?>
                  <div class="form__field">
                    <label class="form__label" for="question-<?= (int)$questionIndex ?>-option-<?= (int)$optionIndex ?>">Вариант <?= (int)$optionIndex + 1 ?></label>
                    <input class="input" type="text" id="question-<?= (int)$questionIndex ?>-option-<?= (int)$optionIndex ?>" name="questions[<?= (int)$questionIndex ?>][options][]" value="<?= e($option) ?>">
                  </div>
                <?php endforeach; ?>
              </div>
              <?php if (!empty($errors['options_' . $questionIndex])): ?>
                <span class="form__error" role="alert"><?= e($errors['options_' . $questionIndex]) ?></span>
              <?php endif; ?>

              <div class="form__field">
                <label class="form__label" for="question-<?= (int)$questionIndex ?>-correct">Правильный вариант</label>
                <select class="select" id="question-<?= (int)$questionIndex ?>-correct" name="questions[<?= (int)$questionIndex ?>][correct_option]">
                  <?php foreach ([0, 1] as $optionIndex): ?>
                    <option value="<?= (int)$optionIndex ?>"<?= (int)($question['correct_option'] ?? 0) === $optionIndex ? ' selected' : '' ?>>Вариант <?= (int)$optionIndex + 1 ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </section>
          <?php endforeach; ?>

          <div class="card__actions">
            <button class="button button--primary" type="submit">Сохранить тест</button>
          </div>
        </form>
      </section>
<?php include ROOT . 'templates/partials/admin-footer.tpl'; ?>
