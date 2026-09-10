  <footer class="site-footer">
    <div class="container nav">
      <p>English Teacher — материалы и задания по английскому языку.</p>
      <a class="button button--secondary button--small" href="<?= HOST ?>admin/login" data-teacher-login-trigger>Вход учителя</a>
    </div>
  </footer>
  <dialog class="modal teacher-login-dialog" role="dialog" aria-labelledby="teacher-login-title" data-teacher-login-dialog>
    <div class="modal__content teacher-login-dialog__content">
      <h2 class="modal__title teacher-login-dialog__title" id="teacher-login-title">А ты точно учитель?</h2>
      <div class="teacher-login-dialog__actions">
        <a class="button button--primary" href="<?= HOST ?>admin/login" data-teacher-login-confirm>Да</a>
        <button class="button button--secondary" type="button" data-teacher-login-close data-modal-focus>Нет</button>
      </div>
    </div>
  </dialog>
  <script type="module" src="<?= HOST ?>assets/js/main.js?v=20260910-1"></script>
</body>
</html>
