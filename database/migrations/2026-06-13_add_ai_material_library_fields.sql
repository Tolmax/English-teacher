ALTER TABLE ai_teaching_materials ADD COLUMN school_year TEXT NOT NULL DEFAULT '';
ALTER TABLE ai_teaching_materials ADD COLUMN period TEXT NOT NULL DEFAULT '';
ALTER TABLE ai_teaching_materials ADD COLUMN tags TEXT NOT NULL DEFAULT '';
ALTER TABLE ai_teaching_materials ADD COLUMN archived_at TEXT DEFAULT NULL;
ALTER TABLE ai_teaching_materials ADD COLUMN source_material_id INTEGER DEFAULT NULL REFERENCES ai_teaching_materials(id) ON DELETE SET NULL;

CREATE INDEX IF NOT EXISTS idx_ai_teaching_materials_school_year ON ai_teaching_materials(school_year);
CREATE INDEX IF NOT EXISTS idx_ai_teaching_materials_source_material_id ON ai_teaching_materials(source_material_id);
