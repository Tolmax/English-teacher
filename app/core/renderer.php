<?php

function renderTemplate(string $template, array $data = []): void
{
    $templatePath = ROOT . 'templates/' . $template;

    if (!file_exists($templatePath)) {
        throw new \RuntimeException('Template not found: ' . $template);
    }

    extract($data, EXTR_SKIP);
    include $templatePath;
}
