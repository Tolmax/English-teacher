<?php
$isEdit = ($mode ?? '') === 'edit';
$pageTitle = ($isEdit ? 'Редактировать ИИ-презентацию' : 'Новая ИИ-презентация') . ' — English Teacher';
$activeNav = 'ai-presentations';
$actionUrl = $isEdit
    ? HOST . 'admin/ai-presentations/' . (int)$presentation['id'] . '/edit'
    : HOST . 'admin/ai-presentations/create';
$cards = $old['cards'] ?? ($isEdit ? aiWordPresentationCards($presentation) : []);
$hasCards = !empty($cards);
$hasPptx = $isEdit && !empty($presentation['pptx_file_id']);
$isUploadedPptx = $hasPptx && !$hasCards && trim((string)($old['source_words_text'] ?? '')) === '';
$openAiConfigured = defined('OPENAI_API_KEY') && trim((string)OPENAI_API_KEY) !== '';
$generationMode = $openAiConfigured ? 'OpenAI API' : 'mock';
$imageGenerationMode = $openAiConfigured ? 'OpenAI API' : 'mock';
include ROOT . 'templates/partials/admin-header.tpl';
?>
      <section class="stack" aria-labelledby="ai-presentation-form-title">
        <div class="section__header">
          <h1 id="ai-presentation-form-title"><?= $isEdit ? 'Редактировать ИИ-презентацию' : 'Новая ИИ-презентация' ?></h1>
          <p class="section__lead">Создайте презентацию из английских слов или загрузите готовый PPTX, собранный локальным генератором.</p>
        </div>

        <?php if (!empty($flash)): ?>
          <div class="alert alert--success" role="status"><?= e($flash) ?></div>
        <?php endif; ?>

        <?php if (!empty($errorFlash)): ?>
          <div class="alert alert--warning" role="alert"><?= e($errorFlash) ?></div>
        <?php endif; ?>

        <?php if (!$isUploadedPptx): ?>
          <div class="alert <?= $generationMode === 'mock' ? 'alert--warning' : 'alert--success' ?>" role="status">
            Режим генерации карточек: <?= e($generationMode) ?>.
            <?php if ($generationMode === 'mock'): ?>
              OPENAI_API_KEY не задан, поэтому будут созданы тестовые карточки без внешнего запроса.
            <?php else: ?>
              Для презентаций используется только OpenAI. GigaChat применяется только для тестов.
            <?php endif; ?>
          </div>

          <div class="alert <?= $imageGenerationMode === 'mock' ? 'alert--warning' : 'alert--success' ?>" role="status">
            Режим картинок при сборке презентации: <?= e($imageGenerationMode) ?>.
            <?php if ($imageGenerationMode === 'mock'): ?>
              OPENAI_API_KEY не задан, поэтому будут созданы локальные тестовые изображения.
            <?php else: ?>
              Картинки будут сгенерированы через OpenAI и сохранены в папку материала.
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if ($isEdit): ?>
          <?php if ($isUploadedPptx): ?>
            <div class="alert alert--success" role="status">
              Загружен готовый PPTX-файл. Для такой презентации генерация карточек на сайте не нужна.
            </div>
          <?php elseif (!$hasCards): ?>
            <form class="admin-inline-action" action="<?= HOST ?>admin/ai-presentations/<?= (int)$presentation['id'] ?>/generate" method="post" data-loading-message="Идёт генерация карточек. Подождите, пожалуйста...">
              <p class="section__lead">Карточки презентации будут сгенерированы через OpenAI.</p>
              <button class="button button--primary" type="submit">Сгенерировать карточки</button>
            </form>
          <?php endif; ?>

          <?php if ($hasPptx): ?>
            <div class="admin-inline-action">
              <a class="button button--secondary" href="<?= HOST ?>admin/ai-presentations/<?= (int)$presentation['id'] ?>/show">Показать</a>
              <?php if ($hasCards): ?>
                <a class="button button--secondary" href="<?= HOST ?>presentation/<?= (int)$presentation['id'] ?>" target="_blank" rel="noopener">Открыть в отдельном окне</a>
              <?php endif; ?>
              <a class="button button--secondary" href="<?= HOST ?>admin/ai-presentations/<?= (int)$presentation['id'] ?>/download">Скачать PPTX</a>
            </div>
          <?php elseif ($hasCards): ?>
            <div class="admin-inline-action">
              <form action="<?= HOST ?>admin/ai-presentations/<?= (int)$presentation['id'] ?>/build" method="post" data-loading-message="Идёт генерация картинок и сборка презентации. Это может занять немного времени...">
                <button class="button button--primary" type="submit">Собрать презентацию</button>
              </form>
            </div>
          <?php endif; ?>
        <?php endif; ?>

        <form class="form card" action="<?= $actionUrl ?>" method="post" enctype="multipart/form-data" novalidate>
          <div class="form__row">
            <div class="form__field">
              <label class="form__label" for="class_id">Класс <span aria-hidden="true">*</span></label>
              <select class="select" id="class_id" name="class_id" required aria-describedby="class-id-error" aria-invalid="<?= !empty($errors['class_id']) ? 'true' : 'false' ?>">
                <option value="">Выберите класс</option>
                <?php foreach ($classes as $class): ?>
                  <option value="<?= (int)$class['id'] ?>"<?= (int)($old['class_id'] ?? 0) === (int)$class['id'] ? ' selected' : '' ?>><?= e($class['title']) ?> класс</option>
                <?php endforeach; ?>
              </select>
              <?php if (!empty($errors['class_id'])): ?>
                <span class="form__error" id="class-id-error" role="alert"><?= e($errors['class_id']) ?></span>
              <?php endif; ?>
            </div>

            <div class="form__field">
              <label class="form__label" for="title">Название <span aria-hidden="true">*</span></label>
              <input class="input" type="text" id="title" name="title" value="<?= e($old['title'] ?? '') ?>" required aria-describedby="title-error" aria-invalid="<?= !empty($errors['title']) ? 'true' : 'false' ?>">
              <?php if (!empty($errors['title'])): ?>
                <span class="form__error" id="title-error" role="alert"><?= e($errors['title']) ?></span>
              <?php endif; ?>
            </div>
          </div>

          <?php if (!$isEdit): ?>
            <section class="form__field" aria-labelledby="pptx-upload-title">
              <h2 id="pptx-upload-title">Загрузить готовую презентацию</h2>
              <p class="section__lead">Если презентация уже собрана локальным генератором, выберите PPTX-файл здесь. В этом случае слова ниже заполнять не нужно.</p>
              <div class="form__field">
                <label class="form__label" for="pptx_file">PPTX-файл</label>
                <input class="input" type="file" id="pptx_file" name="pptx_file" accept=".pptx,application/vnd.openxmlformats-officedocument.presentationml.presentation" aria-describedby="pptx-file-error">
                <?php if (!empty($errors['pptx_file'])): ?>
                  <span class="form__error" id="pptx-file-error" role="alert"><?= e($errors['pptx_file']) ?></span>
                <?php endif; ?>
              </div>
            </section>
          <?php endif; ?>

          <?php if (!$isUploadedPptx): ?>
            <div class="form__field">
              <label class="form__label" for="source_words_text">Английские слова<?= $isEdit ? ' <span aria-hidden="true">*</span>' : '' ?></label>
              <textarea class="textarea" id="source_words_text" name="source_words_text" rows="6" aria-describedby="source-words-hint source-words-error" aria-invalid="<?= !empty($errors['source_words_text']) ? 'true' : 'false' ?>"><?= e($old['source_words_text'] ?? '') ?></textarea>
              <span class="form__hint" id="source-words-hint">Можно писать каждое слово с новой строки или через запятую. Максимум 10 слов. Разрешены пробел, дефис и апостроф.</span>
              <?php if (!empty($errors['source_words_text'])): ?>
                <span class="form__error" id="source-words-error" role="alert"><?= e($errors['source_words_text']) ?></span>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($errors['cards'])): ?>
            <div class="alert alert--warning" role="alert"><?= e($errors['cards']) ?></div>
          <?php endif; ?>

          <?php if ($isEdit && $hasCards): ?>
            <section class="admin-word-cards" aria-labelledby="word-cards-title">
              <div class="section__header">
                <h2 id="word-cards-title">Карточки презентации</h2>
                <p class="section__lead">Проверьте слова, транскрипцию, подсказки и описания картинок перед сборкой PPTX.</p>
              </div>

              <div class="admin-word-cards__list">
                <?php foreach ($cards as $index => $card): ?>
                  <fieldset class="admin-word-card">
                    <legend>Карточка <?= (int)$index + 1 ?></legend>
                    <input type="hidden" name="cards[<?= (int)$index ?>][source_word]" value="<?= e($card['source_word'] ?? '') ?>">
                    <input type="hidden" name="cards[<?= (int)$index ?>][image_path]" value="<?= e($card['image_path'] ?? '') ?>">
                    <input type="hidden" name="cards[<?= (int)$index ?>][image_mime]" value="<?= e($card['image_mime'] ?? '') ?>">

                    <div class="form__row">
                      <div class="form__field">
                        <label class="form__label" for="card-<?= (int)$index ?>-english">Английское слово</label>
                        <input class="input" type="text" id="card-<?= (int)$index ?>-english" name="cards[<?= (int)$index ?>][english_word]" value="<?= e($card['english_word'] ?? '') ?>" required>
                      </div>

                      <div class="form__field">
                        <label class="form__label" for="card-<?= (int)$index ?>-transcription">Транскрипция</label>
                        <input class="input" type="text" id="card-<?= (int)$index ?>-transcription" name="cards[<?= (int)$index ?>][transcription]" value="<?= e($card['transcription'] ?? '') ?>" required>
                      </div>
                    </div>

                    <div class="form__field">
                      <label class="form__label" for="card-<?= (int)$index ?>-hint">Подсказка</label>
                      <textarea class="textarea" id="card-<?= (int)$index ?>-hint" name="cards[<?= (int)$index ?>][hint]" rows="2" required><?= e($card['hint'] ?? '') ?></textarea>
                    </div>

                    <div class="form__field">
                      <label class="form__label" for="card-<?= (int)$index ?>-image">Описание картинки</label>
                      <textarea class="textarea" id="card-<?= (int)$index ?>-image" name="cards[<?= (int)$index ?>][image_prompt]" rows="2" required><?= e($card['image_prompt'] ?? '') ?></textarea>
                    </div>
                  </fieldset>
                <?php endforeach; ?>
              </div>
            </section>
          <?php endif; ?>

          <div class="card__actions">
            <button class="button button--primary" type="submit">Сохранить</button>
            <a class="button button--secondary" href="<?= HOST ?>admin/ai-presentations">Отмена</a>
          </div>
        </form>

        <?php if ($isEdit && !empty($presentation)): ?>
          <form class="admin-inline-action" action="<?= HOST ?>admin/ai-presentations/<?= (int)$presentation['id'] ?>/delete" method="post" data-confirm="Удалить ИИ-презентацию, материал, PPTX и картинки?">
            <button class="button button--danger" type="submit">Удалить</button>
          </form>
        <?php endif; ?>
      </section>
<?php include ROOT . 'templates/partials/admin-footer.tpl'; ?>
