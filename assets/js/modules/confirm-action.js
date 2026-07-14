export function initConfirmAction() {
  const forms = document.querySelectorAll('form');

  forms.forEach((form) => {
    form.addEventListener('submit', (event) => {
      const submitter = event.submitter;
      const message = submitter?.dataset?.confirm || form.dataset.confirm || 'Подтвердите действие.';

      if (!submitter?.dataset?.confirm && !form.dataset.confirm) {
        return;
      }

      if (!window.confirm(message)) {
        event.preventDefault();
      }
    });
  });
}
