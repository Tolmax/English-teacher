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

CREATE INDEX IF NOT EXISTS idx_ai_word_presentations_class_id ON ai_word_presentations(class_id);
CREATE INDEX IF NOT EXISTS idx_ai_word_presentations_material_id ON ai_word_presentations(material_id);
CREATE INDEX IF NOT EXISTS idx_ai_word_presentations_status ON ai_word_presentations(status);
