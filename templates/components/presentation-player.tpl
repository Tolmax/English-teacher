<?php $presentationSlides = $cards ?? []; ?>
<div class="presentation-player" data-presentation-player>
  <div class="presentation-player__viewport">
    <?php foreach ($presentationSlides as $index => $card): ?>
      <article class="presentation-slide" data-presentation-slide<?= $index > 0 ? ' hidden' : '' ?>>
        <div class="presentation-slide__image">
          <?php if (!empty($card['image_path'])): ?>
            <img src="<?= HOST . e((string)$card['image_path']) ?>" alt="<?= e((string)($card['english_word'] ?? '')) ?>">
          <?php else: ?>
            <span><?= e((string)($card['image_prompt'] ?? '')) ?></span>
          <?php endif; ?>
        </div>
        <div class="presentation-slide__content">
          <p class="presentation-slide__eyebrow">Card <?= (int)$index + 1 ?></p>
          <h2><?= e((string)($card['english_word'] ?? '')) ?></h2>
          <p class="presentation-slide__transcription"><?= e((string)($card['transcription'] ?? '')) ?></p>
          <p class="presentation-slide__hint"><?= e((string)($card['hint'] ?? '')) ?></p>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <div class="presentation-player__controls">
    <button class="button button--secondary" type="button" data-presentation-prev>Назад</button>
    <span class="presentation-player__counter" data-presentation-counter>1 / <?= (int)count($presentationSlides) ?></span>
    <button class="button button--primary" type="button" data-presentation-next>Вперёд</button>
    <button class="button button--secondary" type="button" data-presentation-fullscreen>Во весь экран</button>
  </div>
</div>
