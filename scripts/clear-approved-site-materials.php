<?php
// One-time, explicitly approved cleanup; no backup is created.
$root = '/home/c/cr77641/My_Projects/public_html/english/';
$db = new PDO('sqlite:' . $root . 'database/database.sqlite', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec('PRAGMA foreign_keys=ON');
$db->exec('PRAGMA busy_timeout=10000');
$expected = ['ai_test_submissions'=>6, 'submissions'=>4, 'ai_test_options'=>60, 'ai_test_questions'=>30, 'ai_interactive_tests'=>3, 'practice_questions'=>2, 'practice_tasks'=>1, 'ai_material_knowledge_sources'=>0, 'ai_material_files'=>0, 'ai_word_presentations'=>4, 'material_files'=>4, 'ai_teaching_materials'=>5, 'materials'=>4];
$preserved = [];
foreach (['users','classes','calendar_events','extra_lesson_requests','student_questions','ai_knowledge_sources','ai_knowledge_categories'] as $table) {
    $preserved[$table] = (int)$db->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn();
}
// Inspection found no attachment files. Fail closed if files appeared since then.
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . 'uploads/materials', FilesystemIterator::SKIP_DOTS));
foreach ($files as $file) {
    if ($file->isFile() || $file->isLink()) throw new RuntimeException('Attachment files appeared; inspect before cleanup.');
}
$db->exec('BEGIN IMMEDIATE');
try {
    foreach ($expected as $table=>$count) {
        if ((int)$db->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn() !== $count) throw new RuntimeException('Data changed: ' . $table);
    }
    foreach ($expected as $table=>$count) $db->exec('DELETE FROM ' . $table);
    foreach ($expected as $table=>$count) {
        if ((int)$db->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn() !== 0) throw new RuntimeException('Cleanup failed: ' . $table);
    }
    foreach ($preserved as $table=>$count) {
        if ((int)$db->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn() !== $count) throw new RuntimeException('Unexpected related deletion: ' . $table);
    }
    if ($db->query('PRAGMA foreign_key_check')->fetch()) throw new RuntimeException('Foreign key check failed');
    $db->exec('COMMIT');
} catch (Throwable $error) {
    $db->exec('ROLLBACK');
    throw $error;
}
$db->exec('PRAGMA wal_checkpoint(TRUNCATE)');
$db->exec('VACUUM');
$db->exec('PRAGMA wal_checkpoint(TRUNCATE)');
echo json_encode(['deleted'=>$expected, 'preserved'=>$preserved, 'integrity'=>$db->query('PRAGMA integrity_check')->fetchColumn(), 'backup_created'=>false], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
