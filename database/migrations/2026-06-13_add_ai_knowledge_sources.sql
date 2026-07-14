CREATE TABLE IF NOT EXISTS ai_knowledge_sources (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
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

CREATE INDEX IF NOT EXISTS idx_ai_knowledge_sources_status ON ai_knowledge_sources(status);
CREATE INDEX IF NOT EXISTS idx_ai_knowledge_sources_source_type ON ai_knowledge_sources(source_type);
CREATE INDEX IF NOT EXISTS idx_ai_material_knowledge_sources_material_id ON ai_material_knowledge_sources(material_id);
CREATE INDEX IF NOT EXISTS idx_ai_material_knowledge_sources_source_id ON ai_material_knowledge_sources(knowledge_source_id);
