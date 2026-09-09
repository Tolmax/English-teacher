export function initMobileMenu() {
  const toggle = document.querySelector('[data-mobile-menu-toggle]');

  if (!toggle) {
    return;
  }

  const menuId = toggle.getAttribute('aria-controls');
  const menu = menuId ? document.getElementById(menuId) : document.querySelector('[data-mobile-menu]');

  if (!menu) {
    return;
  }

  const closeMenu = () => {
    toggle.setAttribute('aria-expanded', 'false');
    menu.hidden = true;
    menu.classList.remove('is-open');
  };

  const openMenu = () => {
    toggle.setAttribute('aria-expanded', 'true');
    menu.hidden = false;
    menu.classList.add('is-open');
  };

  toggle.addEventListener('click', () => {
    if (toggle.getAttribute('aria-expanded') === 'true') {
      closeMenu();
      return;
    }

    openMenu();
  });

  menu.addEventListener('click', (event) => {
    if (event.target instanceof HTMLAnchorElement) {
      closeMenu();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape' || menu.hidden) {
      return;
    }

    closeMenu();
    toggle.focus();
  });

  document.addEventListener('click', (event) => {
    if (menu.hidden || !(event.target instanceof Node)) {
      return;
    }

    if (menu.contains(event.target) || toggle.contains(event.target)) {
      return;
    }

    closeMenu();
  });
}
