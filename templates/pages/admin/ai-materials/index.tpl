<?php
$pageTitle = 'Создание ИИ-теста — English Teacher';
$activeNav = 'ai-materials';
$recentTests = $recentTests ?? [];
$testTemplates = $testTemplates ?? [];
$defaultTestTemplateKey = $defaultTestTemplateKey ?? 'quick_check';
$selectedProvider = $aiGenerationMode['selected_provider'] ?? 'openai';
include ROOT . 'templates/partials/admin-header.tpl';
?>
      <section class="stack" aria-labelledby="ai-materials-title">
        <div class="section__header">
          <h1 id="ai-materials-title">Создать тест с ИИ</h1>
          <p class="section__lead">Введите класс и тему. Учитель получает тест из 10 вопросов с двумя вариантами ответа, проверяет его и публикует для учеников.</p>
        </div>

        <?php if (!empty($flash)): ?>
          <div class="alert alert--success" role="status"><?= e($flash) ?></div>
        <?php endif; ?>

        <?php if (!empty($errorFlash)): ?>
          <div class="alert alert--warning" role="alert"><?= e($errorFlash) ?></div>
        <?php endif; ?>

        <?php if (!empty($aiGenerationMode)): ?>
          <section class="card admin-ai-mode" aria-labelledby="ai-generation-mode-title">
            <div>
              <h2 id="ai-generation-mode-title">Режим генерации</h2>
              <p class="card__text">Выберите нейросеть в форме ниже. При отправке тест уйдёт именно к выбранному провайдеру.</p>
            </div>
          </section>
        <?php endif; ?>

        <div class="admin-ai-layout">
          <section class="card" aria-labelledby="ai-test-generator-title">
            <h2 id="ai-test-generator-title">Новый тест</h2>
            <form class="form" action="<?= HOST ?>admin/ai-materials/generate-test" method="post" novalidate>
              <div class="form__field">
                <label class="form__label" for="class_title">Класс <span aria-hidden="true">*</span></label>
                <select class="select" id="class_title" name="class_title" required>
                  <option value="">Выберите класс</option>
                  <?php foreach ($classes as $class): ?>
                    <option value="<?= e($class['title']) ?>"><?= e($class['title']) ?> класс</option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form__field">
                <label class="form__label" for="topic">Тема теста <span aria-hidden="true">*</span></label>
                <input class="input" type="text" id="topic" name="topic" placeholder="Например: Названия 10 зверей" required>
              </div>

              <div class="form__field">
                <label class="form__label" for="source_notes">Что учесть</label>
                <textarea class="textarea" id="source_notes" name="source_notes" rows="4" placeholder="Напишите слова, грамматику, формат или ограничения. Например: только Present Simple, короткие вопросы, без сложных слов."></textarea>
              </div>

              <?php if (!empty($testTemplates)): ?>
                <fieldset class="form__field">
                  <legend class="form__label">Шаблон теста</legend>
                  <div class="admin-ai-options">
                    <?php foreach ($testTemplates as $templateKey => $template): ?>
                      <label class="admin-ai-option">
                        <input
                          type="radio"
                          name="test_template"
                          value="<?= e($templateKey) ?>"
                          <?= $templateKey === $defaultTestTemplateKey ? 'checked' : '' ?>
                        >
                        <span>
                          <strong><?= e($template['title']) ?></strong>
                          <small><?= e($template['description']) ?></small>
                        </span>
                      </label>
                    <?php endforeach; ?>
                  </div>
                </fieldset>
              <?php endif; ?>

              <?php if (!empty($aiGenerationMode['providers'])): ?>
                <fieldset class="form__field">
                  <legend class="form__label">Нейросеть</legend>
                  <div class="admin-ai-options">
                    <?php foreach ($aiGenerationMode['providers'] as $providerKey => $provider): ?>
                      <label class="admin-ai-option">
                        <input
                          type="radio"
                          name="ai_provider"
                          value="<?= e($providerKey) ?>"
                          <?= $selectedProvider === $providerKey ? 'checked' : '' ?>
                        >
                        <span>
                          <strong><?= e($provider['label']) ?></strong>
                          <small><?= $provider['configured'] ? 'Ключ настроен' : 'Ключ не задан' ?> · <?= e($provider['model']) ?></small>
                        </span>
                      </label>
                    <?php endforeach; ?>
                  </div>
                </fieldset>
              <?php endif; ?>

              <fieldset class="form__field">
                <legend class="form__label">Как создать</legend>
                <div class="admin-ai-options">
                  <label class="admin-ai-option">
                    <input type="radio" name="generation_mode" value="ai" checked>
                    <span>
                      <strong>Сгенерировать с ИИ</strong>
                      <small>Выбранная нейросеть создаст вопросы по теме и заметкам учителя.</small>
                    </span>
                  </label>
                  <label class="admin-ai-option">
                    <input type="radio" name="generation_mode" value="manual">
                    <span>
                      <strong>Создать шаблон вручную</strong>
                      <small>Откроется готовая форма 10×2 для самостоятельного заполнения.</small>
                    </span>
                  </label>
                </div>
              </fieldset>

              <div class="card__actions">
                <button class="button button--primary" type="submit">Создать тест</button>
                <a class="button button--secondary" href="<?= HOST ?>admin/dashboard">Отмена</a>
              </div>
            </form>
          </section>

          <aside class="card" aria-labelledby="ai-test-template-title">
            <h2 id="ai-test-template-title">Стандарт теста</h2>
            <ul class="feature-list">
              <li class="feature-list__item"><span class="feature-list__marker">1</span><span>Ровно 10 вопросов.</span></li>
              <li class="feature-list__item"><span class="feature-list__marker">2</span><span>В каждом вопросе два варианта ответа.</span></li>
              <li class="feature-list__item"><span class="feature-list__marker">3</span><span>Ученик сразу видит, правильно ли выбран вариант.</span></li>
              <li class="feature-list__item"><span class="feature-list__marker">4</span><span>После отправки баллы сохраняются для учителя.</span></li>
            </ul>
            <p class="card__text">После создания тест открывается в редакторе. Там можно поправить вопросы, затем нажать «Опубликовать».</p>
          </aside>
        </div>

        <section class="card" aria-labelledby="recent-ai-tests-title">
          <div class="section__header">
            <h2 id="recent-ai-tests-title">Последние тесты</h2>
            <p class="section__lead">Здесь только быстрый список для перехода к редактированию и результатам.</p>
          </div>

          <?php if (empty($recentTests)): ?>
            <p class="card__text">Тесты пока не созданы.</p>
          <?php else: ?>
            <div class="table-wrap">
              <table class="table">
                <caption>Последние интерактивные ИИ-тесты</caption>
                <thead>
                  <tr>
                    <th scope="col">Тест</th>
                    <th scope="col">Класс</th>
                    <th scope="col">Вопросы</th>
                    <th scope="col">Статус</th>
                    <th scope="col">Действия</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recentTests as $test): ?>
                    <tr>
                      <th scope="row">Тест</th>
                      <td><?= e($test['class_title']) ?></td>
                      <td><?= (int)$test['questions_count'] ?></td>
                      <td><span class="badge"><?= e(aiInteractiveTestStatusLabel($test['status'])) ?></span></td>
                      <td>
                        <div class="cluster">
                          <a href="<?= HOST ?>admin/ai-tests/<?= (int)$test['id'] ?>/edit">Редактировать</a>
                          <a href="<?= HOST ?>admin/ai-tests/<?= (int)$test['id'] ?>/submissions">Результаты</a>
                          <?php if (($test['status'] ?? '') === 'published'): ?>
                            <a href="<?= HOST ?>ai-tests/<?= e(rawurlencode($test['slug'])) ?>">Открыть</a>
                          <?php endif; ?>
                          <form action="<?= HOST ?>admin/ai-tests/<?= (int)$test['id'] ?>/delete" method="post" data-confirm="Удалить этот ИИ-тест и его результаты?">
                            <button class="button button--danger button--small" type="submit">Удалить</button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </section>
      </section>
<?php include ROOT . 'templates/partials/admin-footer.tpl'; ?>
