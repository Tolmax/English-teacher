<?php

require_once ROOT . 'app/services/presentation-card-api.php';
require_once ROOT . 'app/services/yandex-presentation-api.php';

function presentationCsrfToken(): string
{
    return $_SESSION['presentation_csrf'] ??= bin2hex(random_bytes(32));
}

function requirePresentationCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(presentationCsrfToken(), $token)) {
        http_response_code(403);
        exit('Обновите страницу и повторите действие.');
    }
}

function presentationEnvValue(string $key, string $default = ''): string
{
    return defined($key) ? (string)constant($key) : (string)(getenv($key) ?: $default);
}

function isYandexConfigured(): bool
{
    return trim(presentationEnvValue('YANDEX_API_KEY')) !== ''
        && trim(presentationEnvValue('YANDEX_FOLDER_ID')) !== '';
}

function getYandexModelName(): string
{
    return 'YandexGPT 5.1 Pro';
}

function getYandexImageModelName(): string
{
    return 'Alice AI ART 3.0';
}

function requestYandexText(string $systemPrompt, string $userPrompt, float $temperature = 0.3, int $maxTokens = 7000): array
{
    if (!isYandexConfigured()) {
        throw new RuntimeException('Задайте YANDEX_API_KEY и YANDEX_FOLDER_ID на сервере.');
    }
    $response = yandexRequest('text', presentationEnvValue('YANDEX_API_KEY'), [
        'modelUri' => 'gpt://' . yandexFolder() . '/yandexgpt-5.1',
        'completionOptions' => ['stream' => false, 'temperature' => $temperature, 'maxTokens' => (string)$maxTokens],
        'messages' => [
            ['role' => 'system', 'text' => $systemPrompt],
            ['role' => 'user', 'text' => $userPrompt],
        ],
    ]);
    $result = $response['result'] ?? $response;
    $alternative = $result['alternatives'][0] ?? [];
    if (($alternative['status'] ?? '') !== 'ALTERNATIVE_STATUS_FINAL') {
        throw new RuntimeException('Яндекс не завершил генерацию текста. Возможен лимит длины или отказ модерации.');
    }
    $content = trim((string)($alternative['message']['text'] ?? ''));
    if ($content === '') {
        throw new RuntimeException('Яндекс вернул пустой ответ.');
    }
    return ['content' => $content, 'model' => 'yandex:' . getYandexModelName(), 'response_id' => ''];
}
