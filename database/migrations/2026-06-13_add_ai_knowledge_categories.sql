CREATE TABLE IF NOT EXISTS ai_knowledge_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    description TEXT NOT NULL DEFAULT '',
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

ALTER TABLE ai_knowledge_sources ADD COLUMN category_id INTEGER DEFAULT NULL REFERENCES ai_knowledge_categories(id) ON DELETE SET NULL;

CREATE INDEX IF NOT EXISTS idx_ai_knowledge_categories_slug ON ai_knowledge_categories(slug);
CREATE INDEX IF NOT EXISTS idx_ai_knowledge_sources_category_id ON ai_knowledge_sources(category_id);

INSERT OR IGNORE INTO ai_knowledge_categories (title, slug, description, sort_order)
VALUES
    ('Vocabulary', 'vocabulary', 'Words, phrases and topic vocabulary.', 10),
    ('Grammar', 'grammar', 'Rules, examples and grammar drills.', 20),
    ('Speaking', 'speaking', 'Dialogues, questions and classroom speaking prompts.', 30),
    ('Reading', 'reading', 'Texts and reading comprehension tasks.', 40),
    ('Writing', 'writing', 'Writing plans, useful phrases and examples.', 50);
