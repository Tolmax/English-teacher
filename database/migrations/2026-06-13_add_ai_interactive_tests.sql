CREATE TABLE IF NOT EXISTS ai_interactive_tests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ai_material_id INTEGER NOT NULL REFERENCES ai_teaching_materials(id) ON DELETE CASCADE,
    class_title TEXT NOT NULL DEFAULT '',
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
    submitted_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_ai_interactive_tests_material_id ON ai_interactive_tests(ai_material_id);
CREATE INDEX IF NOT EXISTS idx_ai_interactive_tests_class_title ON ai_interactive_tests(class_title);
CREATE INDEX IF NOT EXISTS idx_ai_interactive_tests_status ON ai_interactive_tests(status);
CREATE INDEX IF NOT EXISTS idx_ai_test_questions_test_id ON ai_test_questions(test_id);
CREATE INDEX IF NOT EXISTS idx_ai_test_options_question_id ON ai_test_options(question_id);
CREATE INDEX IF NOT EXISTS idx_ai_test_submissions_test_id ON ai_test_submissions(test_id);
