/**
 * Modal windows: native dialog open and close behavior.
 */

let lastTrigger = null;

export function initModal() {
  const triggers = document.querySelectorAll('[data-modal]');
  const dialogs = document.querySelectorAll('dialog[data-modal-dialog]');

  if (!triggers.length || !dialogs.length) return;

  triggers.forEach((trigger) => {
    trigger.addEventListener('click', () => openModal(trigger));
  });

  dialogs.forEach((dialog) => {
    dialog.addEventListener('click', handleBackdropClick);

    dialog.querySelectorAll('[data-modal-close]').forEach((closeButton) => {
      closeButton.addEventListener('click', () => closeModal(dialog));
    });
  });

  document.addEventListener('keydown', handleEscape);
}

function openModal(trigger) {
  const dialog = document.getElementById(trigger.dataset.modal);
  if (!dialog || typeof dialog.showModal !== 'function') return;

  lastTrigger = trigger;
  dialog.showModal();
  document.body.classList.add('is-modal-open');

  const focusTarget = dialog.querySelector('[data-modal-focus]') || dialog.querySelector('button, [href], input, select, textarea');
  if (focusTarget) focusTarget.focus();
}

function closeModal(dialog) {
  if (!dialog.open) return;

  dialog.close();
  document.body.classList.remove('is-modal-open');

  if (lastTrigger) {
    lastTrigger.focus();
    lastTrigger = null;
  }
}

function handleBackdropClick(event) {
  if (event.target !== event.currentTarget) return;
  closeModal(event.currentTarget);
}

function handleEscape(event) {
  if (event.key !== 'Escape') return;

  document.querySelectorAll('dialog[data-modal-dialog]').forEach((dialog) => {
    if (dialog.open) closeModal(dialog);
  });
}
