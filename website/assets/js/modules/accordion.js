/**
 * Accordion disclosures.
 */

export function initAccordion() {
  const buttons = document.querySelectorAll('[data-accordion-button]');
  if (!buttons.length) return;

  buttons.forEach((button) => {
    button.addEventListener('click', () => toggleAccordion(button));
  });
}

function toggleAccordion(button) {
  const panel = document.getElementById(button.getAttribute('aria-controls'));
  if (!panel) return;

  const isOpen = button.getAttribute('aria-expanded') === 'true';

  button.setAttribute('aria-expanded', String(!isOpen));
  panel.hidden = isOpen;
}
