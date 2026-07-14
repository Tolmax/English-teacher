# Project Structure

## Purpose

This document describes the full directory and file structure of the project.
It is intended as a navigation reference for both developers and AI agents.
When in doubt about where to place a file or find existing logic — consult this document first.

---

## Directory Tree

```
project/
│
├── index.php                        ← front controller, entry point for all requests
│
├── app/                             ← all server-side PHP application logic
│   ├── controllers/                 ← request handlers (one file per route group)
│   ├── models/                      ← database queries and data access (one file per entity)
│   ├── services/                    ← isolated application logic (uploads, images, editor data)
│   ├── validators/                  ← input validation and sanitization
│   ├── helpers/                     ← small reusable utility functions
│   └── core/                        ← framework internals: router, db connection, renderer, bootstrap
│
├── config/                          ← application configuration files
│   ├── app.php                      ← main app config: ROOT, HOST, environment constants
│   ├── database.php                 ← database connection settings (SQLite path, PDO options)
│   └── routes.php                   ← route definitions (URL → controller mapping)
│
├── database/                        ← database files and SQL scripts
│   ├── database.sqlite              ← SQLite database file (main data store)
│   ├── schema.sql                   ← full database schema (table definitions)
│   ├── seeds.sql                    ← seed data for development and testing
│   └── migrations/                  ← incremental SQL migration files
│
├── assets/                          ← all frontend static assets, for app and prototypes
│   ├── css/                         ← all project styles
│   │   ├── base/                    ← foundational styles loaded first
│   │   │   ├── vars.css             ← CSS custom properties (colors, spacing, fonts, etc.)
│   │   │   ├── reset.css            ← browser style normalization
│   │   │   └── base.css             ← global base styles (body, typography, defaults)
│   │   ├── blocks/                  ← reusable UI block styles (BEM blocks, one file per block)
│   │   ├── sections/                ← page section styles (one file per section)
│   │   └── utils/                   ← utility classes (sr-only, visually-hidden, etc.)
│   ├── js/                          ← all project JavaScript
│   │   ├── main.js                  ← entry point: imports and initializes all modules
│   │   └── modules/                 ← one file per JS feature/component
│   ├── img/                         ← raster images (jpg, png)
│   └── icons/                       ← SVG icons and sprite
│
├── uploads/                         ← user-uploaded files (images, attachments)
│                                       not tracked by git, writable by server
│
├── prototypes/                      ← static HTML prototypes (design/layout exploration)
│   ├── index.html                   ← prototype: main / home page
│   ├── page-post.html               ← prototype: single post page
│   ├── page-admin.html              ← prototype: admin panel page
│   └── page-login.html              ← prototype: login page
│
├── storage/                         ← server-side generated and temporary files
│   ├── logs/                        ← application error and event logs
│   ├── cache/                       ← cached data (rendered fragments, processed data)
│   └── temp/                        ← temporary files during processing (e.g. image resize)
│
├── templates/                       ← PHP template files (.tpl), display only, no business logic
│   ├── layouts/                     ← base page layouts (header + footer wrappers)
│   ├── partials/                    ← reusable template pieces (nav, sidebar, pagination)
│   ├── pages/                       ← full page templates (one file per page type)
│   └── components/                  ← small isolated UI components (card, button, alert)
│
├── docs/                            ← all project documentation
│   ├── project-structure.md         ← this file — directory map and file reference
│   ├── tech-stack.md                ← technology decisions and constraints
│   ├── architecture-rules.md        ← architectural principles and layer responsibilities
│   ├── code-style.md                ← general code style overview (links to specs/)
│   ├── database-schema.md           ← database tables, columns, and relationships
│   ├── security-rules.md            ← security requirements and practices
│   ├── change-policy.md             ← rules for making changes to the project
│   ├── dev-log.md                   ← development log and decisions history
│   │
│   ├── specs/                       ← language and technology specifications
│   │   ├── html.md                  ← HTML spec: semantics, ARIA, accessibility, forms
│   │   ├── css.md                   ← CSS spec: BEM, structure, variables, responsive
│   │   ├── js.md                    ← JS spec: modules, main.js, naming, async, Editor.js
│   │   └── php.md                   ← PHP spec: MVC, functions, PDO, routing, templates
│   │
│   ├── ai-rules/                    ← rules and instructions specifically for AI agents
│   │   └── ai-rules.md              ← how AI must behave, what to follow, what to avoid
│   │
│   ├── product/                     ← product planning and delivery documents
│   │   ├── product-overview.md      ← what the product is and what problem it solves
│   │   ├── features.md              ← full list of product features
│   │   ├── design-direction.md      ← visual and UX design direction for the project
│   │   ├── design-direction-template.md  ← template for writing design direction docs
│   │   ├── prototype-map.md         ← map of all prototype pages and their purpose
│   │   ├── dod-global.md            ← global definition of done (applies to all phases)
│   │   │
│   │   ├── features/                ← detailed specs per feature
│   │   │   ├── auth.md              ← authentication feature spec
│   │   │   ├── posts.md             ← posts feature spec
│   │   │   ├── upload.md            ← file upload feature spec
│   │   │   └── editor.md            ← Editor.js integration spec
│   │   │
│   │   ├── phases/                  ← development phase plans
│   │   │   ├── phase-1.md           ← phase 1 scope and tasks
│   │   │   ├── phase-2.md           ← phase 2 scope and tasks
│   │   │   └── phase-3.md           ← phase 3 scope and tasks
│   │   │
│   │   ├── dod/                     ← definition of done per phase
│   │   │   ├── dod-phase-1.md       ← done criteria for phase 1
│   │   │   ├── dod-phase-2.md       ← done criteria for phase 2
│   │   │   └── dod-phase-3.md       ← done criteria for phase 3
│   │   │
│   │   └── tests/                   ← test cases per phase
│   │       ├── test-cases-phase-1.md
│   │       ├── test-cases-phase-2.md
│   │       └── test-cases-phase-3.md
│   │
│   └── design-refs/                 ← design reference images and visual materials
│
├── .gitignore                       ← git ignore rules
├── README.md                        ← project overview and setup instructions
└── TASK-template.md                 ← template for writing tasks for AI agents
```

---

## Layer Responsibilities

### `index.php`

Single entry point for all HTTP requests.
Bootstraps the application, loads config, initializes the router, dispatches the request to a controller.
Contains no business logic.

### `app/`

All server-side PHP application code. Organized by responsibility layer.

| Directory      | Responsibility                                                                      |
| -------------- | ----------------------------------------------------------------------------------- |
| `controllers/` | Receive request → call validator/model/service → pass data to template              |
| `models/`      | All SQL queries and database access. One file per entity (post, user, etc.)         |
| `services/`    | Isolated application logic: file uploads, image processing, Editor.js data handling |
| `validators/`  | Validate and sanitize external input. Return structured error arrays                |
| `helpers/`     | Small stateless utility functions reused across the project                         |
| `core/`        | Router, DB connection (PDO), template renderer, app bootstrap                       |

### `config/`

Configuration files loaded at startup.

| File           | Responsibility                                                                            |
| -------------- | ----------------------------------------------------------------------------------------- |
| `app.php`      | Defines `ROOT` (absolute path to project root) and `HOST` (base URL). Global app settings |
| `database.php` | Database driver, path to SQLite file, PDO connection options                              |
| `routes.php`   | All route definitions: method + URL pattern → controller + action                         |

### `database/`

Everything related to the database.

| File / Dir        | Responsibility                                                    |
| ----------------- | ----------------------------------------------------------------- |
| `database.sqlite` | The SQLite database file. Not committed to git in production      |
| `schema.sql`      | Authoritative schema. Reflects the current state of all tables    |
| `seeds.sql`       | Development seed data for populating the DB with test content     |
| `migrations/`     | Sequential SQL files for schema changes. Named by date or version |

### `assets/`

All frontend static files served directly to the browser.

#### `assets/css/`

Styles organized in layers, loaded in strict order:

1. `base/vars.css` — CSS variables (colors, spacing, typography, transitions)
2. `base/reset.css` — browser normalization
3. `base/base.css` — global element defaults
4. `blocks/` — BEM block styles (one file per block)
5. `sections/` — page section styles (one file per section)
6. `utils/` — utility classes (`sr-only`, etc.)

#### `assets/js/`

JavaScript organized as ES Modules.

| File       | Responsibility                                                                               |
| ---------- | -------------------------------------------------------------------------------------------- |
| `main.js`  | Entry point. Imports all modules and calls their `init*` functions inside `DOMContentLoaded` |
| `modules/` | One file per feature or component. Each exports an `init*` function                          |

#### `assets/img/`

Raster images used in the interface (jpg, png). User-uploaded files go to `uploads/`, not here.

#### `assets/icons/`

SVG icons. May be organized as individual files or an SVG sprite.

### `uploads/`

Stores all user-uploaded files (images, attachments).
This directory is writable by the server.
It is not committed to git — excluded via `.gitignore`.
File handling logic lives in `app/services/`.

### `prototypes/`

Standalone static HTML pages for design and layout exploration.
These are not connected to the PHP application.
Used as a visual reference during development.
Prototype assets (css, js, img) are self-contained and not shared with `assets/`.

### `storage/`

Server-generated files.

| Directory | Responsibility                                                                   |
| --------- | -------------------------------------------------------------------------------- |
| `logs/`   | Application error logs, request logs                                             |
| `cache/`  | Cached output or processed data to avoid repeated computation                    |
| `temp/`   | Temporary files created during processing (e.g. before image resize is complete) |

### `templates/`

PHP template files with `.tpl` extension.
Templates display data only — no SQL, no business logic, no state mutation.

| Directory     | Responsibility                                                                   |
| ------------- | -------------------------------------------------------------------------------- |
| `layouts/`    | Base page wrappers: full HTML document with header and footer                    |
| `partials/`   | Reusable template pieces included in layouts or pages (nav, sidebar, pagination) |
| `pages/`      | Full page templates, one per page type (home, post, login, admin)                |
| `components/` | Small isolated UI templates (post card, alert box, form field)                   |

### `.docs/`

All project documentation. Organized by type and audience.

| Path                          | Responsibility                                                         |
| ----------------------------- | ---------------------------------------------------------------------- |
| `project-structure.md`        | This file. Directory map and file reference for developers and AI      |
| `tech-stack.md`               | Technology decisions: what we use and what we explicitly do not use    |
| `architecture-rules.md`       | Architectural principles: layer responsibilities, data flow rules      |
| `code-style.md`               | General code style overview with links to per-language specs           |
| `database-schema.md`          | Full DB schema description: tables, columns, types, relationships      |
| `security-rules.md`           | Security requirements: input validation, output escaping, file uploads |
| `change-policy.md`            | Rules for making and documenting changes during development            |
| `dev-log.md`                  | Chronological log of decisions, changes, and implementation notes      |
| `specs/html.md`               | HTML spec: semantic tags, ARIA, accessibility, forms, images           |
| `specs/css.md`                | CSS spec: BEM naming, file structure, variables, responsive            |
| `specs/js.md`                 | JS spec: module system, main.js, naming, async/fetch, Editor.js        |
| `specs/php.md`                | PHP spec: MVC responsibilities, functions, PDO, routing, templates     |
| `ai-rules/ai-rules.md`        | Rules for AI agents: how to work in this project, what to follow       |
| `product/product-overview.md` | Product description: goals, audience, core functionality               |
| `product/features.md`         | Full list of features with brief descriptions                          |
| `product/design-direction.md` | Visual and UX direction: tone, style, layout principles                |
| `product/prototype-map.md`    | Map of all prototype pages and what they represent                     |
| `product/dod-global.md`       | Global definition of done applicable across all phases                 |
| `product/features/`           | Detailed specification per feature (auth, posts, upload, editor)       |
| `product/phases/`             | Scope and task list per development phase                              |
| `product/dod/`                | Definition of done per phase                                           |
| `product/tests/`              | Test cases per phase                                                   |
| `design-refs/`                | Visual reference materials and design assets                           |

---

## Key Rules for AI Agents

- **New PHP feature** → controller in `app/controllers/`, model in `app/models/`, service in `app/services/` if needed
- **New template** → `.tpl` file in `templates/pages/` or `templates/components/`
- **New CSS block** → one file in `assets/css/blocks/`
- **New CSS section** → one file in `assets/css/sections/`
- **New JS feature** → one file in `assets/js/modules/`, export `init*`, import in `main.js`
- **New route** → add to `config/routes.php` only
- **DB schema change** → update `database/schema.sql` and add a migration in `database/migrations/`
- **User-uploaded files** → always go to `uploads/`, never to `assets/`
- **Never put SQL in templates or controllers** — SQL belongs in `app/models/`
- **Never put business logic in templates** — templates display only
- **Do not create new directories** without a clear reason matching this structure
- **Specs are the source of truth** for all code style decisions → see `.docs/specs/`
