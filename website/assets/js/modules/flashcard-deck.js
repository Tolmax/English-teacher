function shuffle(items) {
  const result = [...items];
  for (let index = result.length - 1; index > 0; index -= 1) {
    const target = Math.floor(Math.random() * (index + 1));
    [result[index], result[target]] = [result[target], result[index]];
  }
  return result;
}

function initDeck(deck) {
  const cards = Array.from(deck.querySelectorAll('[data-study-card]'));
  const stage = deck.querySelector('[data-deck-stage]');
  const actions = deck.querySelector('[data-deck-actions]');
  const complete = deck.querySelector('[data-deck-complete]');
  const remaining = deck.querySelector('[data-deck-remaining]');
  const learned = deck.querySelector('[data-deck-learned]');
  const repeats = deck.querySelector('[data-deck-repeats]');
  let queue = [];
  let learnedCount = 0;
  let repeatCount = 0;
  let busy = false;

  const updateStats = () => {
    remaining.textContent = String(queue.length);
    learned.textContent = String(learnedCount);
    repeats.textContent = String(repeatCount);
  };

  const resetCard = (card) => {
    card.classList.remove('study-card--flipped', 'study-card--correct', 'study-card--repeat');
    const button = card.querySelector('[data-card-flip]');
    button.setAttribute('aria-pressed', 'false');
    button.querySelector('.study-card__face--front').setAttribute('aria-hidden', 'false');
    button.querySelector('.study-card__face--back').setAttribute('aria-hidden', 'true');
  };

  const showCurrent = () => {
    cards.forEach((card) => { card.hidden = true; resetCard(card); });
    actions.hidden = true;
    busy = false;
    updateStats();
    if (queue.length === 0) {
      stage.hidden = true;
      complete.hidden = false;
      complete.querySelector('[data-deck-restart]').focus();
      return;
    }
    stage.hidden = false;
    complete.hidden = true;
    const card = cards[queue[0]];
    card.hidden = false;
    card.querySelector('[data-card-flip]').focus();
  };

  const answer = (correct) => {
    if (busy || queue.length === 0) return;
    busy = true;
    actions.hidden = true;
    const card = cards[queue[0]];
    card.classList.add(correct ? 'study-card--correct' : 'study-card--repeat');
    if (correct) {
      queue.shift();
      learnedCount += 1;
    } else {
      queue.push(queue.shift());
      repeatCount += 1;
    }
    updateStats();
    window.setTimeout(showCurrent, window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 430);
  };

  cards.forEach((card) => {
    const button = card.querySelector('[data-card-flip]');
    button.addEventListener('click', () => {
      if (busy) return;
      const flipped = card.classList.toggle('study-card--flipped');
      button.setAttribute('aria-pressed', String(flipped));
      button.querySelector('.study-card__face--front').setAttribute('aria-hidden', String(flipped));
      button.querySelector('.study-card__face--back').setAttribute('aria-hidden', String(!flipped));
      actions.hidden = !flipped;
    });
  });

  deck.querySelector('[data-deck-correct]').addEventListener('click', () => answer(true));
  deck.querySelector('[data-deck-repeat]').addEventListener('click', () => answer(false));
  deck.querySelector('[data-deck-restart]').addEventListener('click', () => {
    queue = shuffle(cards.map((_, index) => index));
    learnedCount = 0;
    repeatCount = 0;
    showCurrent();
  });

  queue = shuffle(cards.map((_, index) => index));
  showCurrent();
}

export function initFlashcardDecks() {
  document.querySelectorAll('[data-flashcard-deck]').forEach(initDeck);
}
