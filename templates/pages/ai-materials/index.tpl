<?php
$pageTitle = 'ИИ-материалы — English Teacher';
$pageDescription = 'Опубликованные ИИ-материалы учителя английского языка для учеников.';
include ROOT . 'templates/partials/header.tpl';
?>
  <main>
    <section class="page-hero page-hero--plain" aria-labelledby="ai-materials-title">
      <div class="container page-hero__inner">
        <div class="page-hero__content">
          <h1 id="ai-materials-title">ИИ-материалы для учеников</h1>
          <p class="page-hero__text">Здесь появляются только материалы, которые учитель проверил и вручную опубликовал.</p>
        </div>
      </div>
    </section>

    <section class="section" aria-labelledby="published-ai-materials-title">
      <div class="container">
        <div class="section__header">
          <h2 id="published-ai-materials-title">Опубликованные материалы</h2>
          <p class="section__lead">Открой нужную тему, прочитай объяснение или выполни задание.</p>
        </div>

        <ul class="material-list">
          <?php if (empty($materials)): ?>
            <li class="material-item">
              <p class="card__text">Опубликованных ИИ-материалов пока нет.</p>
            </li>
          <?php endif; ?>
          <?php foreach ($materials as $material): ?>
            <li class="material-item">
              <div>
                <div class="material-item__meta">
                  <span class="badge"><?= e($material['class_title']) ?> класс</span>
                  <span class="badge"><?= e(aiTeachingMaterialTypeLabel($material['material_type'])) ?></span>
                </div>
                <h3><?= e($material['title']) ?></h3>
                <p class="card__text"><?= e($material['topic']) ?></p>
                <?php if (!empty($material['published_at'])): ?>
                  <p class="card__text">Опубликовано: <?= e($material['published_at']) ?></p>
                <?php endif; ?>
              </div>
              <a class="button button--secondary button--small" href="<?= HOST ?>ai-materials/<?= e(rawurlencode($material['slug'])) ?>">Открыть</a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </section>
  </main>
<?php include ROOT . 'templates/partials/footer.tpl'; ?>
