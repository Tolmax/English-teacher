/**
 * Accessible tabs.
 */

export function initTabs() {
  const tablists = document.querySelectorAll('[data-tabs]');
  if (!tablists.length) return;

  tablists.forEach((tablist) => {
    const tabs = Array.from(tablist.querySelectorAll('[role="tab"]'));
    if (!tabs.length) return;

    tabs.forEach((tab) => {
      tab.addEventListener('click', () => activateTab(tab, tabs));
      tab.addEventListener('keydown', (event) => handleTabKeydown(event, tabs));
    });
  });
}

function activateTab(activeTab, tabs) {
  tabs.forEach((tab) => {
    const isActive = tab === activeTab;
    const panel = document.getElementById(tab.getAttribute('aria-controls'));

    tab.setAttribute('aria-selected', String(isActive));
    tab.tabIndex = isActive ? 0 : -1;

    if (panel) {
      panel.hidden = !isActive;
    }
  });
}

function handleTabKeydown(event, tabs) {
  const currentIndex = tabs.indexOf(event.currentTarget);
  let nextIndex = currentIndex;

  if (event.key === 'ArrowRight') nextIndex = (currentIndex + 1) % tabs.length;
  if (event.key === 'ArrowLeft') nextIndex = (currentIndex - 1 + tabs.length) % tabs.length;
  if (event.key === 'Home') nextIndex = 0;
  if (event.key === 'End') nextIndex = tabs.length - 1;

  if (nextIndex === currentIndex) return;

  event.preventDefault();
  tabs[nextIndex].focus();
  activateTab(tabs[nextIndex], tabs);
}
