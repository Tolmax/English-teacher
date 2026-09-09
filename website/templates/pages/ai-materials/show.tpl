<?php
$pageTitle = $material['title'] . ' — English Teacher';
$pageDescription = 'Опубликованный учебный материал по теме ' . $material['topic'] . '.';
include ROOT . 'templates/partials/header.tpl';
?>
  <main>
    <section class="page-hero page-hero--plain" aria-labelledby="ai-material-title">
      <div class="container page-hero__inner">
        <div class="page-hero__content">
          <h1 id="ai-material-title"><?= e($material['title']) ?></h1>
          <p class="page-hero__text"><?= e($material['topic']) ?></p>
          <div class="cluster">
            <span class="badge"><?= e($material['class_title']) ?> класс</span>
            <span class="badge"><?= e(aiTeachingMaterialTypeLabel($material['material_type'])) ?></span>
          </div>
        </div>
      </div>
    </section>

    <section class="section" aria-labelledby="ai-material-content-title">
      <div class="container lesson-layout">
        <article class="card ai-material-card">
          <h2 id="ai-material-content-title">Материал</h2>
          <div class="ai-material-content">
            <?= nl2br(e($material['edited_content'])) ?>
          </div>
        </article>

        <aside class="stack">
          <section class="card" aria-labelledby="ai-material-info-title">
            <h2 id="ai-material-info-title">Информация</h2>
            <p class="card__text">Класс: <?= e($material['class_title']) ?></p>
            <p class="card__text">Предмет: <?= e($material['subject']) ?></p>
            <p class="card__text">Тип: <?= e(aiTeachingMaterialTypeLabel($material['material_type'])) ?></p>
            <?php if (!empty($material['estimated_duration'])): ?>
              <p class="card__text">Время: <?= e($material['estimated_duration']) ?></p>
            <?php endif; ?>
            <?php if (!empty($material['published_at'])): ?>
              <p class="card__text">Опубликовано: <?= e($material['published_at']) ?></p>
            <?php endif; ?>
          </section>
          <a class="button button--secondary" href="<?= HOST ?>ai-materials">К списку материалов</a>
        </aside>
      </div>
    </section>
  </main>
<?php include ROOT . 'templates/partials/footer.tpl'; ?>
