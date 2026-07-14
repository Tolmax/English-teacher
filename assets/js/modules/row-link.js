export function initRowLink() {
  const rows = document.querySelectorAll('[data-row-href]');

  rows.forEach((row) => {
    const openRow = () => {
      const href = row.dataset.rowHref;
      if (href) {
        window.location.href = href;
      }
    };

    row.addEventListener('click', (event) => {
      if (event.target.closest('a, button, input, select, textarea, form')) {
        return;
      }

      openRow();
    });

    row.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter') {
        return;
      }

      event.preventDefault();
      openRow();
    });
  });
}
