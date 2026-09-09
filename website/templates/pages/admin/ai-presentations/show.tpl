<?php
$pageTitle = 'Показ презентации — English Teacher';
$activeNav = 'ai-presentations';
$hasCards = !empty($cards);
$hasPptxPreview = !empty($pptxPreviewSlides);
include ROOT . 'templates/partials/admin-header.tpl';
?>
      <section class="stack" aria-labelledby="presentation-show-title">
        <div class="section__header section__header--inline">
          <div>
            <h1 id="presentation-show-title"><?= e((string)$presentation['title']) ?></h1>
            <p class="section__lead">
              <?= $hasCards ? 'Экранный показ карточек для урока.' : 'Экранный показ загруженной презентации.' ?>
            </p>
          </div>
          <div class="cluster">
            <a class="button button--secondary" href="<?= HOST ?>admin/ai-presentations">Назад</a>
          </div>
        </div>

        <?php if ($hasCards): ?>
          <?php include ROOT . 'templates/components/presentation-player.tpl'; ?>
        <?php elseif ($hasPptxPreview): ?>
          <div class="presentation-player" data-presentation-player>
            <div class="presentation-player__viewport">
              <?php foreach ($pptxPreviewSlides as $index => $slide): ?>
                <article class="presentation-slide" data-presentation-slide<?= $index > 0 ? ' hidden' : '' ?>>
                  <div class="presentation-slide__image">
                    <?php if (!empty($slide['images'][0])): ?>
                      <img src="<?= e((string)$slide['images'][0]) ?>" alt="">
                    <?php else: ?>
                      <span>Слайд <?= (int)$index + 1 ?></span>
                    <?php endif; ?>
                  </div>
                  <div class="presentation-slide__content">
                    <p class="presentation-slide__eyebrow">Slide <?= (int)$index + 1 ?></p>
                    <h2><?= e((string)($slide['title'] ?? '')) ?></h2>
                    <?php if (!empty($slide['subtitle'])): ?>
                      <p class="presentation-slide__transcription"><?= e((string)$slide['subtitle']) ?></p>
                    <?php endif; ?>
                    <?php foreach (($slide['body'] ?? []) as $line): ?>
                      <p class="presentation-slide__hint"><?= e((string)$line) ?></p>
                    <?php endforeach; ?>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>

            <div class="presentation-player__controls">
              <button class="button button--secondary" type="button" data-presentation-prev>Назад</button>
              <span class="presentation-player__counter" data-presentation-counter>1 / <?= (int)count($pptxPreviewSlides) ?></span>
              <button class="button button--primary" type="button" data-presentation-next>Вперёд</button>
              <button class="button button--secondary" type="button" data-presentation-fullscreen>Во весь экран</button>
            </div>
          </div>
        <?php else: ?>
          <div class="alert alert--warning" role="alert">
            Не удалось подготовить показ PPTX. Проверьте, что файл презентации существует и не повреждён.
          </div>
        <?php endif; ?>
      </section>
<?php include ROOT . 'templates/partials/admin-footer.tpl'; ?>
