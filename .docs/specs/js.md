# JavaScript Spec

## Purpose
This file defines the JavaScript rules for the project.
Goal: keep JS code simple, modular, readable, and predictable for both manual development and AI-assisted development.

## Stack
- Native JavaScript (ES2020+)
- ES Modules (`import` / `export`)
- No jQuery
- No React / Vue / Angular or any frontend framework
- No TypeScript
- No build tools (no Webpack / Vite / Babel) — unless explicitly added later
- No inline JS in HTML (`onclick`, `onchange`, etc.)
- External library exception: Editor.js (WYSIWYG editor)

---

## File Structure

All JavaScript lives in:

```
assets/js/
├── main.js              ← entry point, imports and initializes all modules
├── modules/
│   ├── modal.js         ← modal window logic
│   ├── tabs.js          ← tabs logic
│   ├── accordion.js     ← accordion / disclosure logic
│   ├── form.js          ← general form behavior (validation feedback, states)
│   ├── upload.js        ← file upload UI behavior
│   ├── editor.js        ← Editor.js initialization and data handling
│   ├── notify.js        ← notifications / toast messages
│   └── ...              ← one file per functionality
```

Rules:
- One file = one responsibility
- One module = one feature or component
- Do not mix unrelated logic in one file
- `main.js` only imports and calls init functions — it contains no logic itself
- New functionality = new module file

---

## main.js

`main.js` is the single entry point connected in HTML.
It imports all modules and initializes them.

```js
import { initModal } from './modules/modal.js';
import { initTabs } from './modules/tabs.js';
import { initAccordion } from './modules/accordion.js';
import { initForms } from './modules/form.js';
import { initUpload } from './modules/upload.js';
import { initEditor } from './modules/editor.js';

document.addEventListener('DOMContentLoaded', () => {
  initModal();
  initTabs();
  initAccordion();
  initForms();
  initUpload();
  initEditor();
});
```

Rules:
- All init calls happen inside `DOMContentLoaded`
- `main.js` must not contain inline logic — only imports and init calls
- Each module is responsible for checking if its elements exist on the page before running

---

## Module Structure

Each module must follow this pattern:

```js
// modules/modal.js

/**
 * Modal window — open, close, focus trap, keyboard handling.
 */

export function initModal() {
  const triggers = document.querySelectorAll('[data-modal]');
  if (!triggers.length) return;

  triggers.forEach(trigger => {
    trigger.addEventListener('click', () => openModal(trigger.dataset.modal));
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeAllModals();
  });
}

function openModal(id) {
  const modal = document.getElementById(id);
  if (!modal) return;
  modal.removeAttribute('hidden');
  modal.setAttribute('aria-modal', 'true');
  trapFocus(modal);
}

function closeAllModals() {
  document.querySelectorAll('[role="dialog"]').forEach(modal => {
    modal.setAttribute('hidden', '');
    modal.removeAttribute('aria-modal');
  });
}

function trapFocus(element) {
  // focus trap implementation
}
```

Rules:
- Every module exports one main `init*` function
- Internal helper functions are not exported unless reused elsewhere
- Module must guard against missing DOM elements at the top: `if (!element) return;`
- Each module is self-contained — it does not depend on globals from other modules

---

## Naming

### Files
- `kebab-case` for all file names: `modal.js`, `file-upload.js`, `editor-init.js`

### Variables and Functions
- `camelCase` for variables and functions
- Names must clearly describe what the variable holds or what the function does
- No random abbreviations

Good:
```js
const modalTrigger = document.querySelector('[data-modal]');
function openModal(id) { ... }
function closeModal(modal) { ... }
function trapFocus(container) { ... }
```

Bad:
```js
const el = document.querySelector('[data-modal]');
function handle(x) { ... }
function run() { ... }
function process() { ... }
```

### Constants
- `UPPER_SNAKE_CASE` for true constants (values that never change):
```js
const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
const ALLOWED_TYPES = ['image/jpeg', 'image/png'];
```

### Data Attributes as JS Hooks
- Use `data-*` attributes to target elements from JS — do not use styling classes as JS hooks:
```html
<button class="btn btn--primary" data-modal="login">Login</button>
```
```js
document.querySelectorAll('[data-modal]') // correct
document.querySelectorAll('.btn--primary') // wrong — styling class used as JS hook
```

---

## Functions

Rules:
- One function = one responsibility
- Keep functions short and focused
- Use early returns to avoid deep nesting
- Pass only required arguments
- Return a predictable result
- Avoid side effects inside functions where possible

Good:
```js
function getFormData(form) {
  return new FormData(form);
}

function showError(field, message) {
  const error = document.getElementById(field.dataset.errorId);
  if (!error) return;
  error.textContent = message;
  field.setAttribute('aria-invalid', 'true');
}

function clearError(field) {
  const error = document.getElementById(field.dataset.errorId);
  if (!error) return;
  error.textContent = '';
  field.removeAttribute('aria-invalid');
}
```

Bad:
```js
function handleEverything(e) {
  // 80 lines of mixed validation, DOM, fetch, and state logic
}
```

---

## DOM Manipulation

Rules:
- Query elements once and store in a variable — do not query the same element repeatedly
- Always check that an element exists before working with it
- Do not manipulate DOM inside loops when batch changes can be done instead
- Use `hidden` attribute or `aria-hidden` to show/hide elements — not `display:none` inline style

```js
// Correct — query once, guard, then use
const form = document.querySelector('#login-form');
if (!form) return;

// Show / hide via attribute
element.removeAttribute('hidden');
element.setAttribute('hidden', '');

// Add / remove state via class (when purely visual)
element.classList.add('is-active');
element.classList.remove('is-active');
element.classList.toggle('is-open');
```

State classes prefix:
- Use `is-*` prefix for JS-toggled state classes: `is-active`, `is-open`, `is-loading`, `is-error`
- State classes are used for visual representation only — logic state must be tracked in ARIA or data attributes

---

## Event Handling

Rules:
- Attach events via `addEventListener` — never via inline HTML attributes
- Prefer event delegation for dynamic or repeated elements
- Always remove event listeners when they are no longer needed (modals, popups)
- Use named functions instead of anonymous functions when the listener needs to be removed

```js
// Event delegation — correct for lists, repeated items
document.addEventListener('click', (e) => {
  if (e.target.closest('[data-modal]')) {
    openModal(e.target.closest('[data-modal]').dataset.modal);
  }
});

// Named function for removable listener
function handleKeydown(e) {
  if (e.key === 'Escape') closeModal();
}

document.addEventListener('keydown', handleKeydown);
// later:
document.removeEventListener('keydown', handleKeydown);
```

---

## Forms

Client-side form behavior is UI enhancement only.
All real validation happens server-side in PHP.

JS responsibilities for forms:
- Show/hide validation feedback from server response
- Provide real-time field hints (not blocking)
- Manage loading state during submission
- Handle AJAX form submission if needed

```js
// modules/form.js

export function initForms() {
  const forms = document.querySelectorAll('[data-form]');
  if (!forms.length) return;

  forms.forEach(form => {
    form.addEventListener('submit', handleSubmit);
  });
}

async function handleSubmit(e) {
  e.preventDefault();
  const form = e.currentTarget;

  setFormLoading(form, true);

  try {
    const response = await fetch(form.action, {
      method: form.method,
      body: new FormData(form),
    });

    const data = await response.json();

    if (!response.ok) {
      showFormErrors(form, data.errors);
      return;
    }

    handleFormSuccess(form, data);
  } catch (err) {
    showGlobalError(form);
  } finally {
    setFormLoading(form, false);
  }
}

function setFormLoading(form, state) {
  const btn = form.querySelector('[type="submit"]');
  if (!btn) return;
  btn.disabled = state;
  btn.setAttribute('aria-busy', String(state));
}

function showFormErrors(form, errors = {}) {
  Object.entries(errors).forEach(([field, message]) => {
    const input = form.querySelector(`[name="${field}"]`);
    if (!input) return;
    showError(input, message);
  });
}
```

---

## Fetch / Async

Rules:
- Use `fetch` for all async requests — no XMLHttpRequest
- Always use `async/await` — avoid raw `.then()` chains
- Always wrap `fetch` in `try/catch`
- Always check `response.ok` before reading data
- Always handle the loading state (disable button, show spinner)
- Always handle errors gracefully — show user-visible feedback

```js
async function fetchData(url, options = {}) {
  try {
    const response = await fetch(url, options);

    if (!response.ok) {
      throw new Error(`Server error: ${response.status}`);
    }

    return await response.json();
  } catch (err) {
    console.error('Fetch error:', err);
    throw err;
  }
}
```

JSON is the expected response format from the server.
Server must return `{ success: true, data: {...} }` or `{ success: false, errors: {...} }`.

---

## Editor.js

Editor.js is the only allowed external JS library for content editing.

```js
// modules/editor.js

import EditorJS from '/assets/vendor/editorjs/editorjs.umd.js';
import Header from '/assets/vendor/editorjs/header.umd.js';
import List from '/assets/vendor/editorjs/list.umd.js';
import Image from '/assets/vendor/editorjs/image.umd.js';

export function initEditor() {
  const holder = document.getElementById('editor');
  if (!holder) return;

  const editor = new EditorJS({
    holder: 'editor',
    tools: {
      header: Header,
      list: List,
      image: {
        class: Image,
        config: {
          endpoints: {
            byFile: '/upload/image',
          },
        },
      },
    },
    onChange: async () => {
      const data = await editor.save();
      syncEditorData(data);
    },
  });
}

function syncEditorData(data) {
  const input = document.getElementById('editor-data');
  if (!input) return;
  input.value = JSON.stringify(data);
}
```

Rules:
- Editor.js initialization lives in `modules/editor.js` only
- Editor data is saved to a hidden `<input>` field and submitted with the form
- Do not process Editor.js output in JS — processing happens server-side in PHP
- Do not mix Editor.js logic into form or other modules

---

## Notifications / Toasts

User-facing feedback messages (success, error, info) are managed through a central notify module:

```js
// modules/notify.js

export function initNotify() {
  // set up container on init if needed
}

export function notify(message, type = 'info') {
  const container = getOrCreateContainer();
  const toast = createToast(message, type);
  container.appendChild(toast);

  setTimeout(() => toast.remove(), 4000);
}

function getOrCreateContainer() {
  let container = document.getElementById('notify-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'notify-container';
    container.setAttribute('aria-live', 'polite');
    container.setAttribute('aria-atomic', 'false');
    document.body.appendChild(container);
  }
  return container;
}

function createToast(message, type) {
  const toast = document.createElement('div');
  toast.className = `notify notify--${type}`;
  toast.textContent = message;
  toast.setAttribute('role', 'status');
  return toast;
}
```

Rules:
- All in-page notifications go through `notify.js`
- `aria-live="polite"` is required on the notification container
- Toast messages auto-dismiss after a timeout
- Do not use `alert()`, `confirm()`, or `prompt()`

---

## Error Handling

Rules:
- Never swallow errors silently — always log or surface them
- `console.error()` for unexpected technical errors
- User-visible errors go through `notify()` or inline form feedback
- Do not expose stack traces or internal errors to the user
- Gracefully handle network failures (no internet, server down)

```js
// Do not do this:
try {
  await fetchData('/api/posts');
} catch (err) {
  // silently ignored ← wrong
}

// Correct:
try {
  await fetchData('/api/posts');
} catch (err) {
  console.error('Failed to load posts:', err);
  notify('Failed to load posts. Please try again.', 'error');
}
```

---

## Accessibility in JS

Rules:
- All dynamically added interactive elements must be keyboard accessible
- When opening a modal — move focus inside it
- When closing a modal — return focus to the trigger element
- Use `aria-expanded`, `aria-hidden`, `aria-modal`, `aria-busy` to reflect state changes
- Dynamic content updates must be announced via `aria-live` regions
- Do not remove focus outline in JS

```js
function openModal(modal, trigger) {
  modal.removeAttribute('hidden');
  modal.setAttribute('aria-modal', 'true');
  focusTrap.activate(modal);    // trap focus inside
  modal._trigger = trigger;     // remember trigger for restore
}

function closeModal(modal) {
  modal.setAttribute('hidden', '');
  modal.removeAttribute('aria-modal');
  focusTrap.deactivate();
  if (modal._trigger) modal._trigger.focus(); // restore focus
}
```

---

## What We Do Not Use

- No jQuery
- No frontend frameworks (React, Vue, Alpine, etc.)
- No TypeScript
- No build tools unless explicitly decided
- No `eval()`
- No `document.write()`
- No inline event handlers in HTML
- No `var` — use `const` and `let` only
- No global variables — keep everything inside modules
- No `alert()`, `confirm()`, `prompt()`

---

## AI Rules

AI must:
- place every new functionality in its own module file in `assets/js/modules/`
- export an `init*` function from every module
- import and call all init functions in `main.js`
- guard against missing DOM elements at the start of every init function
- use `const` and `let` — never `var`
- use `async/await` for all async operations
- wrap fetch calls in `try/catch`
- use `data-*` attributes as JS hooks — not styling classes
- keep ARIA state attributes in sync with JS state changes
- use `DOMContentLoaded` wrapper in `main.js`

AI must not:
- put logic directly in `main.js`
- mix multiple features in one module file
- use jQuery or any JS framework
- use inline event handlers in HTML
- use `var`
- use global variables outside modules
- use `alert()`, `confirm()`, `prompt()`
- swallow errors silently
- use styling classes as JS selectors for behavior
- rewrite unrelated modules when implementing a new feature

---

## Definition of Done

JavaScript is complete when:
- each feature lives in its own module file in `assets/js/modules/`
- each module exports an `init*` function
- all modules are imported and initialized in `main.js` inside `DOMContentLoaded`
- modules guard against missing elements on pages where they are not needed
- `const` / `let` used throughout — no `var`
- no global variables or logic outside modules
- async operations use `async/await` with `try/catch`
- ARIA attributes are updated in sync with JS state
- all interactive elements remain keyboard accessible
- no `alert()`, `confirm()`, `prompt()` used
- Editor.js data is synced to a hidden input before form submission
- code is readable, direct, and maintainable manually
