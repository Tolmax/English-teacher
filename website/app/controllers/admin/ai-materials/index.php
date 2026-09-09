<?php

require ROOT . 'app/models/class.php';
require ROOT . 'app/models/ai-teaching-material.php';
require ROOT . 'app/models/ai-interactive-test.php';
require ROOT . 'app/models/ai-material-file.php';
require ROOT . 'app/models/ai-knowledge-source.php';
require ROOT . 'app/models/ai-material-knowledge-source.php';

$action = $segments[2] ?? 'index';

if ($action === 'create') {
    require ROOT . 'app/controllers/admin/ai-materials/create.php';
    return;
}

if ($action === 'generate-test') {
    require ROOT . 'app/controllers/admin/ai-materials/generate-test.php';
    return;
}

if (is_numeric($action) && ($segments[3] ?? '') === 'edit') {
    require ROOT . 'app/controllers/admin/ai-materials/edit.php';
    return;
}

if (is_numeric($action) && ($segments[3] ?? '') === 'generate') {
    require ROOT . 'app/controllers/admin/ai-materials/generate.php';
    return;
}

if (is_numeric($action) && ($segments[3] ?? '') === 'publish') {
    require ROOT . 'app/controllers/admin/ai-materials/publish.php';
    return;
}

if (is_numeric($action) && ($segments[3] ?? '') === 'unpublish') {
    require ROOT . 'app/controllers/admin/ai-materials/unpublish.php';
    return;
}

if (is_numeric($action) && ($segments[3] ?? '') === 'archive') {
    require ROOT . 'app/controllers/admin/ai-materials/archive.php';
    return;
}

if (is_numeric($action) && ($segments[3] ?? '') === 'restore') {
    require ROOT . 'app/controllers/admin/ai-materials/restore.php';
    return;
}

if (is_numeric($action) && ($segments[3] ?? '') === 'duplicate') {
    require ROOT . 'app/controllers/admin/ai-materials/duplicate.php';
    return;
}

if (is_numeric($action) && ($segments[3] ?? '') === 'export-docx') {
    require ROOT . 'app/controllers/admin/ai-materials/export-docx.php';
    return;
}

if (is_numeric($action) && ($segments[3] ?? '') === 'test') {
    require ROOT . 'app/controllers/admin/ai-materials/test.php';
    return;
}

if ($action !== 'index') {
    abort404();
}

require ROOT . 'app/services/openai-teacher-assistant.php';

$filters = [
    'search' => trim((string)($_GET['search'] ?? '')),
    'class_title' => trim((string)($_GET['class_title'] ?? '')),
    'material_type' => trim((string)($_GET['material_type'] ?? '')),
    'status' => trim((string)($_GET['status'] ?? '')),
    'school_year' => trim((string)($_GET['school_year'] ?? '')),
    'library_view' => trim((string)($_GET['library_view'] ?? 'active')),
];

if (!in_array($filters['library_view'], ['active', 'archive', 'all'], true)) {
    $filters['library_view'] = 'active';
}

$materials = getAiTeachingMaterials($filters);
$recentTests = array_slice(getAiInteractiveTestsForAdmin(), 0, 8);
$testForm = $_SESSION['ai_test_form'] ?? [];
unset($_SESSION['ai_test_form']);

renderTemplate('pages/admin/ai-materials/index.tpl', [
    'aiGenerationMode' => getAiGenerationModeStatus(),
    'testTemplates' => getAiTestTemplates(),
    'defaultTestTemplateKey' => getDefaultAiTestTemplateKey(),
    'materials' => $materials,
    'recentTests' => $recentTests,
    'knowledgeSources' => getAiTestKnowledgeSourceOptions(),
    'testForm' => $testForm,
    'csrfToken' => presentationCsrfToken(),
    'classes' => getAllLearningClasses(true),
    'schoolYears' => getAiTeachingMaterialSchoolYears(),
    'filters' => $filters,
    'flash' => getFlash('admin'),
    'errorFlash' => getFlash('admin_error'),
]);
