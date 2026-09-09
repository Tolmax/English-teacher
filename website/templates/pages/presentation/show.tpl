<?php
$pageTitle = (string)$presentation['title'] . ' — English Teacher';
$pageDescription = 'Экранный показ ИИ-презентации по английским словам.';
include ROOT . 'templates/partials/header.tpl';
?>
  <main>
    <section class="section presentation-page" aria-labelledby="presentation-title">
      <div class="container stack">
        <div class="section__header section__header--inline">
          <div>
            <h1 id="presentation-title"><?= e((string)$presentation['title']) ?></h1>
            <p class="section__lead">Нажимай стрелки или кнопки, чтобы листать карточки.</p>
          </div>
          <a class="button button--secondary" href="<?= HOST ?>class/<?= e((string)$presentation['class_slug']) ?>">Назад к классу</a>
        </div>

        <?php include ROOT . 'templates/components/presentation-player.tpl'; ?>
      </div>
    </section>
  </main>
<?php include ROOT . 'templates/partials/footer.tpl'; ?>
