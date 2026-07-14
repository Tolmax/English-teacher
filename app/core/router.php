<?php

function parseRoute(): array
{
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $uri        = $_SERVER['REQUEST_URI'];
    $path       = parse_url($uri, PHP_URL_PATH);
    $basePath   = dirname($scriptName);

    $relativePath = '/' . ltrim(
        preg_replace('/' . preg_quote($basePath, '/') . '/', '', $path, 1),
        '/'
    );

    $segments = explode('/', trim($relativePath, '/'));
    $segments = array_values(array_filter($segments, static fn($s) => $s !== ''));
    $segments = array_map('rawurldecode', $segments);

    return $segments;
}
