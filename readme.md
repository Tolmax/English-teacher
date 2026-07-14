# Start Project Codex Clean

Стартовая папка для нового проекта, подготовленная под работу с Codex.

## С чего начать

Открой:

- `START_HERE.md`

Это главный вход в процесс. В нём указан порядок запуска нового проекта, какие промпты отправлять Codex и какие файлы обновлять.

## Локальный запуск сайта

Открой терминал в корне проекта:

```powershell
cd "C:\Users\Пресс-служба\OneDrive\Рабочий стол\Codex\english"
```

Запусти встроенный PHP-сервер:

```powershell
php -S 127.0.0.1:8000
```

После запуска открой сайт в браузере:

```text
http://127.0.0.1:8000
```

Админка учителя:

```text
http://127.0.0.1:8000/admin/login
```

Данные входа по умолчанию:

```text
login: admin
password: admin
```

Чтобы остановить локальный сервер, вернись в терминал и нажми `Ctrl + C`.

## Локальный запуск с OpenAI API

1. Открой файл `.env` в корне проекта.

2. Вставь свой ключ в строку:

```text
OPENAI_API_KEY=твой_ключ_OpenAI
```

Кавычки не нужны. Сам ключ не отправляй в чат и не добавляй в документацию.

3. Проверь модель:

```text
OPENAI_MODEL=gpt-4.1-mini
```

4. Запускай сайт только через PHP-сервер, не через Live Server:

```powershell
php -S 127.0.0.1:8000
```

5. Открой диагностику после входа в админку:

```text
http://127.0.0.1:8000/admin/system-check
```

В строке `OpenAI режим` должно быть `OpenAI API`. Если там `mock`, значит PHP-сервер не видит `OPENAI_API_KEY` или ключ не вписан в `.env`.

## Что внутри

- PHP 8.x + SQLite + PDO, MVC без фреймворка.
- Готовая структура `app/`, `config/`, `templates/`, `assets/`, `database/`, `prototypes/`.
- Проектные правила в `AGENTS.md`.
- Текущий статус проекта в `.docs/project-status.md`.
- Подробная документация в `.docs/`.
- Codex-инструкции процесса в `.docs/codex/commands/`.
- Готовые промпты для чата в `.docs/codex/prompts/`.
- Режимы работы Codex в `.docs/codex/rules/`.
- Шаблоны документов в `.docs/templates/`.
- Чеклисты в `.docs/checklists/`.
- Дополнительные дизайн/frontend skills в `.codex/skills/`.

## Assets

В проекте одна директория с ассетами в корне проекта: `assets/`.

Плюсы:
- общий источник стилей и скриптов для прототипов и проекта;
- при создании нового компонента в прототипе его можно сразу использовать в проекте.

## Codex skills

- frontend-инструкции: `.codex/skills/frontend-design/SKILL.md`
- UI/UX-инструкции: `.codex/skills/ui-ux-pro-max/SKILL.md`

Если задача связана с frontend, UI, UX или дизайн-системой, используй эти файлы как дополнительный контекст вместе с `AGENTS.md` и `.docs/specs/`.

## Codex-инструкции процесса

Подробные инструкции лежат в `.docs/codex/commands/`:

- `design-direction-init.md`
- `design-system-init.md`
- `prototype-init.md`
- `prototype-map-init.md`
- `spec-init.md`
- `phase-init.md`
- `task-init.md`
- `phase-report-init.md`
- `dev-log-update.md`

## Готовые промпты

Для обычной работы удобнее использовать `.docs/codex/prompts/`:

- `01-start-session.md` — старт нового чата;
- `02-create-product-spec.md` — описание продукта и backlog;
- `03-create-design-direction.md` — дизайн-направление;
- `04-create-design-system.md` — дизайн-система;
- `05-create-prototypes.md` — HTML-прототипы;
- `06-create-prototype-map.md` — карта прототипов;
- `07-create-phase.md` — план фазы;
- `08-create-task.md` — TASK.md;
- `09-implement-task.md` — реализация TASK;
- `10-create-phase-report.md` — отчёт фазы;
- `11-update-dev-log.md` — журнал разработки;
- `12-review-code.md` — ревью;
- `13-fix-bug.md` — исправление бага.

## Документация

- `.docs/project-structure.md` — структура проекта.
- `.docs/app/ARCHITECTURE.md` — архитектура приложения.
- `.docs/specs/` — правила PHP, HTML, CSS, JS.
- `.docs/product/` — описание продукта, backlog, карта прототипов.
- `.docs/design/` — дизайн-направление и дизайн-система.
- `.docs/_phases/` — файлы фаз, задач и отчётов по фазам.
