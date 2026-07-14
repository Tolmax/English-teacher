<?php

$segments = parseRoute();
$module   = $segments[0] ?? '/';

$routes = [
    '/'       => ROOT . 'app/controllers/home.php',
    'class'   => ROOT . 'app/controllers/class/show.php',
    'extra-lessons' => ROOT . 'app/controllers/extra-lessons/index.php',
    'ai-materials' => ROOT . 'app/controllers/ai-materials/index.php',
    'ai-tests' => ROOT . 'app/controllers/ai-tests/index.php',
    'presentation' => ROOT . 'app/controllers/presentation/show.php',
    'admin'   => ROOT . 'app/controllers/admin/_router.php',
    '404'     => ROOT . 'app/controllers/not-found.php',
];

if (isset($routes[$module])) {
    require $routes[$module];
} else {
    http_response_code(404);
    require $routes['404'];
    exit;
}
