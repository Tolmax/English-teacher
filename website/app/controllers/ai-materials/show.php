<?php

$material = getPublishedAiTeachingMaterialBySlug($slug);
requireFound($material);

renderTemplate('pages/ai-materials/show.tpl', [
    'material' => $material,
]);
