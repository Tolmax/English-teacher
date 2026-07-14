<?php

require ROOT . 'app/models/ai-teaching-material.php';

$slug = trim((string)($segments[1] ?? ''));

if ($slug !== '') {
    require ROOT . 'app/controllers/ai-materials/show.php';
    return;
}

renderTemplate('pages/ai-materials/index.tpl', [
    'materials' => getPublishedAiTeachingMaterials(),
]);
