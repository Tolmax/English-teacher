export function initAdminSidebar() {
  const shell = document.querySelector('[data-admin-shell]');
  const toggle = document.querySelector('[data-admin-sidebar-toggle]');

  if (!shell || !toggle) {
    return;
  }

  const storageKey = 'englishTeacherAdminSidebarCollapsed';

  const setCollapsed = (isCollapsed) => {
    shell.classList.toggle('admin-shell--sidebar-collapsed', isCollapsed);
    toggle.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
    toggle.setAttribute('aria-label', isCollapsed ? 'Открыть меню' : 'Скрыть меню');
    toggle.textContent = isCollapsed ? '›' : '‹';
    localStorage.setItem(storageKey, isCollapsed ? '1' : '0');
  };

  setCollapsed(localStorage.getItem(storageKey) === '1');

  toggle.addEventListener('click', () => {
    setCollapsed(!shell.classList.contains('admin-shell--sidebar-collapsed'));
  });
}
