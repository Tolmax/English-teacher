<?php
$pageTitle = 'Результаты ИИ-теста — English Teacher';
$activeNav = 'ai-tests';
include ROOT . 'templates/partials/admin-header.tpl';
?>
      <section class="stack" aria-labelledby="ai-test-submissions-title">
        <div class="section__header">
          <h1 id="ai-test-submissions-title">Результаты: Тест</h1>
          <p class="section__lead">Здесь сохраняются ответы учеников без личных кабинетов и паролей.</p>
        </div>

        <div class="card__actions">
          <a class="button button--secondary" href="<?= HOST ?>admin/ai-tests/<?= (int)$test['id'] ?>/edit">К тесту</a>
          <a class="button button--secondary" href="<?= HOST ?>admin/ai-tests">Все ИИ-тесты</a>
        </div>

        <section class="card" aria-labelledby="submissions-list-title">
          <h2 id="submissions-list-title">Отправки</h2>
          <div class="table-wrap">
            <table class="table">
              <caption>Результаты прохождения теста</caption>
              <thead>
                <tr>
                  <th scope="col">Ученик</th>
                  <th scope="col">Результат</th>
                  <th scope="col">Дата</th>
                  <th scope="col">Ответы</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($submissions)): ?>
                  <tr><td colspan="4">Результатов пока нет.</td></tr>
                <?php endif; ?>
                <?php foreach ($submissions as $submission): ?>
                  <?php $answers = json_decode($submission['answers_json'], true) ?: []; ?>
                  <tr>
                    <th scope="row"><?= e($submission['student_name']) ?></th>
                    <td><?= (int)$submission['score'] ?> из <?= (int)$submission['total_questions'] ?></td>
                    <td><?= e($submission['submitted_at']) ?></td>
                    <td>
                      <?php foreach ($answers as $answer): ?>
                        <p class="card__text">
                          <?= !empty($answer['is_correct']) ? '✓' : '×' ?>
                          <?= e((string)($answer['question'] ?? '')) ?>:
                          <?= e((string)($answer['selected_option'] ?? '')) ?>
                        </p>
                      <?php endforeach; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>
      </section>
<?php include ROOT . 'templates/partials/admin-footer.tpl'; ?>
