<?php
$pageTitle = 'Карточки: ' . (string)$presentation['title'] . ' — English Teacher';
$pageDescription = 'Интерактивная колода для повторения английских слов и выражений.';
include ROOT . 'templates/partials/header.tpl';
?>
  <main>
    <section class="section flashcard-deck-page" aria-labelledby="flashcard-deck-title">
      <div class="container stack">
        <?php if (!empty($isTeacherPreview)): ?>
          <div class="alert alert--warning" role="status">
            Предпросмотр учителя: эта колода пока скрыта от учеников. Опубликовать её можно в списке «ИИ-презентации».
          </div>
        <?php endif; ?>
        <div class="section__header section__header--inline">
          <div>
            <p class="flashcard-deck__eyebrow">Тренировка слов</p>
            <h1 id="flashcard-deck-title"><?= e((string)$presentation['title']) ?></h1>
            <p class="section__lead">Назови перевод, нажми на карточку и проверь себя.</p>
          </div>
          <?php if (!empty($isTeacherPreview)): ?>
            <a class="button button--secondary" href="<?= HOST ?>admin/ai-presentations">Назад к списку презентаций</a>
          <?php else: ?>
            <a class="button button--secondary" href="<?= HOST ?>class/<?= e((string)$presentation['class_slug']) ?>">Назад к классу</a>
          <?php endif; ?>
        </div>

        <div class="flashcard-deck" data-flashcard-deck>
          <div class="flashcard-deck__status" aria-live="polite">
            <span>Осталось: <strong data-deck-remaining><?= (int)count($cards) ?></strong></span>
            <span>Выучено: <strong data-deck-learned>0</strong></span>
            <span>Повторов: <strong data-deck-repeats>0</strong></span>
          </div>

          <div class="flashcard-deck__stage" data-deck-stage>
            <?php foreach ($cards as $index => $card): ?>
              <?php
                $cardWord = trim((string)$card['english_word']);
                $wordLength = mb_strlen($cardWord, 'UTF-8');
                $wordCount = count(preg_split('/\s+/u', $cardWord) ?: []);
                $wordClass = $wordLength > 38
                  ? ' study-card--very-long'
                  : (($wordLength > 18 || $wordCount >= 4) ? ' study-card--long' : '');
              ?>
              <article class="study-card<?= $wordClass ?>" data-study-card data-card-index="<?= (int)$index ?>"<?= $index > 0 ? ' hidden' : '' ?>>
                <button class="study-card__flipper" type="button" data-card-flip aria-label="Перевернуть карточку" aria-pressed="false">
                  <span class="study-card__face study-card__face--front">
                    <span class="study-card__image-wrap">
                      <img class="study-card__image" src="<?= HOST . e((string)$card['image_path']) ?>" alt="" width="1024" height="1024">
                    </span>
                    <strong class="study-card__word"><?= e($cardWord) ?></strong>
                  </span>
                  <span class="study-card__face study-card__face--back" aria-hidden="true">
                    <span class="study-card__back-label">Перевод</span>
                    <strong class="study-card__translation"><?= e((string)$card['translation_ru']) ?></strong>
                    <span class="study-card__back-word"><?= e((string)$card['english_word']) ?></span>
                  </span>
                </button>
              </article>
            <?php endforeach; ?>
          </div>

          <div class="flashcard-deck__actions" data-deck-actions hidden>
            <button class="button flashcard-deck__repeat" type="button" data-deck-repeat>Повторить позже</button>
            <button class="button flashcard-deck__correct" type="button" data-deck-correct>Ответ верный</button>
          </div>

          <section class="flashcard-deck__complete" data-deck-complete hidden aria-live="polite">
            <div class="flashcard-deck__celebration" aria-hidden="true">★</div>
            <h2>Все слова выучены!</h2>
            <p>Колода пройдена полностью. Можно перемешать карточки и начать ещё раз.</p>
            <div class="flashcard-deck__complete-actions">
              <?php if (!empty($isTeacherPreview)): ?>
                <a class="button button--secondary" href="<?= HOST ?>admin/ai-presentations">Вернуться к списку презентаций</a>
              <?php else: ?>
                <a class="button button--secondary" href="<?= HOST ?>class/<?= e((string)$presentation['class_slug']) ?>">Вернуться на страницу класса</a>
              <?php endif; ?>
              <button class="button button--primary" type="button" data-deck-restart>Пройти ещё раз</button>
            </div>
          </section>
        </div>
      </div>
    </section>
  </main>
<?php include ROOT . 'templates/partials/footer.tpl'; ?>
