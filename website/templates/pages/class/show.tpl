<?php
$pageTitle = $learningClass['title'] . ' — English Teacher';
$pageDescription = 'Страница класса с домашними заданиями, материалами, файлами, ИИ-тестами и вопросами учителю.';
include ROOT . 'templates/partials/header.tpl';
?>
  <main>
    <section class="page-hero page-hero--class" aria-labelledby="class-title">
      <div class="container page-hero__inner">
        <div class="page-hero__content">
          <h1 id="class-title" class="class-title">
            <span><?= e($classDisplayTitle) ?> КЛАСС</span>
          </h1>
        </div>
      </div>
    </section>

    <section class="section" aria-labelledby="homework-title">
      <div class="container lesson-layout">
        <div class="stack">
          <section class="card card--accent" aria-labelledby="homework-title">
            <?php if (empty($homework)): ?>
              <h2 id="homework-title">Домашнее задание к уроку</h2>
              <p class="card__text">Загляни позже: учитель добавит задание после урока.</p>
            <?php else: ?>
              <?php $mainHomework = $homework[0]; $mainHomeworkFiles = $filesByMaterial[(int)$mainHomework['id']] ?? []; ?>
              <h2 id="homework-title">Домашнее задание к уроку</h2>
              <?php if (!empty($mainHomework['deadline_at'])): ?>
                <p class="card__text">Дата урока: <?= e($mainHomework['deadline_at']) ?></p>
              <?php endif; ?>
              <?php if (!empty($mainHomework['content'])): ?>
                <p class="card__text"><?= e($mainHomework['content']) ?></p>
              <?php endif; ?>
              <?php if (!empty($mainHomeworkFiles)): ?>
                <ul class="material-list">
                  <?php foreach ($mainHomeworkFiles as $file): ?>
                    <li class="class-page-item">
                      <div>
                        <h3><?= e($file['original_name']) ?></h3>
                        <p class="card__text"><?= (int)ceil((int)$file['file_size'] / 1024) ?> КБ</p>
                      </div>
                      <a class="button button--secondary button--small" href="<?= HOST ?>uploads/materials/<?= (int)$file['material_id'] ?>/<?= e($file['stored_name']) ?>">Открыть</a>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            <?php endif; ?>
          </section>

          <section class="card" aria-labelledby="materials-title">
            <h2 id="materials-title">Пройти тест</h2>
            <ul class="material-list">
              <?php if (empty($lessonMaterials) && empty($aiLessonMaterials) && empty($aiTests) && empty($flashcardDecks)): ?>
                <li class="class-page-item"><p class="card__text">Тесты пока не добавлены.</p></li>
              <?php endif; ?>
              <?php foreach ($aiTests as $test): ?>
                <li class="class-page-item">
                  <div>
                    <h3>Тест</h3>
                    <?php if (!empty($test['description'])): ?>
                      <p class="card__text"><?= e($test['description']) ?></p>
                    <?php endif; ?>
                  </div>
                  <a class="button button--secondary button--small" href="<?= HOST ?>ai-tests/<?= e(rawurlencode($test['slug'])) ?>">Открыть тест</a>
                </li>
              <?php endforeach; ?>
              <?php foreach ($flashcardDecks as $deck): ?>
                <li class="class-page-item">
                  <div>
                    <span class="badge">Карточки</span>
                    <h3><?= e((string)$deck['title']) ?></h3>
                    <p class="card__text"><?= (int)count(aiWordPresentationDeckCards($deck)) ?> слов и выражений для повторения.</p>
                  </div>
                  <a class="button button--primary button--small" href="<?= HOST ?>flashcards/<?= (int)$deck['id'] ?>">Открыть колоду</a>
                </li>
              <?php endforeach; ?>
              <?php foreach ($lessonMaterials as $material): ?>
                <?php $materialFiles = $filesByMaterial[(int)$material['id']] ?? []; ?>
                <li class="class-page-item">
                  <div>
                    <h3><?= e($material['title']) ?></h3>
                    <p class="card__text"><?= e($material['description'] ?? '') ?></p>
                    <?php if (!empty($material['content'])): ?>
                      <p class="card__text"><?= e($material['content']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($materialFiles)): ?>
                      <div class="cluster">
                        <?php foreach ($materialFiles as $file): ?>
                          <a class="button button--secondary button--small" href="<?= HOST ?>uploads/materials/<?= (int)$file['material_id'] ?>/<?= e($file['stored_name']) ?>"><?= e($file['original_name']) ?></a>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </li>
              <?php endforeach; ?>
              <?php foreach ($aiLessonMaterials as $material): ?>
                <li class="class-page-item">
                  <div>
                    <h3><?= e($material['title']) ?></h3>
                  </div>
                  <a class="button button--secondary button--small" href="<?= HOST ?>ai-materials/<?= e(rawurlencode($material['slug'])) ?>">Открыть</a>
                </li>
              <?php endforeach; ?>
            </ul>
          </section>

          <section class="card" aria-labelledby="class-calendar-title">
            <h2 id="class-calendar-title">Ближайшие события класса</h2>
            <?php if (empty($calendarEvents)): ?>
              <p class="card__text">Для этого класса пока нет ближайших событий.</p>
            <?php else: ?>
              <div class="calendar-list calendar-list--compact">
                <?php foreach ($calendarEvents as $event): ?>
                  <article class="calendar-card">
                    <div class="calendar-card__date">
                      <span><?= e(date('d.m', strtotime($event['starts_at']))) ?></span>
                      <small><?= e(date('H:i', strtotime($event['starts_at']))) ?></small>
                    </div>
                    <div class="calendar-card__body">
                      <span class="badge"><?= e(calendarEventTypeLabel($event['event_type'])) ?></span>
                      <h3><?= e($event['title']) ?></h3>
                      <?php if (!empty($event['lesson_number'])): ?>
                        <p class="card__text">№ урока: <?= (int)$event['lesson_number'] ?></p>
                      <?php endif; ?>
                      <?php if (!empty($event['homework_content'])): ?>
                        <p class="card__text">Домашнее задание: <?= e($event['homework_content']) ?></p>
                      <?php endif; ?>
                      <?php if (!empty($event['material_title'])): ?>
                        <p class="card__text">Материал: <?= e($event['material_title']) ?></p>
                      <?php endif; ?>
                      <?php if (!empty($event['description'])): ?>
                        <p class="card__text"><?= e($event['description']) ?></p>
                      <?php endif; ?>
                    </div>
                  </article>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </section>

          <section class="card" aria-labelledby="archive-title">
            <h2 id="archive-title">Архив прошлых материалов</h2>
            <?php if (empty($archivedMaterials)): ?>
              <p class="card__text">Прошлых материалов пока нет.</p>
            <?php else: ?>
              <div class="archive-picker" data-archive-picker>
                <label class="form__label" for="archive-material">Выбери материал по теме и дате</label>
                <select class="select" id="archive-material" data-archive-picker-select>
                  <?php foreach ($archivedMaterials as $index => $material): ?>
                    <option value="archive-material-<?= (int)$material['id'] ?>"><?= e($material['title']) ?><?= !empty($material['deadline_at']) ? ' — ' . e($material['deadline_at']) : '' ?></option>
                  <?php endforeach; ?>
                </select>
                <ul class="material-list archive-picker__list">
                  <?php foreach ($archivedMaterials as $index => $material): ?>
                    <?php $archiveFiles = $filesByMaterial[(int)$material['id']] ?? []; ?>
                    <li class="class-page-item" id="archive-material-<?= (int)$material['id'] ?>" data-archive-picker-item<?= $index > 0 ? ' hidden' : '' ?>>
                  <div>
                    <h3><?= e($material['title']) ?></h3>
                    <p class="card__text"><?= e($material['description'] ?? '') ?></p>
                    <?php if (!empty($archiveFiles)): ?>
                      <div class="cluster">
                        <?php foreach ($archiveFiles as $file): ?>
                          <a class="button button--secondary button--small" href="<?= HOST ?>uploads/materials/<?= (int)$file['material_id'] ?>/<?= e($file['stored_name']) ?>"><?= e($file['original_name']) ?></a>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>
          </section>
        </div>

        <aside class="stack">
          <section class="card" aria-labelledby="announce-title">
            <h2 id="announce-title">Объявления</h2>
            <?php if (empty($announcements)): ?>
              <p class="card__text">Новых объявлений пока нет.</p>
            <?php endif; ?>
            <?php foreach ($announcements as $announcement): ?>
              <article class="stack">
                <h3><?= e($announcement['title']) ?></h3>
                <p class="card__text"><?= e($announcement['description'] ?? '') ?></p>
              </article>
            <?php endforeach; ?>
          </section>

          <section class="card" aria-labelledby="ask-title">
            <h2 id="ask-title">Задать вопрос</h2>
            <?php if (!empty($flash)): ?>
              <div class="alert alert--success" role="status"><?= e($flash) ?></div>
            <?php endif; ?>
            <form class="form" action="<?= HOST ?>class/<?= e($learningClass['slug']) ?>" method="post" novalidate>
              <div class="form__field">
                <label class="form__label" for="student-name">Имя</label>
                <input class="input" id="student-name" name="student_name" type="text" value="<?= e($old['student_name'] ?? '') ?>" autocomplete="name" required aria-describedby="student-name-error" aria-invalid="<?= !empty($errors['student_name']) ? 'true' : 'false' ?>">
                <?php if (!empty($errors['student_name'])): ?>
                  <span class="form__error" id="student-name-error" role="alert"><?= e($errors['student_name']) ?></span>
                <?php endif; ?>
              </div>
              <div class="form__field">
                <label class="form__label" for="student-question">Вопрос</label>
                <textarea class="textarea" id="student-question" name="question" required aria-describedby="student-question-error" aria-invalid="<?= !empty($errors['question']) ? 'true' : 'false' ?>"><?= e($old['question'] ?? '') ?></textarea>
                <?php if (!empty($errors['question'])): ?>
                  <span class="form__error" id="student-question-error" role="alert"><?= e($errors['question']) ?></span>
                <?php endif; ?>
              </div>
              <button class="button button--primary" type="submit">Отправить вопрос</button>
            </form>
          </section>

          <div class="class-bear" aria-hidden="true"></div>
        </aside>
      </div>
    </section>
  </main>
<?php include ROOT . 'templates/partials/footer.tpl'; ?>
