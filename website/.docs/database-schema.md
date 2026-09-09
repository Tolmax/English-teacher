# Database Schema

Актуальная схема базы данных учебного сайта учителя английского.

---

## Правила

- База: SQLite, файл `database/database.sqlite`.
- Источник схемы: `database/schema.sql`.
- Все таблицы используют `id INTEGER PRIMARY KEY AUTOINCREMENT`.
- Даты хранятся как `TEXT` в формате SQLite `datetime('now')` или `YYYY-MM-DD` для дедлайна.
- Внешние ключи включены через `PRAGMA foreign_keys = ON` в `app/core/db.php`.
- SQL находится только в моделях из `app/models/`.
- Загруженные файлы материалов хранятся на диске в `uploads/materials/{material_id}/`, метаданные — в `material_files`.

---

## Таблицы

### users

Учётные записи учителя для входа в админку.

| Поле | Тип | Ограничения |
|------|-----|-------------|
| id | INTEGER | PRIMARY KEY AUTOINCREMENT |
| email | TEXT | NOT NULL UNIQUE |
| login | TEXT | NULL UNIQUE; если задан, используется вместо email при входе |
| password_hash | TEXT | NOT NULL |
| name | TEXT | NOT NULL |
| role | TEXT | NOT NULL DEFAULT 'teacher' |
| created_at | TEXT | NOT NULL DEFAULT datetime('now') |
| updated_at | TEXT | NOT NULL DEFAULT datetime('now') |

### classes

Классы, для которых создаются публичные страницы.

| Поле | Тип | Ограничения |
|------|-----|-------------|
| id | INTEGER | PRIMARY KEY AUTOINCREMENT |
| title | TEXT | NOT NULL |
| slug | TEXT | NOT NULL UNIQUE |
| description | TEXT | NOT NULL DEFAULT '' |
| is_active | INTEGER | NOT NULL DEFAULT 1 |
| created_at | TEXT | NOT NULL DEFAULT datetime('now') |
| updated_at | TEXT | NOT NULL DEFAULT datetime('now') |

### materials

Домашние задания, объявления и учебные материалы для классов.

| Поле | Тип | Ограничения |
|------|-----|-------------|
| id | INTEGER | PRIMARY KEY AUTOINCREMENT |
| class_id | INTEGER | NOT NULL REFERENCES classes(id) ON DELETE CASCADE |
| type | TEXT | NOT NULL DEFAULT 'material' |
| title | TEXT | NOT NULL |
| description | TEXT | NOT NULL DEFAULT '' |
| content | TEXT | NOT NULL DEFAULT '' |
| status | TEXT | NOT NULL DEFAULT 'new' |
| deadline_at | TEXT | NULL |
| is_published | INTEGER | NOT NULL DEFAULT 1 |
| created_at | TEXT | NOT NULL DEFAULT datetime('now') |
| updated_at | TEXT | NOT NULL DEFAULT datetime('now') |

### material_files

Файлы, прикреплённые к материалам.

| Поле | Тип | Ограничения |
|------|-----|-------------|
| id | INTEGER | PRIMARY KEY AUTOINCREMENT |
| material_id | INTEGER | NOT NULL REFERENCES materials(id) ON DELETE CASCADE |
| original_name | TEXT | NOT NULL |
| stored_name | TEXT | NOT NULL |
| mime_type | TEXT | NOT NULL |
| file_size | INTEGER | NOT NULL DEFAULT 0 |
| created_at | TEXT | NOT NULL DEFAULT datetime('now') |

### ai_teaching_materials

Черновики и результаты материалов, которые учитель готовит с помощью ИИ в существующей админке.

| Поле | Тип | Ограничения |
|------|-----|-------------|
| id | INTEGER | PRIMARY KEY AUTOINCREMENT |
| title | TEXT | NOT NULL |
| slug | TEXT | NOT NULL UNIQUE |
| subject | TEXT | NOT NULL |
| class_title | TEXT | NOT NULL |
| topic | TEXT | NOT NULL |
| material_type | TEXT | NOT NULL |
| test_template | TEXT | NOT NULL DEFAULT 'quick_check' |
| difficulty | TEXT | NOT NULL DEFAULT '' |
| estimated_duration | TEXT | NOT NULL DEFAULT '' |
| language | TEXT | NOT NULL DEFAULT 'ru' |
| instructions | TEXT | NOT NULL DEFAULT '' |
| source_notes | TEXT | NOT NULL DEFAULT '' |
| generated_content | TEXT | NOT NULL DEFAULT '' |
| edited_content | TEXT | NOT NULL DEFAULT '' |
| status | TEXT | NOT NULL DEFAULT 'draft' |
| generation_error | TEXT | NOT NULL DEFAULT '' |
| openai_response_id | TEXT | NOT NULL DEFAULT '' |
| model_used | TEXT | NOT NULL DEFAULT '' |
| prompt_version | TEXT | NOT NULL DEFAULT 'v1' |
| school_year | TEXT | NOT NULL DEFAULT '' |
| period | TEXT | NOT NULL DEFAULT '' |
| tags | TEXT | NOT NULL DEFAULT '' |
| archived_at | TEXT | NULL |
| source_material_id | INTEGER | NULL REFERENCES ai_teaching_materials(id) ON DELETE SET NULL |
| published_at | TEXT | NULL |
| created_by | INTEGER | NULL REFERENCES users(id) ON DELETE SET NULL |
| created_at | TEXT | NOT NULL DEFAULT datetime('now') |
| updated_at | TEXT | NOT NULL DEFAULT datetime('now') |

Поля библиотеки:

- `school_year` — учебный год или сезон, например `2026/2027`.
- `period` — четверть, месяц, раздел учебника или иной учебный период.
- `tags` — свободные теги через запятую для будущего поиска и фильтрации.
- `archived_at` — дата переноса материала в архив; архивирование не удаляет текст материала.
- `source_material_id` — ссылка на исходный ИИ-материал, если запись создана как копия для повторного использования.
- `test_template` — ключ шаблона ИИ-теста. По умолчанию `quick_check`; доступные ключи задаются централизованно в `app/services/ai-test-templates.php`.

### ai_material_files

Файлы, связанные с ИИ-материалами: будущие источники, вложения и экспорты.

| Поле | Тип | Ограничения |
|------|-----|-------------|
| id | INTEGER | PRIMARY KEY AUTOINCREMENT |
| material_id | INTEGER | NOT NULL REFERENCES ai_teaching_materials(id) ON DELETE CASCADE |
| file_type | TEXT | NOT NULL |
| original_name | TEXT | NOT NULL |
| stored_name | TEXT | NOT NULL |
| storage_path | TEXT | NOT NULL |
| mime_type | TEXT | NOT NULL |
| file_size | INTEGER | NOT NULL DEFAULT 0 |
| created_at | TEXT | NOT NULL DEFAULT datetime('now') |

### ai_word_presentations

Черновики ИИ-презентаций слов. Учитель вводит до 10 русских слов, а следующие задачи фазы используют эти данные для генерации английских слов, транскрипции, картинок и PPTX-файла.

| Поле | Тип | Ограничения |
|------|-----|-------------|
| id | INTEGER | PRIMARY KEY AUTOINCREMENT |
| material_id | INTEGER | NULL REFERENCES materials(id) ON DELETE SET NULL |
| class_id | INTEGER | NOT NULL REFERENCES classes(id) ON DELETE CASCADE |
| title | TEXT | NOT NULL |
| source_words | TEXT | NOT NULL DEFAULT '[]' |
| cards_json | TEXT | NOT NULL DEFAULT '[]' |
| pptx_file_id | INTEGER | NULL REFERENCES material_files(id) ON DELETE SET NULL |
| status | TEXT | NOT NULL DEFAULT 'draft' |
| model | TEXT | NOT NULL DEFAULT '' |
| response_id | TEXT | NOT NULL DEFAULT '' |
| created_at | TEXT | NOT NULL DEFAULT datetime('now') |
| updated_at | TEXT | NOT NULL DEFAULT datetime('now') |

Поля:

- `source_words` — JSON-массив исходных русских слов, введённых учителем.
- `cards_json` — будущие редактируемые карточки презентации: русское слово, английское слово, транскрипция, описание картинки.
- `material_id` — ссылка на обычный материал класса после публикации презентации.
- `pptx_file_id` — ссылка на файл презентации в `material_files` после генерации PPTX.
- `status` — `draft`, `ready` или `published`.

### ai_knowledge_categories

Категории источников базы знаний. Нужны для фильтрации источников и более удобной навигации учителя.

| Поле | Тип | Ограничения |
|------|-----|-------------|
| id | INTEGER | PRIMARY KEY AUTOINCREMENT |
| title | TEXT | NOT NULL |
| slug | TEXT | NOT NULL UNIQUE |
| description | TEXT | NOT NULL DEFAULT '' |
| sort_order | INTEGER | NOT NULL DEFAULT 0 |
| created_at | TEXT | NOT NULL DEFAULT datetime('now') |
| updated_at | TEXT | NOT NULL DEFAULT datetime('now') |

Базовые категории: `Vocabulary`, `Grammar`, `Speaking`, `Reading`, `Writing`.

### ai_knowledge_sources

Источники базы знаний ИИ-помощника: учебные тексты, файлы, конспекты и примеры, которые учитель хранит в админке для будущего прикрепления к ИИ-материалам.

Файлы источников хранятся на диске в `uploads/ai-knowledge/`, в таблице сохраняются только метаданные и относительный путь. Архивирование не удаляет файл физически.

| Поле | Тип | Ограничения |
|------|-----|-------------|
| id | INTEGER | PRIMARY KEY AUTOINCREMENT |
| category_id | INTEGER | NULL REFERENCES ai_knowledge_categories(id) ON DELETE SET NULL |
| title | TEXT | NOT NULL |
| source_type | TEXT | NOT NULL DEFAULT 'text' |
| original_name | TEXT | NOT NULL DEFAULT '' |
| stored_name | TEXT | NOT NULL DEFAULT '' |
| storage_path | TEXT | NOT NULL DEFAULT '' |
| mime_type | TEXT | NOT NULL DEFAULT '' |
| file_size | INTEGER | NOT NULL DEFAULT 0 |
| extracted_text | TEXT | NOT NULL DEFAULT '' |
| description | TEXT | NOT NULL DEFAULT '' |
| tags | TEXT | NOT NULL DEFAULT '' |
| status | TEXT | NOT NULL DEFAULT 'active' |
| openai_file_id | TEXT | NOT NULL DEFAULT '' |
| vector_store_id | TEXT | NOT NULL DEFAULT '' |
| created_by | INTEGER | NULL REFERENCES users(id) ON DELETE SET NULL |
| created_at | TEXT | NOT NULL DEFAULT datetime('now') |
| updated_at | TEXT | NOT NULL DEFAULT datetime('now') |
| archived_at | TEXT | NULL |

`openai_file_id` и `vector_store_id` подготовлены для будущей интеграции OpenAI file search/vector store, но в текущей реализации не обязательны.

### ai_material_knowledge_sources

Связующая таблица для будущего прикрепления источников базы знаний к ИИ-материалам.

| Поле | Тип | Ограничения |
|------|-----|-------------|
| id | INTEGER | PRIMARY KEY AUTOINCREMENT |
| material_id | INTEGER | NOT NULL REFERENCES ai_teaching_materials(id) ON DELETE CASCADE |
| knowledge_source_id | INTEGER | NOT NULL REFERENCES ai_knowledge_sources(id) ON DELETE CASCADE |
| created_at | TEXT | NOT NULL DEFAULT datetime('now') |

Пара `material_id`, `knowledge_source_id` уникальна.

### practice_tasks

Тренировочные задания для класса.

| Поле | Тип | Ограничения |
|------|-----|-------------|
| id | INTEGER | PRIMARY KEY AUTOINCREMENT |
| class_id | INTEGER | NOT NULL REFERENCES classes(id) ON DELETE CASCADE |
| title | TEXT | NOT NULL |
| description | TEXT | NOT NULL DEFAULT '' |
| is_published | INTEGER | NOT NULL DEFAULT 1 |
| created_at | TEXT | NOT NULL DEFAULT datetime('now') |
| updated_at | TEXT | NOT NULL DEFAULT datetime('now') |

### practice_questions

Вопросы внутри тренировочного задания.

| Поле | Тип | Ограничения |
|------|-----|-------------|
| id | INTEGER | PRIMARY KEY AUTOINCREMENT |
| task_id | INTEGER | NOT NULL REFERENCES practice_tasks(id) ON DELETE CASCADE |
| question | TEXT | NOT NULL |
| options_json | TEXT | NOT NULL |
| correct_option | INTEGER | NOT NULL DEFAULT 0 |
| explanation | TEXT | NOT NULL DEFAULT '' |
| sort_order | INTEGER | NOT NULL DEFAULT 0 |

### submissions

Отправленные учениками результаты тренировок.

| Поле | Тип | Ограничения |
|------|-----|-------------|
| id | INTEGER | PRIMARY KEY AUTOINCREMENT |
| task_id | INTEGER | NOT NULL REFERENCES practice_tasks(id) ON DELETE CASCADE |
| class_id | INTEGER | NOT NULL REFERENCES classes(id) ON DELETE CASCADE |
| student_name | TEXT | NOT NULL |
| student_contact | TEXT | NOT NULL DEFAULT '' |
| score | INTEGER | NOT NULL DEFAULT 0 |
| answers_json | TEXT | NOT NULL DEFAULT '[]' |
| submitted_at | TEXT | NOT NULL DEFAULT datetime('now') |
| reviewed_at | TEXT | NULL |

### student_questions

Вопросы учеников со страницы класса.

| Поле | Тип | Ограничения |
|------|-----|-------------|
| id | INTEGER | PRIMARY KEY AUTOINCREMENT |
| class_id | INTEGER | NOT NULL REFERENCES classes(id) ON DELETE CASCADE |
| student_name | TEXT | NOT NULL |
| question | TEXT | NOT NULL |
| status | TEXT | NOT NULL DEFAULT 'new' |
| created_at | TEXT | NOT NULL DEFAULT datetime('now') |
| answered_at | TEXT | NULL |

### extra_lesson_requests

Заявки учеников на дополнительные занятия.

| Поле | Тип | Ограничения |
|------|-----|-------------|
| id | INTEGER | PRIMARY KEY AUTOINCREMENT |
| student_name | TEXT | NOT NULL |
| student_contact | TEXT | NOT NULL |
| class_title | TEXT | NOT NULL DEFAULT '' |
| topic | TEXT | NOT NULL |
| message | TEXT | NOT NULL DEFAULT '' |
| status | TEXT | NOT NULL DEFAULT 'new' |
| created_at | TEXT | NOT NULL DEFAULT datetime('now') |
| processed_at | TEXT | NULL |

### calendar_events

Календарь уроков, дедлайнов, допзанятий и мероприятий. Событие может быть общим для всех классов (`class_id` пустой) или привязанным к конкретному классу. Для уроков хранится номер урока, домашнее задание и опциональная привязка к материалу.

| Поле | Тип | Ограничения |
|------|-----|-------------|
| id | INTEGER | PRIMARY KEY AUTOINCREMENT |
| class_id | INTEGER | NULL REFERENCES classes(id) ON DELETE SET NULL |
| material_id | INTEGER | NULL REFERENCES materials(id) ON DELETE SET NULL |
| title | TEXT | NOT NULL |
| event_type | TEXT | NOT NULL |
| description | TEXT | NOT NULL DEFAULT '' |
| starts_at | TEXT | NOT NULL |
| ends_at | TEXT | NULL |
| lesson_number | INTEGER | NULL |
| homework_content | TEXT | NOT NULL DEFAULT '' |
| is_published | INTEGER | NOT NULL DEFAULT 1 |
| created_at | TEXT | NOT NULL DEFAULT datetime('now') |
| updated_at | TEXT | NOT NULL DEFAULT datetime('now') |

### ai_interactive_tests

Интерактивные тесты, созданные учителем на основе ИИ-материалов.

| Поле | Тип | Ограничения |
|------|-----|-------------|
| id | INTEGER | PRIMARY KEY AUTOINCREMENT |
| ai_material_id | INTEGER | NOT NULL REFERENCES ai_teaching_materials(id) ON DELETE CASCADE |
| class_title | TEXT | NOT NULL DEFAULT '' |
| template_key | TEXT | NOT NULL DEFAULT 'quick_check' |
| title | TEXT | NOT NULL |
| slug | TEXT | NOT NULL UNIQUE |
| description | TEXT | NOT NULL DEFAULT '' |
| status | TEXT | NOT NULL DEFAULT 'draft' |
| published_at | TEXT | NULL |
| created_at | TEXT | NOT NULL DEFAULT datetime('now') |
| updated_at | TEXT | NOT NULL DEFAULT datetime('now') |

`template_key` хранит выбранный шаблон теста на момент создания интерактивного теста. Для старых записей используется `quick_check`.

### ai_test_questions

Вопросы интерактивных ИИ-тестов.

| Поле | Тип | Ограничения |
|------|-----|-------------|
| id | INTEGER | PRIMARY KEY AUTOINCREMENT |
| test_id | INTEGER | NOT NULL REFERENCES ai_interactive_tests(id) ON DELETE CASCADE |
| question_text | TEXT | NOT NULL |
| question_type | TEXT | NOT NULL DEFAULT 'single_choice' |
| sort_order | INTEGER | NOT NULL DEFAULT 0 |
| created_at | TEXT | NOT NULL DEFAULT datetime('now') |
| updated_at | TEXT | NOT NULL DEFAULT datetime('now') |

### ai_test_options

Варианты ответов для вопросов интерактивных ИИ-тестов.

| Поле | Тип | Ограничения |
|------|-----|-------------|
| id | INTEGER | PRIMARY KEY AUTOINCREMENT |
| question_id | INTEGER | NOT NULL REFERENCES ai_test_questions(id) ON DELETE CASCADE |
| option_text | TEXT | NOT NULL |
| is_correct | INTEGER | NOT NULL DEFAULT 0 |
| sort_order | INTEGER | NOT NULL DEFAULT 0 |

### ai_test_submissions

Ответы учеников по опубликованным интерактивным ИИ-тестам.

| Поле | Тип | Ограничения |
|------|-----|-------------|
| id | INTEGER | PRIMARY KEY AUTOINCREMENT |
| test_id | INTEGER | NOT NULL REFERENCES ai_interactive_tests(id) ON DELETE CASCADE |
| student_name | TEXT | NOT NULL |
| score | INTEGER | NOT NULL DEFAULT 0 |
| total_questions | INTEGER | NOT NULL DEFAULT 0 |
| answers_json | TEXT | NOT NULL DEFAULT '[]' |
| submitted_at | TEXT | NOT NULL DEFAULT datetime('now') |

---

## Индексы

```sql
CREATE INDEX idx_classes_slug ON classes(slug);
CREATE INDEX idx_materials_class_id ON materials(class_id);
CREATE INDEX idx_materials_status ON materials(status);
CREATE INDEX idx_material_files_material_id ON material_files(material_id);
CREATE INDEX idx_ai_teaching_materials_status ON ai_teaching_materials(status);
CREATE INDEX idx_ai_teaching_materials_class_title ON ai_teaching_materials(class_title);
CREATE INDEX idx_ai_teaching_materials_test_template ON ai_teaching_materials(test_template);
CREATE INDEX idx_ai_teaching_materials_school_year ON ai_teaching_materials(school_year);
CREATE INDEX idx_ai_teaching_materials_source_material_id ON ai_teaching_materials(source_material_id);
CREATE INDEX idx_ai_material_files_material_id ON ai_material_files(material_id);
CREATE INDEX idx_ai_word_presentations_class_id ON ai_word_presentations(class_id);
CREATE INDEX idx_ai_word_presentations_material_id ON ai_word_presentations(material_id);
CREATE INDEX idx_ai_word_presentations_status ON ai_word_presentations(status);
CREATE INDEX idx_ai_knowledge_categories_slug ON ai_knowledge_categories(slug);
CREATE INDEX idx_ai_knowledge_sources_status ON ai_knowledge_sources(status);
CREATE INDEX idx_ai_knowledge_sources_source_type ON ai_knowledge_sources(source_type);
CREATE INDEX idx_ai_knowledge_sources_category_id ON ai_knowledge_sources(category_id);
CREATE INDEX idx_ai_material_knowledge_sources_material_id ON ai_material_knowledge_sources(material_id);
CREATE INDEX idx_ai_material_knowledge_sources_source_id ON ai_material_knowledge_sources(knowledge_source_id);
CREATE INDEX idx_ai_interactive_tests_material_id ON ai_interactive_tests(ai_material_id);
CREATE INDEX idx_ai_interactive_tests_class_title ON ai_interactive_tests(class_title);
CREATE INDEX idx_ai_interactive_tests_template_key ON ai_interactive_tests(template_key);
CREATE INDEX idx_ai_interactive_tests_status ON ai_interactive_tests(status);
CREATE INDEX idx_ai_test_questions_test_id ON ai_test_questions(test_id);
CREATE INDEX idx_ai_test_options_question_id ON ai_test_options(question_id);
CREATE INDEX idx_ai_test_submissions_test_id ON ai_test_submissions(test_id);
CREATE INDEX idx_practice_tasks_class_id ON practice_tasks(class_id);
CREATE INDEX idx_practice_questions_task_id ON practice_questions(task_id);
CREATE INDEX idx_submissions_task_id ON submissions(task_id);
CREATE INDEX idx_submissions_class_id ON submissions(class_id);
CREATE INDEX idx_student_questions_class_id ON student_questions(class_id);
CREATE INDEX idx_extra_lesson_requests_status ON extra_lesson_requests(status);
CREATE INDEX idx_calendar_events_class_id ON calendar_events(class_id);
CREATE INDEX idx_calendar_events_material_id ON calendar_events(material_id);
CREATE INDEX idx_calendar_events_starts_at ON calendar_events(starts_at);
CREATE INDEX idx_calendar_events_type ON calendar_events(event_type);
CREATE INDEX idx_calendar_events_published ON calendar_events(is_published);
```

---

## Сидовые данные

`database/schema.sql` создаёт стартового пользователя:

- email: `admin@site.com`
- пароль: `admin`

Также добавлены тестовые классы `5А`, `7Б`, `9В`, несколько материалов, тренировочное задание, отправленные ответы и вопрос ученика.

Для базы знаний ИИ-помощника добавлены стартовые категории `Vocabulary`, `Grammar`, `Speaking`, `Reading`, `Writing`. Тестовые текстовые источники больше не добавляются автоматически.
