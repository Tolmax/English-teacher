export function initArchivePicker() {
  const pickers = document.querySelectorAll('[data-archive-picker]');

  pickers.forEach((picker) => {
    const select = picker.querySelector('[data-archive-picker-select]');
    const items = [...picker.querySelectorAll('[data-archive-picker-item]')];

    if (!select || items.length === 0) {
      return;
    }

    const syncSelectedItem = () => {
      items.forEach((item) => {
        item.hidden = item.id !== select.value;
      });
    };

    select.addEventListener('change', syncSelectedItem);
    syncSelectedItem();
  });
}
