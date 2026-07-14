function setActiveSlide(player, nextIndex) {
  const slides = Array.from(player.querySelectorAll('[data-presentation-slide]'));
  const counter = player.querySelector('[data-presentation-counter]');
  const prevButton = player.querySelector('[data-presentation-prev]');
  const nextButton = player.querySelector('[data-presentation-next]');

  if (slides.length === 0) {
    return;
  }

  const index = Math.max(0, Math.min(nextIndex, slides.length - 1));
  player.dataset.presentationIndex = String(index);

  slides.forEach((slide, slideIndex) => {
    slide.hidden = slideIndex !== index;
  });

  if (counter) {
    counter.textContent = `${index + 1} / ${slides.length}`;
  }

  if (prevButton) {
    prevButton.disabled = index === 0;
  }

  if (nextButton) {
    nextButton.disabled = index === slides.length - 1;
  }
}

function moveSlide(player, direction) {
  const currentIndex = Number(player.dataset.presentationIndex || 0);
  setActiveSlide(player, currentIndex + direction);
}

function updateFullscreenButton(player) {
  const fullscreenButton = player.querySelector('[data-presentation-fullscreen]');

  if (!fullscreenButton) {
    return;
  }

  fullscreenButton.textContent = document.fullscreenElement === player ? 'Выйти из полного экрана' : 'Во весь экран';
}

async function toggleFullscreen(player) {
  if (!document.fullscreenEnabled || typeof player.requestFullscreen !== 'function') {
    return;
  }

  if (document.fullscreenElement === player) {
    await document.exitFullscreen();
  } else {
    await player.requestFullscreen();
  }
}

export function initPresentationShow() {
  const players = Array.from(document.querySelectorAll('[data-presentation-player]'));

  players.forEach((player) => {
    const fullscreenButton = player.querySelector('[data-presentation-fullscreen]');

    setActiveSlide(player, 0);
    updateFullscreenButton(player);

    player.querySelector('[data-presentation-prev]')?.addEventListener('click', () => {
      moveSlide(player, -1);
    });

    player.querySelector('[data-presentation-next]')?.addEventListener('click', () => {
      moveSlide(player, 1);
    });

    if (fullscreenButton && (!document.fullscreenEnabled || typeof player.requestFullscreen !== 'function')) {
      fullscreenButton.hidden = true;
    }

    fullscreenButton?.addEventListener('click', () => {
      toggleFullscreen(player).catch(() => {});
    });
  });

  if (players.length === 0) {
    return;
  }

  document.addEventListener('fullscreenchange', () => {
    players.forEach(updateFullscreenButton);
  });

  document.addEventListener('keydown', (event) => {
    const player = document.querySelector('[data-presentation-player]');

    if (!player) {
      return;
    }

    if (event.key === 'ArrowLeft') {
      moveSlide(player, -1);
    }

    if (event.key === 'ArrowRight') {
      moveSlide(player, 1);
    }
  });
}
