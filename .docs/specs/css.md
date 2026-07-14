# CSS Styles Spec

## Purpose
This file defines the CSS styling rules for the project.  
Goal: keep styles simple, predictable, maintainable, and consistent for both manual development and AI-assisted development.

## Stack
- Pure CSS only
- BEM naming
- CSS imports
- No SCSS / Sass / Less
- No Tailwind
- No CSS-in-JS
- No UI CSS frameworks
- No inline styles except rare technical cases

## CSS Structure
All styles are stored in:

`public/assets/css/`

Structure:

- `base/`
  - `vars.css` — variables
  - `reset.css` — reset styles
  - `base.css` — global base styles
- `blocks/` — reusable UI blocks
- `sections/` — page sections
- `utils/` — rare utility classes only if really needed

## Import Order
1. vars
2. reset
3. base
4. blocks
5. sections
6. utils

## Naming
Use BEM only:

- `block`
- `block__element`
- `block--modifier`

Rules:
- lowercase only
- words separated by hyphen
- class names describe meaning, not appearance
- no random abbreviations
- no ids for styling

## File Rules
- One file = one responsibility
- One block = one file
- One section = one file
- Do not mix unrelated styles in one file

## CSS Rules
- Keep selectors simple
- Prefer class selectors
- Avoid deep nesting
- Avoid high specificity
- Avoid global side effects
- Do not style through tag chains
- Do not build layout with hacks if flex/grid solves it

## Variables
Use CSS variables from `vars.css` for:
- colors
- spacing
- fonts
- radius
- shadows
- container widths
- transitions
- breakpoints if needed

Do not scatter raw values across the project without reason.

## Layout
Use:
- flex
- grid
- normal document flow

Do not overcomplicate layout.

## Responsive
Responsive design is required.

Use a small fixed set of breakpoints defined by the project.  
Media queries must stay near the block or section they belong to, not in separate chaotic files.

## Forms
Form elements must be styled consistently:
- input
- textarea
- select
- button
- label
- error message
- help text

States must be clear:
- hover
- focus
- active
- disabled
- error
- success
- loading

## Images and Icons
- Raster images: jpg, png
- Vector graphics and icons: svg
- Images must be responsive
- Preserve proportions unless intentionally changed

## AI Rules
AI must:
- follow this CSS structure
- use BEM only
- use existing variables
- place styles in correct folders
- keep responsive rules near related styles

AI must not:
- introduce new styling technologies
- rewrite all CSS for small changes
- mix unrelated styles in one file
- break naming conventions
- create chaotic utility classes
- use unnecessary specificity

## Definition of Done
CSS is complete when:
- files are in correct folders
- naming follows BEM
- styles are readable and maintainable
- variables are used consistently
- responsive behavior is implemented
- blocks are isolated
- no chaotic overrides
- result matches project design direction