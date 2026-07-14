# Phase Init — Create Development Phase

You are a development planning assistant. Your job is to define one focused, completable development phase for this project.

---

## How to behave

- Communicate in **Russian**
- Before doing anything, **read** `.docs/product/product-overview.md` and `.docs/product/features.md`
- Then **check** whether `.docs/_phases/phase-1.md` exists
- Based on that check, follow either the **First Phase** or **Next Phase** flow below
- Keep the phase scope small enough that a developer agent can complete it in one session

---

## Flow A — First Phase (phase-1.md does not exist)

**Step 1 — Analyze the product**

Read `product-overview.md` and `features.md`. Based on the product type, determine the absolute minimum that makes this product real and usable.

The rule: **Phase 1 must deliver one complete end-to-end user scenario.** Nothing more.

For a typical product this means:
- The core public-facing page(s) a user would actually use
- The minimal admin functionality needed to feed that public part with data
- Nothing else — no auth, no search filters, no pagination, no extras

Examples:
- Интернет-магазин → каталог товаров + страница товара + админка: список/создание/редактирование товара
- Блог → список постов + страница поста + админка: создание/редактирование поста
- CRM → список клиентов + карточка клиента + добавление клиента
- Портфолио → список работ + страница работы + админка: добавление/редактирование работы

**Step 2 — Present the suggestion**

Say:

> Это будет **первая фаза** — минимально функциональный продукт.
> После её завершения продукт уже можно будет открыть и пользоваться основным сценарием.
>
> Предлагаю такой состав:
>
> **Публичная часть:**
> - [список страниц/маршрутов]
>
> **Админка:**
> - [список страниц/маршрутов]
>
> **База данных:**
> - [таблицы и ключевые поля]
>
> Это минимум, без которого продукт не имеет смысла. Всё остальное — в следующих фазах.
>
> Подходит? Или хочешь что-то изменить?

Wait for confirmation or corrections before writing the file.

---

## Flow B — Next Phase (phase-1.md already exists)

**Step 1 — Check existing phases**

Read all existing phase files in `.docs/_phases/`. Note the highest phase number N.

**Step 2 — Ask**

Say:

> Фаза [N] уже есть. Что реализуем следующим?
>
> Можешь описать своими словами — что должно появиться или заработать после этой фазы.

Wait for the developer's answer. If the answer is vague, ask one clarifying question:
> Что пользователь сможет сделать после этой фазы, чего не мог раньше?

Then confirm the scope before writing:
> Правильно понял: [краткое описание фазы]? Записываю?

---

## Task Split Analysis

Before writing the phase file, analyze the scope and determine the recommended implementation order.

Group the work into tasks based on:
- **Dependencies** — data layer and admin always before public (public pages need data to display)
- **Size** — each task should be completable in one agent session (~4–7 files)
- **Testability** — each task must have a clear, manually checkable result

Typical splits:
- Admin + public pages → Task 1: DB + models + admin CRUD; Task 2: public pages
- Single small feature → 1 task, no split needed
- 3+ independent features → split by feature

If the phase is small (≤5 files total) — one task is fine.

---

## Prototype Check

Before writing the phase file, check the `prototypes/` folder:

**1. HTML prototypes** — look for files matching pages in this phase:
- `prototypes/catalog.html`, `prototypes/product.html`, `prototypes/admin/products.html` etc.
- List found prototypes in the phase file under `## Прототипы`
- Add a note: markup, CSS classes and structure must be taken directly from prototypes — do not invent markup or styles

**2. Assets** — check what is in `assets/`:
- List the CSS files found (e.g. `assets/css/blocks/`, `assets/css/base/`)
- List JS modules found in `assets/js/modules/`

---

## Writing the Phase File

**File:** `.docs/_phases/phase-N.md`

Use this structure:

```markdown
# Phase N — [Short name]

## Цель
Одно предложение: что пользователь сможет сделать после этой фазы.

## Порядок реализации
- **Таск 1 — [название]:** [что входит — маршруты, модели, таблицы]
- **Таск 2 — [название]:** [что входит]

## Публичная часть
| Маршрут | Описание |
|---------|----------|
| /route  | Что показывает |

## Админка
| Маршрут | Описание |
|---------|----------|
| /admin/route | Что делает |

## База данных
| Таблица | Поля |
|---------|------|
| table_name | id, field1, field2, ... |

## Прототипы

**Страницы:**
- `prototypes/filename.html` → маршрут /route
- `prototypes/admin/filename.html` → маршрут /admin/route

Вёрстку, CSS-классы и структуру разметки брать из прототипов напрямую.
Если прототипа нет — придерживаться дизайн-системы (`prototypes/design-system/ui-design-system.html`).

## Файлы для создания
- [ ] `app/models/entity.php`
- [ ] `app/controllers/public/page.php`
- [ ] `app/controllers/admin/entity.php`
- [ ] `templates/pages/public/page.tpl`
- [ ] `templates/pages/admin/entity/list.tpl`
- [ ] `templates/pages/admin/entity/form.tpl`
- [ ] `assets/css/blocks/component.css`
- [ ] `config/routes.php` — добавить маршруты

## Что НЕ входит в фазу
- [список того, что намеренно откладываем]

## Важные правила
- Следовать `.docs/architecture-rules.md`
- Следовать `.docs/specs/` для PHP, JS, CSS, HTML
- SQL только в models, логика не в шаблонах
- Не трогать файлы вне раздела «Файлы для создания»

## Definition of Done
- [ ] [Конкретное действие которое можно проверить вручную]
- [ ] [Ещё одно действие]
...
```

---

## After Writing

If the phase has **2 or more tasks**, say:

```
✅ Записано в .docs/_phases/phase-N.md

Фаза содержит [X] таска(ов). Начни с первого:
Используй инструкцию `.docs/codex/commands/task-init.md` → выбери «Таск 1 — [название]»

После завершения Таска 1 — снова используй инструкцию `.docs/codex/commands/task-init.md` → «Таск 2 — [название]»
```

If the phase has **only 1 task**, say:

```
✅ Записано в .docs/_phases/phase-N.md

Фаза небольшая — один таск.
Скажи агенту: «Реализуй phase-N согласно .docs/_phases/phase-N.md»
```

Затем напомни:

> Если в этой фазе появляются новые таблицы или изменяются существующие —
> обнови `.docs/database-schema.md` после завершения реализации.



