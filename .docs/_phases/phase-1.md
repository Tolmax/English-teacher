# Phase 1 — MVP учебного сайта

## Цель

После этой фазы учитель сможет публиковать классы, материалы и задания, ученик сможет открыть страницу своего класса, выполнить тренировку и отправить результат, а учитель сможет увидеть отправленные ответы в админке.

## Порядок реализации

- **Таск 1 — База данных, модели и базовая админка:** таблицы `users`, `classes`, `materials`, `practice_tasks`, `practice_questions`, `submissions`, `student_questions`; модели для чтения/создания/обновления данных; вход учителя; страницы админки для классов, материалов и ответов.
- **Таск 2 — Публичные страницы классов и материалов:** главная страница, страница класса, вывод домашки, материалов, объявлений и ссылки на тренировку.
- **Таск 3 — Тренировка и отправка результата:** публичная страница тренировки, проверка ответов, сохранение результата в `submissions`, отображение результата в админке.

## Публичная часть

| Маршрут | Описание |
|---------|----------|
| `/` | Главная страница: информация об учителе, выбор класса, краткое описание возможностей сайта. |
| `/class/{slug}` | Страница класса: актуальная домашка, материалы к уроку, объявления, форма вопроса учителю, переход к тренировке. |
| `/practice/{id}` | Страница тренировки: вопросы, варианты ответов, результат и отправка результата учителю. |

## Админка

| Маршрут | Описание |
|---------|----------|
| `/admin/login` | Вход учителя в админку. |
| `/admin/dashboard` | Сводка по классам, новым ответам учеников и вопросам. |
| `/admin/classes` | Список классов, материалы, домашка, статусы и переходы к ответам. |
| `/admin/submissions` | Список отправленных ответов учеников и просмотр результата. |

## База данных

| Таблица | Поля |
|---------|------|
| `users` | `id`, `email`, `password_hash`, `name`, `role`, `created_at`, `updated_at` |
| `classes` | `id`, `title`, `slug`, `description`, `is_active`, `created_at`, `updated_at` |
| `materials` | `id`, `class_id`, `type`, `title`, `description`, `content`, `status`, `deadline_at`, `is_published`, `created_at`, `updated_at` |
| `practice_tasks` | `id`, `class_id`, `title`, `description`, `is_published`, `created_at`, `updated_at` |
| `practice_questions` | `id`, `task_id`, `question`, `options_json`, `correct_option`, `explanation`, `sort_order` |
| `submissions` | `id`, `task_id`, `class_id`, `student_name`, `student_contact`, `score`, `answers_json`, `submitted_at`, `reviewed_at` |
| `student_questions` | `id`, `class_id`, `student_name`, `question`, `status`, `created_at`, `answered_at` |

## Прототипы

**Страницы:**
- `prototypes/home.html` → маршрут `/`
- `prototypes/class.html` → маршрут `/class/{slug}`
- `prototypes/practice.html` → маршрут `/practice/{id}`
- `prototypes/admin/login.html` → маршрут `/admin/login`
- `prototypes/admin/dashboard.html` → маршрут `/admin/dashboard`
- `prototypes/admin/classes.html` → маршрут `/admin/classes`
- `prototypes/admin/submissions.html` → маршрут `/admin/submissions`

Вёрстку, CSS-классы и структуру разметки брать из прототипов напрямую.
Если прототипа нет — придерживаться дизайн-системы (`prototypes/design-system/ui-design-system.html`).

**CSS:**
- `assets/css/main.css`
- `assets/css/base/reset.css`
- `assets/css/base/vars.css`
- `assets/css/base/base.css`
- `assets/css/blocks/alert.css`
- `assets/css/blocks/badge.css`
- `assets/css/blocks/button.css`
- `assets/css/blocks/card.css`
- `assets/css/blocks/input.css`
- `assets/css/blocks/modal.css`
- `assets/css/blocks/nav.css`
- `assets/css/blocks/table.css`
- `assets/css/sections/hero.css`
- `assets/css/sections/prototype-pages.css`
- `assets/css/utils/utils.css`

**JS-модули:**
- `assets/js/main.js`
- `assets/js/modules/accordion.js`
- `assets/js/modules/modal.js`
- `assets/js/modules/tabs.js`

## Файлы для создания

- [ ] `database/schema.sql` — добавить таблицы фазы
- [ ] `.docs/database-schema.md` — обновить описание новых таблиц после реализации
- [ ] `app/models/user.php`
- [ ] `app/models/class.php`
- [ ] `app/models/material.php`
- [ ] `app/models/practice.php`
- [ ] `app/models/submission.php`
- [ ] `app/models/student-question.php`
- [ ] `app/validators/auth.php`
- [ ] `app/validators/class.php`
- [ ] `app/validators/material.php`
- [ ] `app/validators/submission.php`
- [ ] `app/controllers/home.php`
- [ ] `app/controllers/class/show.php`
- [ ] `app/controllers/practice/show.php`
- [ ] `app/controllers/practice/submit.php`
- [ ] `app/controllers/admin/login.php`
- [ ] `app/controllers/admin/logout.php`
- [ ] `app/controllers/admin/dashboard.php`
- [ ] `app/controllers/admin/classes/index.php`
- [ ] `app/controllers/admin/materials/create.php`
- [ ] `app/controllers/admin/materials/edit.php`
- [ ] `app/controllers/admin/submissions/index.php`
- [ ] `app/controllers/admin/submissions/show.php`
- [ ] `templates/pages/home.tpl`
- [ ] `templates/pages/class/show.tpl`
- [ ] `templates/pages/practice/show.tpl`
- [ ] `templates/pages/admin/login.tpl`
- [ ] `templates/pages/admin/dashboard.tpl`
- [ ] `templates/pages/admin/classes/index.tpl`
- [ ] `templates/pages/admin/materials/form.tpl`
- [ ] `templates/pages/admin/submissions/index.tpl`
- [ ] `templates/pages/admin/submissions/show.tpl`
- [ ] `config/routes.php` — добавить маршруты фазы

## Что НЕ входит в фазу

- Личный кабинет ученика.
- Пароль или закрытый доступ для страниц классов.
- Полный календарь уроков и дедлайнов.
- Загрузка файлов учителем.
- Архив всех прошлых домашних заданий.
- Фильтрация материалов по темам на публичной странице.
- Сложная статистика успеваемости.
- Email-уведомления и внешние интеграции.
- Редактор rich-text на Editor.js.

## Важные правила

- Следовать `.docs/architecture-rules.md`.
- Следовать `.docs/app/ARCHITECTURE.md` перед созданием контроллеров.
- Следовать `.docs/specs/` для PHP, JS, CSS, HTML.
- SQL только в моделях.
- Логика не в шаблонах.
- Валидация через `app/validators/`.
- POST-действия заканчивать `redirectTo()` по PRG-паттерну.
- Весь вывод строк в шаблонах через `e()`.
- Разметку, CSS-классы и структуру брать из прототипов напрямую.
- Не трогать файлы вне раздела «Файлы для создания» без явной необходимости.

## Definition of Done

- [ ] Учитель может войти в админку через `/admin/login`.
- [ ] Учитель видит dashboard со сводкой по классам и последним ответам учеников.
- [ ] Учитель может создать или обновить класс.
- [ ] Учитель может создать или обновить материал/домашку для класса.
- [ ] Главная страница `/` показывает информацию об учителе и список классов.
- [ ] Страница `/class/{slug}` показывает домашку, материалы, объявления и ссылку на тренировку.
- [ ] Страница `/practice/{id}` показывает вопросы тренировки.
- [ ] Ученик может отправить результат тренировки с именем.
- [ ] Отправленный результат сохраняется в `submissions`.
- [ ] Учитель видит отправленные результаты на `/admin/submissions`.
- [ ] Проверены основные сценарии вручную: главная → класс → тренировка → отправка → админка ответов.
- [ ] После реализации обновлены `.docs/database-schema.md` и `database/schema.sql`.
