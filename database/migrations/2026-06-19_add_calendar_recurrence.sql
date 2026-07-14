ALTER TABLE calendar_events ADD COLUMN recurrence_group TEXT NOT NULL DEFAULT '';
ALTER TABLE calendar_events ADD COLUMN recurrence_rule TEXT NOT NULL DEFAULT 'none';
ALTER TABLE calendar_events ADD COLUMN recurrence_source_id INTEGER DEFAULT NULL;

CREATE INDEX IF NOT EXISTS idx_calendar_events_recurrence_group ON calendar_events(recurrence_group);
