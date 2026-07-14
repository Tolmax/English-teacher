# Phase 6 — База знаний для ИИ-помощника

## Цель

Учитель сможет хранить собственные учебные источники, прикреплять их к ИИ-заданию и использовать релевантные фрагменты этих источников при генерации материала.

## Порядок реализации

- **Таск 1 — Хранилище базы знаний:** выполнено — добавлены таблицы для источников базы знаний, модель, валидатор, загрузка файлов и админский список `/admin/ai-knowledge`.
- **Таск 2 — Прикрепление источников к ИИ-материалу:** выполнено — учитель выбирает источники базы знаний на форме ИИ-материала, связи сохраняются, список показывает привязки, копия переносит активные источники оригинала.
- **Таск 3 — Использование источников при генерации:** выполнено — добавлен локальный контекст из выбранных активных источников, безопасное ограничение длины и подключение контекста к OpenAI/mock-генерации; поля `openai_file_id` и `vector_store_id` оставлены как подготовка к будущему OpenAI file search/vector store.

## Публичная часть

| Маршрут | Описание |
|---------|----------|
| `/ai-materials` | Без изменений: показывает только опубликованные ИИ-материалы, созданные учителем. |
| `/class/{slug}` | Без изменений: показывает опубликованные материалы выбранного класса; источники базы знаний ученикам не открываются. |

## Админка

| Маршрут | Описание |
|---------|----------|
| `/admin/ai-knowledge` | Список источников базы знаний, фильтры по статусу/типу, загрузка нового источника. |
| `/admin/ai-knowledge/create` | Форма добавления источника: файл или текстовая заметка, название, описание, теги, статус. |
| `/admin/ai-knowledge/{id}/edit` | Редактирование метаданных источника и текстового содержимого, если оно извлечено или введено вручную. |
| `/admin/ai-knowledge/{id}/archive` | POST-действие архивирования источника без физического удаления файла. |
| `/admin/ai-knowledge/{id}/restore` | POST-действие восстановления источника. |
| `/admin/ai-materials/create` | Форма ИИ-материала позволяет выбрать источники базы знаний. |
| `/admin/ai-materials/{id}/edit` | Редактирование ИИ-материала позволяет изменить набор выбранных источников. |
| `/admin/ai-materials/{id}/duplicate` | Копия ИИ-материала переносит активные и неархивные источники оригинала. |
| `/admin/ai-materials/{id}/generate` | Генерация учитывает выбранные источники через локально подготовленный контекст. |

## База данных

| Таблица | Поля |
|---------|------|
| `ai_knowledge_sources` | `id`, `title`, `source_type`, `original_name`, `stored_name`, `storage_path`, `mime_type`, `file_size`, `extracted_text`, `description`, `tags`, `status`, `openai_file_id`, `vector_store_id`, `created_by`, `created_at`, `updated_at`, `archived_at` |
| `ai_material_knowledge_sources` | `id`, `material_id`, `knowledge_source_id`, `created_at` |

`openai_file_id` и `vector_store_id` добавляются как подготовка к будущей интеграции OpenAI file search/vector store. В этой фазе основной рабочий сценарий — локальный fallback: извлечённый или введённый текст источника ограничивается по длине и добавляется в генерационный запрос.

## Прототипы

**Страницы:**
- Отдельного прототипа базы знаний нет.
- `prototypes/admin/classes.html` → ориентир для админского списка `/admin/ai-knowledge`.
- `prototypes/admin/submissions.html` → ориентир для таблиц, фильтров, статусов и служебных сообщений.
- `prototypes/design-system/ui-design-system.html` → ориентир для alert, badge, card, input, table, form и button.

Вёрстку, CSS-классы и структуру разметки брать из существующих админских шаблонов и дизайн-системы.
Если прототипа нет — придерживаться дизайн-системы `prototypes/design-system/ui-design-system.html`.

**Assets:**
- CSS уже подключается через `assets/css/main.css`.
- Использовать существующие CSS-блоки: `assets/css/blocks/alert.css`, `badge.css`, `button.css`, `card.css`, `input.css`, `table.css`, `modal.css`, `nav.css`.
- Админские страницы продолжают использовать `assets/css/sections/prototype-pages.css`.
- JS-модули уже есть в `assets/js/modules/`: `accordion.js`, `class-video.js`, `modal.js`, `tabs.js`.
- Новый JS добавлять только если без него нельзя удобно выбрать несколько источников; предпочтительно сначала обойтись обычными checkbox/select в HTML.

## Файлы для создания или изменения

- [ ] `database/schema.sql`
- [ ] `database/migrations/`
- [ ] `.docs/database-schema.md`
- [ ] `.env.example`
- [ ] `app/models/ai-knowledge-source.php`
- [ ] `app/models/ai-material-knowledge-source.php`
- [ ] `app/models/ai-teaching-material.php`
- [ ] `app/validators/ai-knowledge-source.php`
- [ ] `app/validators/ai-teaching-material.php`
- [ ] `app/services/ai-knowledge-upload.php`
- [ ] `app/services/ai-knowledge-context.php`
- [ ] `app/services/openai-teacher-assistant.php`
- [ ] `app/controllers/admin/index.php`
- [ ] `app/controllers/admin/ai-knowledge/index.php`
- [ ] `app/controllers/admin/ai-knowledge/create.php`
- [ ] `app/controllers/admin/ai-knowledge/edit.php`
- [ ] `app/controllers/admin/ai-knowledge/archive.php`
- [ ] `app/controllers/admin/ai-knowledge/restore.php`
- [ ] `app/controllers/admin/ai-materials/create.php`
- [ ] `app/controllers/admin/ai-materials/edit.php`
- [ ] `app/controllers/admin/ai-materials/generate.php`
- [ ] `templates/partials/admin-header.tpl`
- [ ] `templates/pages/admin/ai-knowledge/index.tpl`
- [ ] `templates/pages/admin/ai-knowledge/form.tpl`
- [ ] `templates/pages/admin/ai-knowledge/edit.tpl`
- [ ] `templates/pages/admin/ai-materials/form.tpl`
- [ ] `templates/partials/header.tpl`
- [ ] `assets/css/blocks/nav.css`
- [ ] `assets/js/main.js`
- [ ] `assets/js/modules/mobile-menu.js`
- [ ] `AI_TEACHER_ASSISTANT_README.md`
- [ ] `AI_TEACHER_COMPLETION_REPORT.md`
- [ ] `.docs/project-status.md`
- [ ] `.docs/_phases/phase-6.md`
- [ ] `TASK.md`

## Что НЕ входит в фазу

- Не подключать настоящий OpenAI vector store как обязательный рабочий сценарий.
- Не делать полноценный semantic search по embeddings.
- Не передавать большие документы целиком в каждый запрос.
- Не делать PDF-экспорт.
- Не добавлять историю редакций источников.
- Не делать публичный просмотр источников базы знаний для учеников.
- Не создавать отдельный кабинет `/teacher`.
- Не хранить и не показывать `OPENAI_API_KEY` в БД, браузере, логах или документации.
- Не добавлять внешние библиотеки, SDK или CDN.
- Не поддерживать сложное извлечение текста из всех форматов сразу; если формат нельзя безопасно прочитать локально, источник должен храниться с ручным текстовым описанием или извлечённым текстом, введённым учителем.

## Важные правила

- Следовать `AGENTS.md`.
- Следовать `.docs/architecture-rules.md`.
- Следовать `.docs/specs/` для PHP, HTML, CSS и JS.
- SQL только в models, логика не в templates.
- Загрузка файлов — только через сервис, с проверкой размера, MIME-типа и расширения.
- Имена файлов санитизировать перед сохранением.
- Пользовательские строки в шаблонах выводить через `e()`.
- Источники базы знаний доступны только в админке.
- При генерации использовать только активные выбранные источники.
- Ограничивать суммарную длину контекста источников перед отправкой в OpenAI/mock.
- Не отправлять в OpenAI секреты, ключи, внутренние логи и технические данные.
- Сохранять mock-режим без `OPENAI_API_KEY`.
- Поля `openai_file_id` и `vector_store_id` не должны ломать локальный fallback, если они пустые.

## Definition of Done

- [ ] В БД есть таблицы `ai_knowledge_sources` и `ai_material_knowledge_sources`, схема отражена в `database/schema.sql` и `.docs/database-schema.md`.
- [ ] Учитель может открыть `/admin/ai-knowledge` и увидеть список источников базы знаний.
- [ ] Учитель может добавить источник базы знаний с названием, описанием, тегами и файлом или текстовым содержимым.
- [ ] Загружаемый файл проходит серверную валидацию типа, размера и имени.
- [ ] Учитель может редактировать метаданные источника и его текстовое содержимое.
- [ ] Учитель может архивировать и восстанавливать источник без физического удаления файла.
- [ ] Архивные источники не предлагаются для новых генераций.
- [ ] Форма создания ИИ-материала позволяет выбрать один или несколько источников базы знаний.
- [ ] Форма редактирования ИИ-материала показывает выбранные источники и позволяет изменить выбор.
- [ ] Связи ИИ-материала с источниками сохраняются в `ai_material_knowledge_sources`.
- [ ] Генерация ИИ-материала добавляет в prompt ограниченный локальный контекст из выбранных активных источников.
- [ ] Если источник слишком большой, в запрос попадает только безопасно ограниченный фрагмент.
- [ ] Mock-генерация остаётся рабочей и показывает, какие источники были учтены.
- [ ] OpenAI-режим не раскрывает `OPENAI_API_KEY` и не логирует содержимое ключа.
- [ ] Существующие ИИ-материалы без источников продолжают генерироваться как раньше.
- [ ] Изменённые PHP/TPL-файлы проходят `php -l`.
- [ ] Базовые сценарии проверены вручную: создать источник -> выбрать его в ИИ-материале -> сгенерировать -> увидеть влияние источника в результате.
