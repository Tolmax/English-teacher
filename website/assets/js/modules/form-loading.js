function createLoadingOverlay() {
  const overlay = document.createElement('div');
  overlay.className = 'form-loading';
  overlay.setAttribute('data-form-loading-overlay', '');
  overlay.setAttribute('role', 'status');
  overlay.setAttribute('aria-live', 'polite');
  overlay.hidden = true;

  const panel = document.createElement('div');
  panel.className = 'form-loading__panel';

  const spinner = document.createElement('span');
  spinner.className = 'form-loading__spinner';
  spinner.setAttribute('aria-hidden', 'true');

  const text = document.createElement('p');
  text.className = 'form-loading__text';
  text.setAttribute('data-form-loading-text', '');

  panel.append(spinner, text);
  overlay.append(panel);
  document.body.append(overlay);

  return overlay;
}

function showLoading(message) {
  const overlay = document.querySelector('[data-form-loading-overlay]') || createLoadingOverlay();
  const text = overlay.querySelector('[data-form-loading-text]');

  if (text) {
    text.textContent = message;
  }

  overlay.hidden = false;
}

export function initFormLoading() {
  const forms = document.querySelectorAll('form');

  forms.forEach((form) => {
    form.addEventListener('submit', (event) => {
      const message = event.submitter?.dataset.loadingMessage || form.dataset.loadingMessage;
      if (!message) {
        return;
      }

      if (event.defaultPrevented) {
        return;
      }

      if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
        return;
      }

      showLoading(message);
    });
  });
}
