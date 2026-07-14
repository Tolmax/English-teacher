<?php
$pageTitle = 'Календарь — English Teacher';
$activeNav = 'calendar';
$eventsByClassAndDate = [];
$commonEventsByDate = [];
$weekendToggleUrl = HOST . 'admin/calendar?week=' . e($weekStart) . ($showWeekend ? '' : '&show_weekend=1');

foreach ($events as $event) {
    $dateKey = substr((string)$event['starts_at'], 0, 10);
    $classId = (int)($event['class_id'] ?? 0);

    if ($classId > 0) {
        $eventsByClassAndDate[$classId][$dateKey][] = $event;
    } else {
        $commonEventsByDate[$dateKey][] = $event;
    }
}

include ROOT . 'templates/partials/admin-header.tpl';
?>
      <section class="stack admin-calendar" aria-labelledby="calendar-title">
        <div class="section__header">
          <h1 id="calendar-title">Календарь</h1>
          <p class="section__lead">Планируйте уроки, домашку, материалы и события. Уроки переносятся на следующие недели автоматически, домашка не переносится.</p>
        </div>

        <?php if (!empty($flash)): ?>
          <div class="alert alert--success" role="status"><?= e($flash) ?></div>
        <?php endif; ?>

        <section class="card admin-calendar-toolbar" aria-label="Навигация календаря">
          <a href="<?= HOST ?>admin/calendar?week=<?= e($previousWeek) ?><?= $showWeekend ? '&show_weekend=1' : '' ?>">Предыдущая неделя</a>
          <strong><?= e(date('d.m.Y', strtotime($weekStart))) ?> — <?= e(date('d.m.Y', strtotime($weekEnd . ' -1 day'))) ?></strong>
          <a href="<?= HOST ?>admin/calendar?week=<?= e($nextWeek) ?><?= $showWeekend ? '&show_weekend=1' : '' ?>">Следующая неделя</a>
          <a class="button button--secondary button--small" href="<?= HOST ?>admin/calendar/create">Добавить запись</a>
          <a class="button button--secondary button--small calendar-weekend-toggle" href="<?= $weekendToggleUrl ?>" aria-expanded="<?= $showWeekend ? 'true' : 'false' ?>">
            <?= $showWeekend ? '− Скрыть выходные' : '+ Выходные' ?>
          </a>
        </section>

        <section class="card" aria-labelledby="planner-title">
          <h2 id="planner-title">Текущая неделя</h2>
          <div class="table-wrap">
            <table class="table admin-planner">
              <caption>Недельный план по классам</caption>
              <thead>
                <tr>
                  <th scope="col">Класс</th>
                  <?php foreach ($weekDays as $day): ?>
                    <th scope="col"><?= e($day['label']) ?><br><span><?= e($day['day']) ?></span></th>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($classes as $class): ?>
                  <tr>
                    <th scope="row"><?= e($class['title']) ?> класс</th>
                    <?php foreach ($weekDays as $day): ?>
                      <?php
                        $dayEvents = $eventsByClassAndDate[(int)$class['id']][$day['date']] ?? [];
                        $hasLesson = false;
                        foreach ($dayEvents as $dayEvent) {
                            if (($dayEvent['event_type'] ?? '') === 'lesson') {
                                $hasLesson = true;
                                break;
                            }
                        }
                      ?>
                      <td>
                        <?php foreach ($dayEvents as $event): ?>
                          <article class="planner-event">
                            <a href="<?= HOST ?>admin/calendar/<?= (int)$event['id'] ?>/edit">
                              <?= e(calendarEventTypeLabel($event['event_type'])) ?>
                              <?php if (!empty($event['lesson_number'])): ?><?= (int)$event['lesson_number'] ?><?php endif; ?>
                            </a>
                            <span><?= e(date('H:i', strtotime($event['starts_at']))) ?></span>
                            <?php if (!empty($event['homework_content'])): ?>
                              <small>ДЗ: <?= e($event['homework_content']) ?></small>
                            <?php endif; ?>
                            <?php if (!empty($event['material_title'])): ?>
                              <small>Материал: <?= e($event['material_title']) ?></small>
                            <?php endif; ?>
                          </article>
                        <?php endforeach; ?>
                        <?php if (!$hasLesson): ?>
                          <a class="planner-add" href="<?= HOST ?>admin/calendar/create?class_id=<?= (int)$class['id'] ?>&date=<?= e($day['date']) ?>">Добавить</a>
                        <?php endif; ?>
                      </td>
                    <?php endforeach; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>

        <section class="card" aria-labelledby="common-events-title">
          <h2 id="common-events-title">Общие события недели</h2>
          <ul class="admin-clean-list">
            <?php if (empty($commonEventsByDate)): ?>
              <li>Общих событий на этой неделе нет.</li>
            <?php endif; ?>
            <?php foreach ($commonEventsByDate as $date => $dayEvents): ?>
              <?php foreach ($dayEvents as $event): ?>
                <li>
                  <a href="<?= HOST ?>admin/calendar/<?= (int)$event['id'] ?>/edit">
                    <?= e(date('d.m H:i', strtotime($event['starts_at']))) ?> — <?= e($event['title']) ?>
                  </a>
                  <span><?= e(calendarEventTypeLabel($event['event_type'])) ?></span>
                </li>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </ul>
        </section>
      </section>
<?php include ROOT . 'templates/partials/admin-footer.tpl'; ?>
