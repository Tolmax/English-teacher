ALTER TABLE ai_teaching_materials
ADD COLUMN test_template TEXT NOT NULL DEFAULT 'quick_check';

ALTER TABLE ai_interactive_tests
ADD COLUMN template_key TEXT NOT NULL DEFAULT 'quick_check';

CREATE INDEX IF NOT EXISTS idx_ai_teaching_materials_test_template ON ai_teaching_materials(test_template);
CREATE INDEX IF NOT EXISTS idx_ai_interactive_tests_template_key ON ai_interactive_tests(template_key);
