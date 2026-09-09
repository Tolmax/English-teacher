<?php
$pageTitle = 'Просмотр источника — English Teacher';
$activeNav = 'ai-knowledge';
$isArchived = !empty($source['archived_at']) || ($source['status'] ?? '') === 'archived';
include ROOT . 'templates/partials/admin-header.tpl';
?>
      <section class="stack" aria-labelledby="ai-knowledge-show-title">
        <div class="section__header">
          <h1 id="ai-knowledge-show-title"><?= e($source['title']) ?></h1>
          <p class="section__lead">Просмотр содержимого источника базы знаний без редактирования.</p>
        </div>

        <div class="card__actions">
          <a class="button button--link" href="<?= HOST ?>admin/ai-knowledge">К списку</a>
          <a class="button button--link" href="<?= HOST ?>admin/ai-knowledge/<?= (int)$source['id'] ?>/edit">Редактировать</a>
          <form action="<?= HOST ?>admin/ai-knowledge/<?= (int)$source['id'] ?>/delete" method="post" data-confirm="Удалить этот источник базы знаний?">
            <button class="button button--link" type="submit">Удалить</button>
          </form>
        </div>

        <section class="card" aria-labelledby="ai-knowledge-meta-title">
          <h2 id="ai-knowledge-meta-title">Сведения</h2>
          <div class="stack">
            <p class="card__text"><strong>Категория:</strong> <?= !empty($source['category_title']) ? e($source['category_title']) : 'Без категории' ?></p>
            <p class="card__text"><strong>Тип:</strong> <?= e(aiKnowledgeSourceTypeLabel($source['source_type'])) ?></p>
            <p class="card__text"><strong>Статус:</strong> <?= e(aiKnowledgeSourceStatusLabel($source['status'])) ?></p>
            <p class="card__text"><strong>Создан:</strong> <?= e($source['created_at']) ?></p>
            <?php if (!empty($source['updated_at'])): ?>
              <p class="card__text"><strong>Обновлён:</strong> <?= e($source['updated_at']) ?></p>
            <?php endif; ?>
            <?php if ($isArchived && !empty($source['archived_at'])): ?>
              <p class="card__text"><strong>В архиве:</strong> <?= e($source['archived_at']) ?></p>
            <?php endif; ?>
          </div>

          <?php if (!empty($source['description'])): ?>
            <p class="card__text"><strong>Описание:</strong> <?= e($source['description']) ?></p>
          <?php endif; ?>

          <?php if (!empty($source['tags'])): ?>
            <p class="card__text"><strong>Теги:</strong> <?= e($source['tags']) ?></p>
          <?php endif; ?>
        </section>

        <?php if (!empty($source['stored_name'])): ?>
          <section class="card" aria-labelledby="ai-knowledge-file-title">
            <h2 id="ai-knowledge-file-title">Файл</h2>
            <p class="card__text">
              <a href="<?= HOST . e($source['storage_path']) ?>"><?= e($source['original_name']) ?></a>
            </p>
            <p class="card__text"><?= e($source['mime_type']) ?> · <?= (int)ceil((int)$source['file_size'] / 1024) ?> КБ</p>
          </section>
        <?php endif; ?>

        <section class="card" aria-labelledby="ai-knowledge-text-title">
          <h2 id="ai-knowledge-text-title">Содержимое</h2>
          <?php if (trim((string)($source['extracted_text'] ?? '')) !== ''): ?>
            <pre class="card__text"><?= e($source['extracted_text']) ?></pre>
          <?php else: ?>
            <p class="card__text">Текстовое содержимое пока не добавлено.</p>
          <?php endif; ?>
        </section>
      </section>
<?php include ROOT . 'templates/partials/admin-footer.tpl'; ?>
