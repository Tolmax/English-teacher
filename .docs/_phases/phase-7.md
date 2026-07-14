# Phase 7 — Календарь, умная база знаний, интерактивные тесты и статистика

## Цель

Учитель сможет планировать уроки и дедлайны, удобнее работать с базой знаний, публиковать интерактивные тесты из ИИ-материалов и смотреть статистику выполнения по классам и темам.

## Порядок реализации

- **Таск 1 — Календарь уроков, дедлайнов и допзанятий:** выполнено — добавлены таблица календарных событий, модель, админский список/форма и публичный вывод ближайших событий на главной и странице класса.
- **Таск 2 — Улучшение базы знаний:** выполнено — добавлены категории источников, поиск по содержимому, страница просмотра источника, фильтр по категории и 10 тестовых источников в `/admin/ai-knowledge`.
- **Таск 3 — Интерактивные тесты из ИИ-материалов:** дать учителю превращать ИИ-материал в тест с вопросами, публиковать его для класса и принимать ответы учеников.
- **Таск 4 — Статистика учителя:** собрать экран статистики по классам, темам, тренировкам, ИИ-тестам и заявкам, чтобы учитель видел активность учеников.

## Публичная часть

| Маршрут | Описание |
|---------|----------|
| `/` | Показывает ближайшие общие события: уроки, дедлайны, доступные допзанятия. |
| `/class/{slug}` | Показывает события выбранного класса, дедлайны материалов и ссылки на опубликованные интерактивные тесты. |
| `/ai-tests/{slug}` | Публичная страница интерактивного теста из ИИ-материала: вопросы, варианты ответов, отправка результата. |
| `/practice/{id}` | Остаётся рабочим для существующих тренировок; статистика должна учитывать уже сохранённые ответы. |

## Админка

| Маршрут | Описание |
|---------|----------|
| `/admin/calendar` | Список календарных событий с фильтрами по классу, типу и дате. |
| `/admin/calendar/create` | Создание урока, дедлайна или события допзанятий. |
| `/admin/calendar/{id}/edit` | Редактирование календарного события. |
| `/admin/ai-knowledge` | Расширенный список источников с категориями, поиском по тексту и фильтрами. |
| `/admin/ai-knowledge/{id}` | Просмотр содержимого источника базы знаний без перехода в редактирование. |
| `/admin/ai-knowledge/{id}/edit` | Редактирование источника, категории, описания, тегов и текста. |
| `/admin/ai-materials/{id}/test` | Настройка интерактивного теста на основе ИИ-материала. |
| `/admin/ai-tests` | Список интерактивных ИИ-тестов, статусы публикации и быстрые действия. |
| `/admin/ai-tests/{id}/edit` | Редактирование вопросов, вариантов и правильных ответов теста. |
| `/admin/stats` | Сводная статистика по классам, темам, тренировкам, ИИ-тестам и допзанятиям. |

## База данных

| Таблица | Поля |
|---------|------|
| `calendar_events` | `id`, `class_id`, `title`, `event_type`, `description`, `starts_at`, `ends_at`, `is_published`, `created_at`, `updated_at` |
| `ai_knowledge_categories` | `id`, `title`, `slug`, `description`, `sort_order`, `created_at`, `updated_at` |
| `ai_knowledge_sources` | добавить `category_id` к существующим полям |
| `ai_interactive_tests` | `id`, `ai_material_id`, `class_id`, `title`, `slug`, `description`, `status`, `published_at`, `created_at`, `updated_at` |
| `ai_test_questions` | `id`, `test_id`, `question_text`, `question_type`, `sort_order`, `created_at`, `updated_at` |
| `ai_test_options` | `id`, `question_id`, `option_text`, `is_correct`, `sort_order`, `created_at`, `updated_at` |
| `ai_test_submissions` | `id`, `test_id`, `student_name`, `score`, `total_questions`, `answers_json`, `submitted_at` |

## Прототипы

**Страницы:**
- Отдельных прототипов календаря, базы знаний и ИИ-тестов нет.
- `prototypes/home.html` → ориентир для блока ближайших событий на `/`.
- `prototypes/class.html` → ориентир для блока событий класса и ссылок на тесты на `/class/{slug}`.
- `prototypes/practice.html` → ориентир для публичной страницы `/ai-tests/{slug}`.
- `prototypes/admin/classes.html` → ориентир для списков и админских таблиц `/admin/calendar`, `/admin/ai-tests`, `/admin/stats`.
- `prototypes/admin/submissions.html` → ориентир для статистики, отправок и служебных таблиц.
- `prototypes/design-system/ui-design-system.html` → ориентир для alert, badge, card, input, table, form и button.

Вёрстку, CSS-классы и структуру разметки брать из существующих прототипов и дизайн-системы напрямую.
Если прототипа нет — придерживаться дизайн-системы `prototypes/design-system/ui-design-system.html`.

**Assets:**
- CSS уже подключается через `assets/css/main.css`.
- Использовать существующие CSS-файлы: `assets/css/base/`, `assets/css/blocks/`, `assets/css/sections/`, `assets/css/utils/`.
- Существующие JS-модули: `assets/js/modules/accordion.js`, `class-video.js`, `mobile-menu.js`, `modal.js`, `tabs.js`.
- Новый JS добавлять только для интерактивного теста или удобного фильтра, если обычного HTML/PHP недостаточно.

## Файлы для создания или изменения

- [ ] `database/schema.sql`
- [ ] `database/migrations/`
- [ ] `.docs/database-schema.md`
- [ ] `app/models/calendar-event.php`
- [ ] `app/models/ai-knowledge-category.php`
- [ ] `app/models/ai-interactive-test.php`
- [ ] `app/models/statistics.php`
- [ ] `app/validators/calendar-event.php`
- [ ] `app/validators/ai-interactive-test.php`
- [ ] `app/controllers/admin/_router.php`
- [ ] `app/controllers/admin/calendar/index.php`
- [ ] `app/controllers/admin/calendar/create.php`
- [ ] `app/controllers/admin/calendar/edit.php`
- [ ] `app/controllers/admin/ai-knowledge/index.php`
- [ ] `app/controllers/admin/ai-knowledge/show.php`
- [ ] `app/controllers/admin/ai-knowledge/create.php`
- [ ] `app/controllers/admin/ai-knowledge/edit.php`
- [ ] `app/controllers/admin/ai-materials/test.php`
- [ ] `app/controllers/admin/ai-tests/index.php`
- [ ] `app/controllers/admin/ai-tests/edit.php`
- [ ] `app/controllers/admin/stats/index.php`
- [ ] `app/controllers/public/home.php`
- [ ] `app/controllers/public/class.php`
- [ ] `app/controllers/public/ai-tests/index.php`
- [ ] `app/controllers/public/ai-tests/show.php`
- [ ] `templates/partials/admin-header.tpl`
- [ ] `templates/pages/home.tpl`
- [ ] `templates/pages/class/show.tpl`
- [ ] `templates/pages/ai-tests/show.tpl`
- [ ] `templates/pages/admin/calendar/index.tpl`
- [ ] `templates/pages/admin/calendar/form.tpl`
- [ ] `templates/pages/admin/ai-knowledge/index.tpl`
- [ ] `templates/pages/admin/ai-knowledge/show.tpl`
- [ ] `templates/pages/admin/ai-knowledge/form.tpl`
- [ ] `templates/pages/admin/ai-tests/index.tpl`
- [ ] `templates/pages/admin/ai-tests/form.tpl`
- [ ] `templates/pages/admin/stats/index.tpl`
- [ ] `assets/css/blocks/calendar.css`
- [ ] `assets/css/blocks/stats.css`
- [ ] `assets/css/blocks/quiz.css`
- [ ] `assets/js/main.js`
- [ ] `assets/js/modules/ai-test.js`
- [ ] `AI_KNOWLEDGE_BASE_README.md`
- [ ] `.docs/project-status.md`
- [ ] `.docs/_phases/phase-7.md`
- [ ] `TASK.md`

## Что НЕ входит в фазу

- Не делать личный кабинет ученика.
- Не закрывать страницы классов паролем или логином.
- Не добавлять роли учеников и регистрацию.
- Не делать сложный календарь с drag-and-drop.
- Не подключать внешние календарные сервисы.
- Не делать полноценную аналитику по каждому ученику с личной историей за годы.
- Не подключать OpenAI vector store как обязательный сценарий.
- Не делать автоматическое превращение любого свободного текста в идеальный тест без проверки учителем.
- Не удалять существующие тренировки и форму ответов учеников.

## Важные правила

- Следовать `AGENTS.md`.
- Следовать `.docs/architecture-rules.md`.
- Следовать `.docs/specs/` для PHP, HTML, CSS, JS.
- SQL только в models, логика не в templates.
- Все защищённые маршруты админки начинать с `requireAuth()`.
- POST-действия заканчивать `redirectTo()`.
- Пользовательские строки в шаблонах выводить через `e()`.
- Не использовать React, Vue, Tailwind, Bootstrap, SCSS, jQuery и CDN.
- Не создавать отдельный `/teacher`-кабинет: всё остаётся внутри `/admin`.
- Интерактивный тест из ИИ-материала должен быть проверяемым и редактируемым учителем перед публикацией.

## Definition of Done

- [ ] Учитель может открыть `/admin/calendar`, создать урок/дедлайн/событие допзанятий и увидеть его в списке.
- [ ] Опубликованные события отображаются на главной и на странице нужного класса.
- [ ] База знаний поддерживает категории, фильтр по категории и поиск по названию, тегам, описанию и тексту источника.
- [ ] Учитель может открыть страницу просмотра источника `/admin/ai-knowledge/{id}` и прочитать его содержимое.
- [ ] Учитель может создать интерактивный тест из ИИ-материала, отредактировать вопросы и варианты ответов.
- [ ] Опубликованный тест открывается на публичном маршруте `/ai-tests/{slug}`.
- [ ] Ученик может пройти опубликованный тест, отправить имя и ответы, а результат сохраняется в БД.
- [ ] Учитель видит отправленные результаты интерактивных тестов в статистике или связанном списке.
- [ ] `/admin/stats` показывает сводку по классам, темам, тренировкам, ИИ-тестам и заявкам на допзанятия.
- [ ] Публичные страницы классов остаются открытыми без логина и пароля.
- [ ] Изменённые PHP/TPL-файлы проходят `php -l`.
- [ ] Если менялась БД, обновлены `database/schema.sql` и `.docs/database-schema.md`.
