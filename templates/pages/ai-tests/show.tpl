<?php
$pageTitle = 'Тест — English Teacher';
$pageDescription = 'Интерактивный тест по английскому языку с отправкой результата учителю.';
include ROOT . 'templates/partials/header.tpl';
?>
  <main>
    <section class="page-hero" aria-labelledby="ai-test-title">
      <div class="container page-hero__inner">
        <div class="page-hero__content">
          <h1 id="ai-test-title">Тест</h1>
          <p class="page-hero__text"><?= e($test['description'] ?: 'Ответь на вопросы и отправь результат учителю.') ?></p>
        </div>
        <div class="page-hero__media"><?= (int)count($questions) ?> вопросов</div>
      </div>
    </section>

    <section class="section" aria-labelledby="ai-test-form-title">
      <div class="container lesson-layout">
        <article class="card ai-test" data-ai-test>
          <h2 id="ai-test-form-title">Мини-тест</h2>

          <?php if (!empty($flash)): ?>
            <div class="alert alert--success ai-test__result" role="status">
              <?= e($flash) ?>
              <?php if ((int)($submitted ?? 0) > 0): ?>
                <span class="badge">Отправка №<?= (int)$submitted ?></span>
              <?php endif; ?>
              <div class="card__actions">
                <a class="button button--secondary button--small" href="<?= HOST ?>ai-tests/<?= e(rawurlencode($test['slug'])) ?>">Пройти ещё раз</a>
                <a class="button button--secondary button--small" href="<?= HOST ?>class/<?= e(rawurlencode($test['class_title'])) ?>">Уйти на страницу класса</a>
              </div>
            </div>
          <?php endif; ?>

          <?php if (empty($questions)): ?>
            <div class="alert alert--warning" role="status">В этом тесте пока нет вопросов.</div>
          <?php else: ?>
            <form class="form" action="<?= HOST ?>ai-tests/<?= e(rawurlencode($test['slug'])) ?>" method="post" novalidate>
              <div class="form__field">
                <label class="form__label" for="student_name">Имя и фамилия</label>
                <input class="input" type="text" id="student_name" name="student_name" value="<?= e($old['student_name'] ?? '') ?>" autocomplete="name" required aria-describedby="student-name-error" aria-invalid="<?= !empty($errors['student_name']) ? 'true' : 'false' ?>">
                <?php if (!empty($errors['student_name'])): ?>
                  <span class="form__error" id="student-name-error" role="alert"><?= e($errors['student_name']) ?></span>
                <?php endif; ?>
              </div>

              <?php foreach ($questions as $index => $question): ?>
                <?php $questionId = (int)$question['id']; ?>
                <fieldset class="ai-test__question" aria-describedby="answers-<?= $questionId ?>-error">
                  <legend>Вопрос <?= (int)$index + 1 ?> из <?= (int)count($questions) ?></legend>
                  <p><strong><?= e($question['question_text']) ?></strong></p>
                  <div class="ai-test__answers">
                    <?php foreach ($question['options'] as $option): ?>
                      <?php $optionId = (int)$option['id']; ?>
                      <label class="ai-test__option" for="answer-<?= $questionId ?>-<?= $optionId ?>" data-ai-test-option data-correct="<?= (int)$option['is_correct'] === 1 ? '1' : '0' ?>">
                        <input id="answer-<?= $questionId ?>-<?= $optionId ?>" type="radio" name="answers[<?= $questionId ?>]" value="<?= $optionId ?>"<?= isset($old['answers'][$questionId]) && (int)$old['answers'][$questionId] === $optionId ? ' checked' : '' ?>>
                        <span><?= e($option['option_text']) ?></span>
                        <span class="ai-test__feedback" data-ai-test-feedback></span>
                      </label>
                    <?php endforeach; ?>
                  </div>
                  <?php if (!empty($errors['answers_' . $questionId])): ?>
                    <span class="form__error" id="answers-<?= $questionId ?>-error" role="alert"><?= e($errors['answers_' . $questionId]) ?></span>
                  <?php endif; ?>
                </fieldset>
              <?php endforeach; ?>

              <div class="card__actions">
                <button class="button button--primary" type="submit">Отправить результат учителю</button>
                <a class="button button--secondary" href="<?= HOST ?>class/<?= e(rawurlencode($test['class_title'])) ?>">Вернуться к классу</a>
              </div>
            </form>
          <?php endif; ?>
        </article>

        <aside class="card">
          <h2>Что увидит учитель</h2>
          <ul class="feature-list">
            <li class="feature-list__item"><span class="feature-list__marker">1</span><span>Имя ученика.</span></li>
            <li class="feature-list__item"><span class="feature-list__marker">2</span><span>Баллы и количество вопросов.</span></li>
            <li class="feature-list__item"><span class="feature-list__marker">3</span><span>Подробности по выбранным ответам.</span></li>
          </ul>
        </aside>
      </div>
    </section>
  </main>
<?php include ROOT . 'templates/partials/footer.tpl'; ?>
