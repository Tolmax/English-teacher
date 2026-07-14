# Phase 2 — Наполнение и удобство учителя

## Цель

После этой фазы учитель сможет вести сайт ежедневно: прикреплять файлы к материалам, создавать тренировки с вопросами из админки и получать заявки на дополнительные занятия.

## Порядок реализации

- **Таск 1 — Файлы материалов и архив:** таблица `material_files`, загрузка PDF/изображений/документов к материалам, вывод файлов на странице класса, раздел прошлых материалов.
- **Таск 2 — Управление тренировками:** админские страницы для создания/редактирования тренировок и вопросов, вывод новых тренировок на странице класса и в `/practice/{id}`.
- **Таск 3 — Заявки на допзанятия:** таблица `extra_lesson_requests`, публичная форма заявки, админский список заявок и статус обработки.

## Публичная часть

| Маршрут | Описание |
|---------|----------|
| `/class/{slug}` | Показывает актуальные материалы, прикреплённые файлы, архив прошлых материалов и ссылки на тренировки. |
| `/practice/{id}` | Показывает тренировку, созданную учителем в админке. |
| `/extra-lessons` | Показывает информацию о допзанятиях и форму заявки ученика. |

## Админка

| Маршрут | Описание |
|---------|----------|
| `/admin/materials` | Список материалов с фильтрами по классу и типу. |
| `/admin/materials/create` | Создание материала с загрузкой файлов. |
| `/admin/materials/{id}/edit` | Редактирование материала и управление прикреплёнными файлами. |
| `/admin/practice` | Список тренировок. |
| `/admin/practice/create` | Создание тренировки. |
| `/admin/practice/{id}/edit` | Редактирование тренировки и её вопросов. |
| `/admin/extra-lessons` | Список заявок на дополнительные занятия. |
| `/admin/extra-lessons/{id}` | Просмотр заявки и смена статуса. |

## База данных

| Таблица | Поля |
|---------|------|
| `material_files` | `id`, `material_id`, `original_name`, `stored_name`, `mime_type`, `file_size`, `created_at` |
| `extra_lesson_requests` | `id`, `student_name`, `student_contact`, `class_title`, `topic`, `message`, `status`, `created_at`, `processed_at` |
| `practice_tasks` | уже есть: `id`, `class_id`, `title`, `description`, `is_published`, `created_at`, `updated_at` |
| `practice_questions` | уже есть: `id`, `task_id`, `question`, `options_json`, `correct_option`, `explanation`, `sort_order` |
| `materials` | уже есть; использовать `type`, `deadline_at`, `is_published`, `created_at` для актуальных и архивных материалов |

## Прототипы

**Страницы:**
- `prototypes/class.html` → маршрут `/class/{slug}`
- `prototypes/practice.html` → маршрут `/practice/{id}`
- `prototypes/extra-lessons.html` → маршрут `/extra-lessons`
- `prototypes/admin/classes.html` → ориентир для админских списков материалов и тренировок
- `prototypes/admin/submissions.html` → ориентир для списка заявок на допзанятия
- `prototypes/design-system/ui-design-system.html` → дизайн-система для новых форм и таблиц

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

- [ ] `database/schema.sql` — добавить таблицы `material_files`, `extra_lesson_requests`
- [ ] `.docs/database-schema.md` — обновить описание БД после реализации
- [ ] `app/models/material-file.php`
- [ ] `app/models/extra-lesson-request.php`
- [ ] `app/services/file-upload.php`
- [ ] `app/validators/material-file.php`
- [ ] `app/validators/practice.php`
- [ ] `app/validators/extra-lesson-request.php`
- [ ] `app/controllers/admin/materials/files.php`
- [ ] `app/controllers/admin/practice/index.php`
- [ ] `app/controllers/admin/practice/create.php`
- [ ] `app/controllers/admin/practice/edit.php`
- [ ] `app/controllers/admin/practice/questions.php`
- [ ] `app/controllers/extra-lessons/index.php`
- [ ] `app/controllers/admin/extra-lessons/index.php`
- [ ] `app/controllers/admin/extra-lessons/show.php`
- [ ] `templates/pages/admin/practice/index.tpl`
- [ ] `templates/pages/admin/practice/form.tpl`
- [ ] `templates/pages/admin/practice/questions.tpl`
- [ ] `templates/pages/extra-lessons/index.tpl`
- [ ] `templates/pages/admin/extra-lessons/index.tpl`
- [ ] `templates/pages/admin/extra-lessons/show.tpl`
- [ ] `templates/pages/class/show.tpl` — вывести файлы и архив
- [ ] `templates/pages/admin/materials/form.tpl` — добавить блок файлов
- [ ] `templates/pages/admin/materials/index.tpl` — добавить фильтры
- [ ] `config/routes.php` — добавить маршруты `extra-lessons` и админские разделы
- [ ] `app/controllers/admin/_router.php` — добавить разделы `practice` и `extra-lessons`

## Что НЕ входит в фазу

- Личный кабинет ученика.
- Пароль или закрытый доступ к страницам классов.
- Сложная аналитика успеваемости.
- Email-уведомления по заявкам и результатам.
- Полноценный календарь уроков и дедлайнов.
- Rich-text редактор на Editor.js.
- Массовая загрузка материалов.

## Важные правила

- Следовать `.docs/architecture-rules.md`.
- Следовать `.docs/specs/` для PHP, JS, CSS, HTML.
- SQL только в models, логика не в шаблонах.
- Валидация входных данных только через `app/validators/`.
- Загрузка файлов только через сервис в `app/services/`.
- Проверять тип, размер и имя файла перед сохранением.
- POST-действия завершать `redirectTo()` по PRG-паттерну.
- Не трогать файлы вне раздела «Файлы для создания» без явной необходимости.

## Definition of Done

- [ ] Учитель может загрузить файл к материалу через админку.
- [ ] Файлы материала видны ученику на странице класса.
- [ ] На странице класса есть раздел архива прошлых материалов.
- [ ] Учитель может создать тренировку в админке.
- [ ] Учитель может добавить, изменить и упорядочить вопросы тренировки.
- [ ] Новая тренировка доступна на странице класса и открывается через `/practice/{id}`.
- [ ] Ученик может отправить заявку на дополнительные занятия.
- [ ] Учитель видит заявки на `/admin/extra-lessons`.
- [ ] Учитель может отметить заявку обработанной.
- [ ] Обновлены `database/schema.sql` и `.docs/database-schema.md`.
- [ ] Проверены основные сценарии вручную: файл материала → страница класса; создание тренировки → прохождение; заявка → админка.
