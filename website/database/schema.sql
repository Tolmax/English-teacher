PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    login TEXT UNIQUE,
    password_hash TEXT NOT NULL,
    name TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'teacher',
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS classes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    description TEXT NOT NULL DEFAULT '',
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS materials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    class_id INTEGER NOT NULL REFERENCES classes(id) ON DELETE CASCADE,
    type TEXT NOT NULL DEFAULT 'material',
    title TEXT NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    content TEXT NOT NULL DEFAULT '',
    status TEXT NOT NULL DEFAULT 'new',
    deadline_at TEXT DEFAULT NULL,
    is_published INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS material_files (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    material_id INTEGER NOT NULL REFERENCES materials(id) ON DELETE CASCADE,
    original_name TEXT NOT NULL,
    stored_name TEXT NOT NULL,
    mime_type TEXT NOT NULL,
    file_size INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS ai_teaching_materials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    subject TEXT NOT NULL,
    class_title TEXT NOT NULL,
    topic TEXT NOT NULL,
    material_type TEXT NOT NULL,
    test_template TEXT NOT NULL DEFAULT 'quick_check',
    difficulty TEXT NOT NULL DEFAULT '',
    estimated_duration TEXT NOT NULL DEFAULT '',
    language TEXT NOT NULL DEFAULT 'ru',
    instructions TEXT NOT NULL DEFAULT '',
    source_notes TEXT NOT NULL DEFAULT '',
    generated_content TEXT NOT NULL DEFAULT '',
    edited_content TEXT NOT NULL DEFAULT '',
    status TEXT NOT NULL DEFAULT 'draft',
    generation_error TEXT NOT NULL DEFAULT '',
    openai_response_id TEXT NOT NULL DEFAULT '',
    model_used TEXT NOT NULL DEFAULT '',
    prompt_version TEXT NOT NULL DEFAULT 'v1',
    school_year TEXT NOT NULL DEFAULT '',
    period TEXT NOT NULL DEFAULT '',
    tags TEXT NOT NULL DEFAULT '',
    archived_at TEXT DEFAULT NULL,
    source_material_id INTEGER DEFAULT NULL REFERENCES ai_teaching_materials(id) ON DELETE SET NULL,
    published_at TEXT DEFAULT NULL,
    created_by INTEGER DEFAULT NULL REFERENCES users(id) ON DELETE SET NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS ai_material_files (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    material_id INTEGER NOT NULL REFERENCES ai_teaching_materials(id) ON DELETE CASCADE,
    file_type TEXT NOT NULL,
    original_name TEXT NOT NULL,
    stored_name TEXT NOT NULL,
    storage_path TEXT NOT NULL,
    mime_type TEXT NOT NULL,
    file_size INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS ai_word_presentations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    material_id INTEGER DEFAULT NULL REFERENCES materials(id) ON DELETE SET NULL,
    class_id INTEGER NOT NULL REFERENCES classes(id) ON DELETE CASCADE,
    title TEXT NOT NULL,
    source_words TEXT NOT NULL DEFAULT '[]',
    cards_json TEXT NOT NULL DEFAULT '[]',
    pptx_file_id INTEGER DEFAULT NULL REFERENCES material_files(id) ON DELETE SET NULL,
    status TEXT NOT NULL DEFAULT 'draft',
    model TEXT NOT NULL DEFAULT '',
    response_id TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS ai_knowledge_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    description TEXT NOT NULL DEFAULT '',
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS ai_knowledge_sources (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER DEFAULT NULL REFERENCES ai_knowledge_categories(id) ON DELETE SET NULL,
    title TEXT NOT NULL,
    source_type TEXT NOT NULL DEFAULT 'text',
    original_name TEXT NOT NULL DEFAULT '',
    stored_name TEXT NOT NULL DEFAULT '',
    storage_path TEXT NOT NULL DEFAULT '',
    mime_type TEXT NOT NULL DEFAULT '',
    file_size INTEGER NOT NULL DEFAULT 0,
    extracted_text TEXT NOT NULL DEFAULT '',
    description TEXT NOT NULL DEFAULT '',
    tags TEXT NOT NULL DEFAULT '',
    status TEXT NOT NULL DEFAULT 'active',
    openai_file_id TEXT NOT NULL DEFAULT '',
    vector_store_id TEXT NOT NULL DEFAULT '',
    created_by INTEGER DEFAULT NULL REFERENCES users(id) ON DELETE SET NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now')),
    archived_at TEXT DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS ai_material_knowledge_sources (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    material_id INTEGER NOT NULL REFERENCES ai_teaching_materials(id) ON DELETE CASCADE,
    knowledge_source_id INTEGER NOT NULL REFERENCES ai_knowledge_sources(id) ON DELETE CASCADE,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    UNIQUE(material_id, knowledge_source_id)
);

CREATE TABLE IF NOT EXISTS ai_interactive_tests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ai_material_id INTEGER NOT NULL REFERENCES ai_teaching_materials(id) ON DELETE CASCADE,
    class_title TEXT NOT NULL DEFAULT '',
    template_key TEXT NOT NULL DEFAULT 'quick_check',
    title TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    description TEXT NOT NULL DEFAULT '',
    status TEXT NOT NULL DEFAULT 'draft',
    published_at TEXT DEFAULT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS ai_test_questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    test_id INTEGER NOT NULL REFERENCES ai_interactive_tests(id) ON DELETE CASCADE,
    question_text TEXT NOT NULL,
    question_type TEXT NOT NULL DEFAULT 'single_choice',
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS ai_test_options (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    question_id INTEGER NOT NULL REFERENCES ai_test_questions(id) ON DELETE CASCADE,
    option_text TEXT NOT NULL,
    is_correct INTEGER NOT NULL DEFAULT 0,
    sort_order INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS ai_test_submissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    test_id INTEGER NOT NULL REFERENCES ai_interactive_tests(id) ON DELETE CASCADE,
    student_name TEXT NOT NULL,
    score INTEGER NOT NULL DEFAULT 0,
    total_questions INTEGER NOT NULL DEFAULT 0,
    answers_json TEXT NOT NULL DEFAULT '[]',
    submitted_at TEXT NOT NULL DEFAULT (datetime('now')),
    reviewed_at TEXT DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS practice_tasks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    class_id INTEGER NOT NULL REFERENCES classes(id) ON DELETE CASCADE,
    title TEXT NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    is_published INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS practice_questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    task_id INTEGER NOT NULL REFERENCES practice_tasks(id) ON DELETE CASCADE,
    question TEXT NOT NULL,
    options_json TEXT NOT NULL,
    correct_option INTEGER NOT NULL DEFAULT 0,
    explanation TEXT NOT NULL DEFAULT '',
    sort_order INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS submissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    task_id INTEGER NOT NULL REFERENCES practice_tasks(id) ON DELETE CASCADE,
    class_id INTEGER NOT NULL REFERENCES classes(id) ON DELETE CASCADE,
    student_name TEXT NOT NULL,
    student_contact TEXT NOT NULL DEFAULT '',
    score INTEGER NOT NULL DEFAULT 0,
    answers_json TEXT NOT NULL DEFAULT '[]',
    submitted_at TEXT NOT NULL DEFAULT (datetime('now')),
    reviewed_at TEXT DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS student_questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    class_id INTEGER NOT NULL REFERENCES classes(id) ON DELETE CASCADE,
    student_name TEXT NOT NULL,
    question TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'new',
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    answered_at TEXT DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS extra_lesson_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_name TEXT NOT NULL,
    student_contact TEXT NOT NULL,
    class_title TEXT NOT NULL DEFAULT '',
    lesson_format TEXT NOT NULL DEFAULT '',
    topic TEXT NOT NULL,
    message TEXT NOT NULL DEFAULT '',
    status TEXT NOT NULL DEFAULT 'new',
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    processed_at TEXT DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS calendar_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    class_id INTEGER DEFAULT NULL REFERENCES classes(id) ON DELETE SET NULL,
    material_id INTEGER DEFAULT NULL REFERENCES materials(id) ON DELETE SET NULL,
    title TEXT NOT NULL,
    event_type TEXT NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    starts_at TEXT NOT NULL,
    ends_at TEXT DEFAULT NULL,
    lesson_number INTEGER DEFAULT NULL,
    homework_content TEXT NOT NULL DEFAULT '',
    recurrence_group TEXT NOT NULL DEFAULT '',
    recurrence_rule TEXT NOT NULL DEFAULT 'none',
    recurrence_source_id INTEGER DEFAULT NULL,
    is_published INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_classes_slug ON classes(slug);
CREATE INDEX IF NOT EXISTS idx_materials_class_id ON materials(class_id);
CREATE INDEX IF NOT EXISTS idx_materials_status ON materials(status);
CREATE INDEX IF NOT EXISTS idx_material_files_material_id ON material_files(material_id);
CREATE INDEX IF NOT EXISTS idx_ai_teaching_materials_status ON ai_teaching_materials(status);
CREATE INDEX IF NOT EXISTS idx_ai_teaching_materials_class_title ON ai_teaching_materials(class_title);
CREATE INDEX IF NOT EXISTS idx_ai_teaching_materials_test_template ON ai_teaching_materials(test_template);
CREATE INDEX IF NOT EXISTS idx_ai_teaching_materials_school_year ON ai_teaching_materials(school_year);
CREATE INDEX IF NOT EXISTS idx_ai_teaching_materials_source_material_id ON ai_teaching_materials(source_material_id);
CREATE INDEX IF NOT EXISTS idx_ai_material_files_material_id ON ai_material_files(material_id);
CREATE INDEX IF NOT EXISTS idx_ai_word_presentations_class_id ON ai_word_presentations(class_id);
CREATE INDEX IF NOT EXISTS idx_ai_word_presentations_material_id ON ai_word_presentations(material_id);
CREATE INDEX IF NOT EXISTS idx_ai_word_presentations_status ON ai_word_presentations(status);
CREATE INDEX IF NOT EXISTS idx_ai_knowledge_categories_slug ON ai_knowledge_categories(slug);
CREATE INDEX IF NOT EXISTS idx_ai_knowledge_sources_status ON ai_knowledge_sources(status);
CREATE INDEX IF NOT EXISTS idx_ai_knowledge_sources_source_type ON ai_knowledge_sources(source_type);
CREATE INDEX IF NOT EXISTS idx_ai_knowledge_sources_category_id ON ai_knowledge_sources(category_id);
CREATE INDEX IF NOT EXISTS idx_ai_material_knowledge_sources_material_id ON ai_material_knowledge_sources(material_id);
CREATE INDEX IF NOT EXISTS idx_ai_material_knowledge_sources_source_id ON ai_material_knowledge_sources(knowledge_source_id);
CREATE INDEX IF NOT EXISTS idx_ai_interactive_tests_material_id ON ai_interactive_tests(ai_material_id);
CREATE INDEX IF NOT EXISTS idx_ai_interactive_tests_class_title ON ai_interactive_tests(class_title);
CREATE INDEX IF NOT EXISTS idx_ai_interactive_tests_template_key ON ai_interactive_tests(template_key);
CREATE INDEX IF NOT EXISTS idx_ai_interactive_tests_status ON ai_interactive_tests(status);
CREATE INDEX IF NOT EXISTS idx_ai_test_questions_test_id ON ai_test_questions(test_id);
CREATE INDEX IF NOT EXISTS idx_ai_test_options_question_id ON ai_test_options(question_id);
CREATE INDEX IF NOT EXISTS idx_ai_test_submissions_test_id ON ai_test_submissions(test_id);
CREATE INDEX IF NOT EXISTS idx_practice_tasks_class_id ON practice_tasks(class_id);
CREATE INDEX IF NOT EXISTS idx_practice_questions_task_id ON practice_questions(task_id);
CREATE INDEX IF NOT EXISTS idx_submissions_task_id ON submissions(task_id);
CREATE INDEX IF NOT EXISTS idx_submissions_class_id ON submissions(class_id);
CREATE INDEX IF NOT EXISTS idx_student_questions_class_id ON student_questions(class_id);
CREATE INDEX IF NOT EXISTS idx_extra_lesson_requests_status ON extra_lesson_requests(status);
CREATE INDEX IF NOT EXISTS idx_calendar_events_class_id ON calendar_events(class_id);
CREATE INDEX IF NOT EXISTS idx_calendar_events_material_id ON calendar_events(material_id);
CREATE INDEX IF NOT EXISTS idx_calendar_events_starts_at ON calendar_events(starts_at);
CREATE INDEX IF NOT EXISTS idx_calendar_events_type ON calendar_events(event_type);
CREATE INDEX IF NOT EXISTS idx_calendar_events_published ON calendar_events(is_published);
CREATE INDEX IF NOT EXISTS idx_calendar_events_recurrence_group ON calendar_events(recurrence_group);

INSERT OR IGNORE INTO users (email, password_hash, name, role)
VALUES ('admin@site.com', '$2y$12$nYSCc/rWPqoA.SULF6ix9.02VlP1by7rxAcfUm8RTE9rE7zJhRHlm', 'Teacher', 'teacher');

INSERT OR IGNORE INTO ai_knowledge_categories (title, slug, description, sort_order)
VALUES
    ('Vocabulary', 'vocabulary', 'Words, phrases and topic vocabulary.', 10),
    ('Grammar', 'grammar', 'Rules, examples and grammar drills.', 20),
    ('Speaking', 'speaking', 'Dialogues, questions and classroom speaking prompts.', 30),
    ('Reading', 'reading', 'Texts and reading comprehension tasks.', 40),
    ('Writing', 'writing', 'Writing plans, useful phrases and examples.', 50);

INSERT OR IGNORE INTO classes (id, title, slug, description, is_active)
VALUES
    (1, '1', '1', 'Материалы и задания для 1 класса.', 1),
    (2, '5', '5', 'Материалы и задания для 5 класса.', 1),
    (3, '6', '6', 'Материалы и задания для 6 класса.', 1),
    (4, '7', '7', 'Past Simple, irregular verbs and mini-tests.', 1),
    (5, '8', '8', 'Материалы и задания для 8 класса.', 1),
    (6, '10', '10', 'Revision and test preparation.', 1);
