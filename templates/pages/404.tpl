<?php
$pageTitle = 'Страница не найдена — English Teacher';
$pageDescription = 'Страница не найдена.';
include ROOT . 'templates/partials/header.tpl';
?>
  <main class="page-section">
    <section class="card stack" aria-labelledby="not-found-title">
      <h1 id="not-found-title">Страница не найдена</h1>
      <p class="card__text">Проверьте адрес или вернитесь на главную страницу.</p>
      <a class="button button--primary" href="<?= HOST ?>">На главную</a>
    </section>
  </main>
<?php include ROOT . 'templates/partials/footer.tpl'; ?>
