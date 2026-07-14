ALTER TABLE calendar_events ADD COLUMN material_id INTEGER DEFAULT NULL REFERENCES materials(id) ON DELETE SET NULL;
ALTER TABLE calendar_events ADD COLUMN lesson_number INTEGER DEFAULT NULL;
ALTER TABLE calendar_events ADD COLUMN homework_content TEXT NOT NULL DEFAULT '';

CREATE INDEX IF NOT EXISTS idx_calendar_events_material_id ON calendar_events(material_id);
