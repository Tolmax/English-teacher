# HTML Spec

## Purpose
This file defines the HTML rules for the project.
Goal: keep markup semantic, accessible, readable, and consistent for both manual development and AI-assisted development.

## Stack
- HTML5 only
- Semantic tags where applicable
- ARIA attributes where native semantics are not enough
- No HTML frameworks or component libraries
- No inline styles
- No inline JavaScript (event handlers in attributes)
- No deprecated tags (`<font>`, `<center>`, `<b>`, `<i>` for semantics, etc.)

---

## Document Structure

Every HTML page must start with the correct declaration and meta set:

```html
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="...">
  <title>Page Title — Site Name</title>
  <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
  ...
</body>
</html>
```

Rules:
- `lang` attribute is required on `<html>` — always match the content language
- `charset` must be `UTF-8`
- `viewport` meta is required on every page
- `description` meta is required on every page
- `<title>` must be unique per page and descriptive
- CSS is connected in `<head>`, JS before `</body>` or with `defer`

---

## Semantic Structure

Use semantic HTML5 elements to describe the meaning of content, not its appearance.

### Page Layout
```html
<header>      <!-- site header: logo, navigation -->
<nav>         <!-- navigation block -->
<main>        <!-- main unique content of the page (one per page) -->
<aside>       <!-- sidebar, related content -->
<footer>      <!-- site or section footer -->
<section>     <!-- thematic section with a heading -->
<article>     <!-- self-contained independent content (post, card, comment) -->
```

Rules:
- One `<main>` per page
- `<header>` and `<footer>` can appear inside `<article>` and `<section>`
- `<section>` must have a heading (`<h1>`–`<h6>`)
- `<article>` is used for content that makes sense independently (post, comment, card)
- `<nav>` is used for primary and secondary navigation blocks
- `<aside>` is for content related to but separate from the main content

### Headings
- Use headings in logical order: `<h1>` → `<h2>` → `<h3>` etc.
- Do not skip heading levels
- One `<h1>` per page — the main page topic
- Do not use headings for styling purposes — use CSS classes instead

### Text Content
```html
<p>           <!-- paragraph -->
<ul> / <ol>   <!-- lists -->
<li>          <!-- list item -->
<dl>          <!-- definition list -->
<dt> / <dd>   <!-- term / description -->
<blockquote>  <!-- long quote from an external source -->
<cite>        <!-- reference to a source -->
<time>        <!-- date or time -->
<address>     <!-- contact information -->
<strong>      <!-- important text (semantic bold) -->
<em>          <!-- emphasized text (semantic italic) -->
<code>        <!-- inline code -->
<pre>         <!-- preformatted / code block -->
<mark>        <!-- highlighted/relevant text in context -->
<abbr>        <!-- abbreviation with title attribute -->
```

Do not use `<b>` or `<i>` for semantic purposes — use `<strong>` and `<em>`.
Use `<b>` and `<i>` only if no semantic meaning is implied and styling is the intent.

---

## Forms

### Structure
```html
<form action="/submit" method="post" novalidate>
  <fieldset>
    <legend>Group title</legend>

    <div class="form__field">
      <label for="email">Email</label>
      <input
        type="email"
        id="email"
        name="email"
        autocomplete="email"
        required
        aria-describedby="email-hint email-error"
      >
      <span id="email-hint" class="form__hint">We'll never share your email.</span>
      <span id="email-error" class="form__error" role="alert" aria-live="polite"></span>
    </div>

  </fieldset>
  <button type="submit">Submit</button>
</form>
```

Rules:
- Every `<input>`, `<select>`, `<textarea>` must have a paired `<label>` via `for` + `id`
- Never use placeholder as a replacement for `<label>`
- `autocomplete` attribute must be set where applicable
- Group related fields in `<fieldset>` with `<legend>`
- Error messages must be linked via `aria-describedby`
- Error messages must use `role="alert"` or `aria-live="polite"` for dynamic announcement
- Submit button must have an explicit `type="submit"`
- Use `novalidate` on `<form>` when handling validation manually in PHP/JS
- All required fields must have the `required` attribute

### Input Types
Use the correct `type` for each input:
- `type="email"` for email
- `type="password"` for passwords
- `type="tel"` for phone numbers
- `type="number"` for numeric input
- `type="url"` for URLs
- `type="search"` for search fields
- `type="date"` for dates
- `type="file"` for file uploads
- `type="checkbox"` / `type="radio"` for choices

---

## Images

```html
<!-- Raster image -->
<img
  src="/assets/img/photo.jpg"
  alt="Brief description of what is shown"
  width="800"
  height="600"
  loading="lazy"
>

<!-- Decorative image — empty alt, no ARIA -->
<img src="/assets/img/divider.svg" alt="" role="presentation">

<!-- Responsive image with multiple sources -->
<picture>
  <source srcset="/assets/img/hero.webp" type="image/webp">
  <img src="/assets/img/hero.jpg" alt="Hero image description" width="1440" height="600" loading="lazy">
</picture>

<!-- SVG icon inline -->
<svg aria-hidden="true" focusable="false" width="24" height="24">
  <use href="/assets/icons/sprite.svg#icon-name"></use>
</svg>
```

Rules:
- `alt` is required on every `<img>` — empty `alt=""` only for decorative images
- `width` and `height` attributes must be set to prevent layout shift (CLS)
- `loading="lazy"` for images below the fold; `loading="eager"` for above-the-fold critical images
- Use `<picture>` for modern formats (WebP) with fallback
- Inline SVG icons must have `aria-hidden="true"` and `focusable="false"` if they are decorative
- If SVG carries meaning — use `<title>` inside SVG or pair with visible/sr-only text

---

## Accessibility (a11y)

### Core Principles
- All interactive elements must be keyboard accessible (Tab, Enter, Space, Escape, Arrow keys)
- All interactive elements must have a visible focus state
- Color alone must not be the only way to convey information
- Text contrast must meet WCAG 2.1 AA minimum (4.5:1 for normal text, 3:1 for large text)
- Do not remove focus outline without providing a custom visible alternative

### Focus Management
- Do not use `tabindex` values greater than `0`
- `tabindex="0"` — makes an element focusable in natural DOM order (use sparingly)
- `tabindex="-1"` — removes from tab order but allows programmatic focus (e.g. modals)
- Modals must trap focus while open and restore focus to the trigger on close

### Hidden Content
```html
<!-- Hidden from everyone (CSS hidden) -->
<div hidden>...</div>

<!-- Visually hidden but accessible to screen readers -->
<span class="sr-only">Additional context for screen readers</span>

<!-- Hidden from screen readers, visible on screen (decorative) -->
<span aria-hidden="true">★★★★☆</span>
```

`sr-only` utility class must be defined in CSS for visually hidden content.

---

## ARIA

### When to Use ARIA
Use ARIA only when native HTML semantics are not sufficient.
Prefer native elements over ARIA roles whenever possible.

> Rule: No ARIA is better than wrong ARIA.

### ARIA Roles
Use roles only when the element's default role is incorrect or missing:

```html
<!-- Correct: native button -->
<button type="button">Click me</button>

<!-- Only if a non-button element must act as button -->
<div role="button" tabindex="0" aria-pressed="false">Custom button</div>
```

Common roles:
- `role="alert"` — dynamic important message (auto-announced)
- `role="status"` — non-urgent live region
- `role="dialog"` — modal or dialog window
- `role="navigation"` — navigation landmark (prefer `<nav>`)
- `role="banner"` — site header (prefer `<header>` at top level)
- `role="main"` — main content (prefer `<main>`)
- `role="contentinfo"` — footer (prefer `<footer>` at top level)
- `role="search"` — search form container
- `role="presentation"` / `role="none"` — removes element from accessibility tree

### ARIA States and Properties
```html
<!-- Interactive states -->
aria-expanded="true|false"     <!-- collapsible: open/closed -->
aria-checked="true|false"      <!-- checkbox/toggle state -->
aria-selected="true|false"     <!-- selected item in listbox/tabs -->
aria-pressed="true|false"      <!-- toggle button -->
aria-disabled="true"           <!-- disabled state (when attr disabled is not used) -->
aria-hidden="true"             <!-- hide from accessibility tree -->
aria-current="page|step|..."   <!-- current item in set (breadcrumbs, pagination) -->

<!-- Relationships -->
aria-label="..."               <!-- accessible name when visible label is absent -->
aria-labelledby="id"           <!-- points to visible element that labels this one -->
aria-describedby="id"          <!-- points to element that describes this one -->
aria-controls="id"             <!-- points to element this one controls -->
aria-owns="id"                 <!-- declares ownership of element outside DOM tree -->

<!-- Live regions -->
aria-live="polite|assertive"   <!-- dynamic content announcements -->
aria-atomic="true"             <!-- announce entire region on change -->
```

Rules:
- `aria-label` must not duplicate visible text — use `aria-labelledby` instead
- Do not add `aria-label` to a `<div>` or `<span>` without a role
- Do not use `aria-hidden="true"` on focusable elements
- Do not use `role="button"` on `<a>` if it navigates — use `<button>` instead

### Navigation and Landmarks
Every page must have clearly defined landmarks for screen reader navigation:
- One `<header>` (or `role="banner"`) at the top level
- One `<main>` (or `role="main"`)
- One `<footer>` (or `role="contentinfo"`) at the top level
- `<nav>` blocks must be uniquely labeled if there are more than one:
  ```html
  <nav aria-label="Main navigation">...</nav>
  <nav aria-label="Breadcrumbs">...</nav>
  ```

---

## Interactive Components

### Buttons vs Links
- `<button>` — triggers an action (submit form, open modal, toggle state)
- `<a href="...">` — navigates to a URL or anchor
- Never use `<a>` without `href` for actions — use `<button>`
- Never use `<div>` or `<span>` as interactive elements unless technically forced and ARIA is added

### Modals / Dialogs
```html
<dialog id="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
  <h2 id="modal-title">Dialog Title</h2>
  <p>Dialog content...</p>
  <button type="button" aria-label="Close dialog">✕</button>
</dialog>
```

Rules:
- Use native `<dialog>` where possible
- `aria-modal="true"` tells screen readers the rest of the page is inert
- `aria-labelledby` must point to the dialog heading
- Close button must have a descriptive `aria-label`
- Focus must be trapped inside while open

### Tabs
```html
<div role="tablist" aria-label="Content tabs">
  <button role="tab" aria-selected="true" aria-controls="panel-1" id="tab-1">Tab One</button>
  <button role="tab" aria-selected="false" aria-controls="panel-2" id="tab-2" tabindex="-1">Tab Two</button>
</div>
<div role="tabpanel" id="panel-1" aria-labelledby="tab-1">...</div>
<div role="tabpanel" id="panel-2" aria-labelledby="tab-2" hidden>...</div>
```

### Disclosure (Accordion)
```html
<button type="button" aria-expanded="false" aria-controls="section-body">
  Section Title
</button>
<div id="section-body" hidden>
  Content...
</div>
```

---

## Links

Rules:
- Link text must describe the destination — never use "click here" or "read more" alone
- If generic text is needed visually, add hidden context:
  ```html
  <a href="/posts/my-post">
    Read more <span class="sr-only">about My Post Title</span>
  </a>
  ```
- External links must indicate they open externally:
  ```html
  <a href="https://example.com" target="_blank" rel="noopener noreferrer">
    External site <span class="sr-only">(opens in new tab)</span>
  </a>
  ```
- `target="_blank"` must always be paired with `rel="noopener noreferrer"`

---

## Tables

Use `<table>` only for tabular data, never for layout.

```html
<table>
  <caption>Monthly sales report</caption>
  <thead>
    <tr>
      <th scope="col">Month</th>
      <th scope="col">Sales</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <th scope="row">January</th>
      <td>1200</td>
    </tr>
  </tbody>
</table>
```

Rules:
- `<caption>` is required — describes the table purpose
- `<thead>` and `<tbody>` must be used
- Header cells use `<th>` with `scope="col"` or `scope="row"`
- Complex tables use `id` + `headers` attributes for cell association

---

## Naming and Attributes

### Classes
- Use BEM naming in sync with CSS spec
- Classes describe meaning, not appearance
- No random abbreviations

### IDs
- IDs must be unique within the page
- Use IDs for: labels (`for`/`id`), ARIA references, anchor links, JS hooks
- Do not use IDs for styling

### Data Attributes
- Use `data-*` attributes for JS hooks and state storage
- Never use `data-*` for styling triggers — use classes

### Custom JS Hooks
- Use `data-*` or dedicated class prefix (e.g. `js-*`) for JavaScript targets
- Never attach JS behavior to styling classes
  ```html
  <!-- Correct -->
  <button class="btn btn--primary js-open-modal" data-modal="login">Login</button>
  ```

---

## Performance

- Use `loading="lazy"` for images and iframes below the fold
- Use `<link rel="preload">` for critical above-the-fold resources if needed
- Avoid large DOM trees — keep nesting reasonable
- Do not output invisible or duplicate markup for layout purposes
- Use `<template>` for JS-rendered repeated structures if applicable

---

## AI Rules

AI must:
- follow semantic HTML5 structure
- use correct tags for their meaning
- add `alt` text to all images (empty `alt=""` only for decorative)
- use ARIA attributes when native semantics are insufficient
- keep forms accessible: paired labels, error messages with `aria-describedby`
- use `aria-label` / `aria-labelledby` where visible labels are absent
- preserve keyboard accessibility of all interactive elements
- use `<button>` for actions and `<a href>` for navigation
- set `width` and `height` on `<img>` elements
- add `lang` attribute to `<html>`

AI must not:
- use `<div>` or `<span>` for interactive elements without ARIA and keyboard support
- use `aria-hidden="true"` on focusable elements
- skip heading levels
- use placeholder as a label replacement
- use `<table>` for layout
- add `role="button"` to links that navigate
- use `tabindex` values greater than 0
- use `target="_blank"` without `rel="noopener noreferrer"`
- add unnecessary ARIA that duplicates native semantics
- write incorrect or meaningless `alt` text
- use deprecated HTML tags

---

## Definition of Done

HTML is complete when:
- document structure is valid and starts with `<!DOCTYPE html>`
- `lang`, `charset`, `viewport`, and `description` are present
- heading hierarchy is logical and sequential
- all semantic tags are used appropriately
- all images have `alt` attributes and size attributes
- all form fields have paired `<label>` elements
- ARIA is used correctly where needed
- all interactive elements are keyboard accessible
- no layout tables are used
- links have descriptive text
- external links use `rel="noopener noreferrer"`
- markup passes W3C HTML validation
- markup passes basic accessibility check (axe, WAVE, or equivalent)
