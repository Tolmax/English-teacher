<link rel="stylesheet" href="<?= HOST ?>assets/css/blocks/presentation-slides.css?v=20260909">
<div class="presentation-player" data-presentation-player>
  <div class="presentation-player__viewport">
    <?php foreach ($presentationSlides as $index => $card): ?>
      <article class="presentation-slide presentation-slide--single" data-presentation-slide<?= $index > 0 ? ' hidden' : '' ?>>
        <?php if ($card['type'] === 'image'): ?>
        <div class="presentation-slide__image">
          <?php if (!empty($card['image_path'])): ?>
            <img src="<?= HOST . e((string)$card['image_path']) ?>" alt="Vocabulary illustration" width="1024" height="1024">
          <?php else: ?>
            <span><?= e((string)($card['image_prompt'] ?? '')) ?></span>
          <?php endif; ?>
        </div>
        <?php elseif ($card['type'] === 'text'): ?>
        <div class="presentation-slide__content">
          <p class="presentation-slide__eyebrow">Card <?= (int)$index + 1 ?></p>
          <h2><?= e((string)($card['english_word'] ?? '')) ?></h2>
          <p class="presentation-slide__transcription"><?= e((string)($card['transcription'] ?? '')) ?></p>
          <p class="presentation-slide__hint"><?= e((string)($card['hint'] ?? '')) ?></p>
          <p><?= e((string)($card['example_sentence'] ?? '')) ?></p>
        </div>
        <?php else: ?>
          <div class="presentation-slide__content">
            <h2><?= $card['type'] === 'quiz' ? 'Fill in the missing words' : 'Check your answers' ?></h2>
            <?php if (!empty($card['words'])): ?>
              <div class="presentation-slide__words">
                <?php foreach ($card['words'] as $word): ?><span class="presentation-slide__cloud"><?= e($word) ?></span><?php endforeach; ?>
              </div>
            <?php endif; ?>
            <ol start="<?= (int)$card['offset'] + 1 ?>">
              <?php foreach ($card['sentences'] as $sentence): ?><li><?= e($sentence) ?></li><?php endforeach; ?>
            </ol>
          </div>
        <?php endif; ?>
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
