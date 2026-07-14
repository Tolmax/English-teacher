# Tech Stack

## Backend
- PHP 8.x
- SQLite
- PDO
- Router
- MVC
- Templates with .tpl

## Frontend
- HTML
- CSS
- Native JavaScript (ES modules)
- JS-библиотеки — допускаются при необходимости, подключаются как ES-модули (скачиваются локально, не через CDN)

## Editor
- Editor.js

## Images
- GD library
- JPG / PNG only for raster images
- SVG for icons and vector graphics

## Project rules
- no frontend frameworks
- no Tailwind
- no SCSS
- no jQuery
- no ORM
- no complex OOP except PDO or external libraries

## CSS rules
- BEM naming
- CSS imports
- base / blocks / sections / utils
- nested media queries inside selectors

## Database
- SQLite only for current phase

## Upload rules
- image validation required
- generate 2x versions for retina where needed
- sanitize file names

## Что используем

- PHP
- SQLite
- PDO
- MVC
- шаблоны `.tpl`
- Editor.js
- GD
- SVG
- native JS
- CSS + BEM

## Что не используем

Это критически важно. Часто именно этого не хватает.

Например:

- не используем React
- не используем Tailwind
- не используем SCSS
- не используем jQuery
- не используем ORM
- не используем Composer-пакеты без необходимости
- не используем сложный ООП в бизнес-логике
- не используем MySQL на этапе MVP
- не используем внешние UI-библиотеки

Раздел “не используем” делает поведение LLM заметно стабильнее.