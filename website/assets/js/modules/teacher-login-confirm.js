/**
 * Teacher login confirmation dialog.
 */

let lastTrigger = null;

export function initTeacherLoginConfirm() {
  const dialog = document.querySelector('[data-teacher-login-dialog]');
  const triggers = document.querySelectorAll('[data-teacher-login-trigger]');

  if (!dialog || !triggers.length || typeof dialog.showModal !== 'function') {
    return;
  }

  const closeButtons = dialog.querySelectorAll('[data-teacher-login-close]');

  triggers.forEach((trigger) => {
    trigger.addEventListener('click', (event) => {
      event.preventDefault();
      openTeacherLoginDialog(dialog, trigger);
    });
  });

  closeButtons.forEach((button) => {
    button.addEventListener('click', () => closeTeacherLoginDialog(dialog));
  });

  dialog.addEventListener('close', () => {
    dialog.removeAttribute('aria-modal');
    document.body.classList.remove('is-modal-open');
    restoreTeacherLoginFocus();
  });

  dialog.addEventListener('click', (event) => {
    if (event.target !== dialog) {
      return;
    }

    closeTeacherLoginDialog(dialog);
  });
}

function openTeacherLoginDialog(dialog, trigger) {
  lastTrigger = trigger;
  dialog.showModal();
  dialog.setAttribute('aria-modal', 'true');
  document.body.classList.add('is-modal-open');

  const focusTarget = dialog.querySelector('[data-modal-focus]') || dialog.querySelector('button, [href]');
  if (focusTarget) {
    focusTarget.focus();
  }
}

function closeTeacherLoginDialog(dialog) {
  if (!dialog.open) {
    return;
  }

  dialog.close();
}

function restoreTeacherLoginFocus() {
  if (!lastTrigger) {
    return;
  }

  lastTrigger.focus();
  lastTrigger = null;
}
