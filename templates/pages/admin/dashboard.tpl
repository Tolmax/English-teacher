<?php
$pageTitle = 'Панель учителя — English Teacher';
$activeNav = 'dashboard';
include ROOT . 'templates/partials/admin-header.tpl';
?>
      <section class="stack admin-dashboard" aria-labelledby="dashboard-title">
        <div class="section__header">
          <h1 id="dashboard-title">Панель учителя</h1>
          <p class="section__lead">Ближайшие уроки, домашка, заявки и вопросы без лишних блоков.</p>
        </div>

        <?php if (!empty($flash)): ?>
          <div class="alert alert--success" role="status"><?= e($flash) ?></div>
        <?php endif; ?>

        <section class="card" aria-labelledby="lesson-schedule-title">
          <div class="card__header">
            <h2 id="lesson-schedule-title">Ближайшие занятия классов</h2>
            <a class="button button--secondary button--small" href="<?= HOST ?>admin/calendar">Открыть календарь</a>
          </div>
          <div class="table-wrap">
            <table class="table admin-dashboard-table">
              <caption>Ближайшее опубликованное занятие для каждого активного класса</caption>
              <thead>
                <tr>
                  <th scope="col">Класс</th>
                  <th scope="col">Дата</th>
                  <th scope="col">Урок</th>
                  <th scope="col">Домашнее задание</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($classLessons as $lesson): ?>
                  <?php
                    $hasLesson = !empty($lesson['starts_at']);
                    $hasHomework = trim((string)($lesson['homework_content'] ?? '')) !== '' || !empty($lesson['material_title']);
                  ?>
                  <tr>
                    <th scope="row"><?= e($lesson['class_title']) ?> класс</th>
                    <td><?= $hasLesson ? e(date('d.m.Y H:i', strtotime($lesson['starts_at']))) : 'Не запланировано' ?></td>
                    <td>
                      <?php if ($hasLesson && !empty($lesson['lesson_number'])): ?>
                        Урок <?= (int)$lesson['lesson_number'] ?>
                      <?php elseif ($hasLesson): ?>
                        Урок
                      <?php else: ?>
                        —
                      <?php endif; ?>
                    </td>
                    <td class="<?= $hasHomework ? '' : 'admin-dashboard-table__warning' ?>">
                      <?php if ($hasHomework): ?>
                        <?php if (!empty($lesson['homework_content'])): ?>
                          <p class="card__text"><?= e($lesson['homework_content']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($lesson['material_title'])): ?>
                          <p class="card__text">Материал: <?= e($lesson['material_title']) ?></p>
                        <?php endif; ?>
                      <?php else: ?>
                        Домашка не опубликована
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>

        <section class="card" aria-labelledby="extra-lessons-title">
          <div class="card__header">
            <h2 id="extra-lessons-title">Заявки на допзанятия</h2>
            <a href="<?= HOST ?>admin/extra-lessons">Все заявки</a>
          </div>
          <ul class="admin-clean-list">
            <?php if (empty($recentExtraLessonRequests)): ?>
              <li>Нет активных заявок.</li>
            <?php endif; ?>
            <?php foreach ($recentExtraLessonRequests as $request): ?>
              <li>
                <a href="<?= HOST ?>admin/extra-lessons/<?= (int)$request['id'] ?>">
                  <?= e($request['student_name']) ?><?= !empty($request['class_title']) ? ', ' . e($request['class_title']) . ' класс' : '' ?> — <?= e($request['topic']) ?>
                </a>
                <span><?= e($request['created_at']) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>

        <section class="card" aria-labelledby="questions-title">
          <h2 id="questions-title">Вопросы учеников</h2>
          <ul class="admin-clean-list">
            <?php if (empty($recentQuestions)): ?>
              <li>Нет активных вопросов.</li>
            <?php endif; ?>
            <?php foreach ($recentQuestions as $question): ?>
              <li>
                <span><?= e($question['class_title']) ?> класс — <?= e($question['student_name']) ?></span>
                <strong><?= e($question['question']) ?></strong>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
      </section>
<?php include ROOT . 'templates/partials/admin-footer.tpl'; ?>
