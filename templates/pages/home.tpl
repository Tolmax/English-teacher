<?php
$pageTitle = 'English Teacher — учебный сайт';
$pageDescription = 'Главная страница учителя английского с выбором класса, материалами, ИИ-тестами и заявкой на дополнительные занятия.';
include ROOT . 'templates/partials/header.tpl';
?>
  <main>
    <section class="hero" aria-labelledby="home-title">
      <div class="container hero__inner">
        <div class="hero__content">
          <p class="hero__eyebrow">Английский без хаоса</p>
          <h1 id="home-title">Материалы, домашка и вопросы по английскому в одном месте</h1>
          <p class="hero__text">Выбери свой класс, посмотри актуальное задание, потренируй тему урока или отправь заявку на дополнительное занятие.</p>
          <div class="hero__actions">
            <details class="class-picker class-picker--hero">
              <summary class="button button--primary button--large class-picker__button">Найти свой класс</summary>
              <div class="class-picker__menu">
                <?php if (empty($classCards)): ?>
                  <p class="class-picker__empty">Классы скоро появятся</p>
                <?php endif; ?>

                <?php foreach ($classCards as $card): ?>
                  <?php $class = $card['class']; ?>
                  <a class="class-picker__link" href="<?= HOST ?>class/<?= e($class['slug']) ?>">
                    <span><?= e($class['title']) ?> класс</span>
                    <span class="class-picker__meta"><?= (int)$card['material_count'] ?> материалов</span>
                  </a>
                <?php endforeach; ?>
              </div>
            </details>
            <a class="button button--secondary button--large" href="<?= HOST ?>extra-lessons">Нужна помощь с темой</a>
          </div>
        </div>
        <div class="hero__media">
          <img class="hero__photo" src="<?= HOST ?>assets/img/hero-photo.png" alt="Фото учителя английского">
        </div>
      </div>
    </section>

    <section class="section" aria-labelledby="calendar-title">
      <div class="container">
        <div class="section__header">
          <h2 id="calendar-title">Ближайшие события</h2>
          <p class="section__lead">Уроки, дедлайны и допзанятия, которые учитель опубликовал для всех учеников.</p>
        </div>
        <?php if (empty($calendarEvents)): ?>
          <article class="card">
            <p class="card__text">Ближайших общих событий пока нет.</p>
          </article>
        <?php else: ?>
          <div class="calendar-list">
            <?php foreach ($calendarEvents as $event): ?>
              <article class="calendar-card">
                <div class="calendar-card__date">
                  <span><?= e(date('d.m', strtotime($event['starts_at']))) ?></span>
                  <small><?= e(date('H:i', strtotime($event['starts_at']))) ?></small>
                </div>
                <div class="calendar-card__body">
                  <span class="badge"><?= e(calendarEventTypeLabel($event['event_type'])) ?></span>
                  <h3><?= e($event['title']) ?></h3>
                  <?php if (!empty($event['description'])): ?>
                    <p class="card__text"><?= e($event['description']) ?></p>
                  <?php endif; ?>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <section class="section section--soft" aria-labelledby="features-title">
      <div class="container">
        <div class="section__header">
          <h2 id="features-title">Что можно сделать на сайте</h2>
          <p class="section__lead">Главная задача сайта — быстро довести ученика до нужного материала и показать учителю выполненные задания.</p>
        </div>
        <div class="demo-grid demo-grid--three">
          <article class="card">
            <h3>Посмотреть домашку</h3>
            <p class="card__text">Актуальное задание, дата сдачи и материалы к уроку.</p>
          </article>
          <article class="card">
            <h3>Пройти ИИ-тест</h3>
            <p class="card__text">Короткие тесты по теме урока с результатом для учителя.</p>
          </article>
          <article class="card">
            <h3>Попросить помощь</h3>
            <p class="card__text">Заявка на допзанятие попадёт учителю в админку.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="section" id="questions" aria-labelledby="questions-title">
      <div class="container lesson-layout">
        <div>
          <div class="section__header">
            <h2 id="questions-title">Частые вопросы</h2>
            <p class="section__lead">Короткие ответы помогают не ждать личного сообщения по простым вопросам.</p>
          </div>
          <div class="accordion">
            <button class="accordion__button" type="button" aria-expanded="false" aria-controls="home-faq-1" data-accordion-button>Где найти домашнее задание?</button>
            <div class="accordion__panel" id="home-faq-1" hidden>Выбери свой класс и открой блок с актуальной домашкой.</div>
            <button class="accordion__button" type="button" aria-expanded="false" aria-controls="home-faq-2" data-accordion-button>Как учитель узнает, что я отправил заявку?</button>
            <div class="accordion__panel" id="home-faq-2" hidden>После отправки заявка появится в админке учителя в разделе допзанятий.</div>
          </div>
        </div>
        <aside class="card card--accent" id="extra-lessons">
          <h2>Нужна помощь?</h2>
          <div class="card__actions">
            <a class="button button--primary" href="<?= HOST ?>extra-lessons">Отправить заявку</a>
          </div>
        </aside>
      </div>
    </section>
  </main>
<?php include ROOT . 'templates/partials/footer.tpl'; ?>
