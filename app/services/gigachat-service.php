<?php

function isGigaChatConfigured(): bool
{
    return defined('GIGACHAT_AUTH_KEY') && trim((string)GIGACHAT_AUTH_KEY) !== '';
}

function getGigaChatModelName(): string
{
    $model = defined('GIGACHAT_MODEL') ? trim((string)GIGACHAT_MODEL) : '';

    return $model !== '' ? $model : 'GigaChat';
}

function getGigaChatImageModelName(): string
{
    $model = defined('GIGACHAT_IMAGE_MODEL') ? trim((string)GIGACHAT_IMAGE_MODEL) : '';

    return $model !== '' ? $model : getGigaChatModelName();
}

function requestGigaChatChatCompletion(array $messages, ?string $model = null, float $temperature = 0.3, int $maxTokens = 1200): array
{
    if (!isGigaChatConfigured()) {
        throw new RuntimeException('GIGACHAT_AUTH_KEY не задан на сервере.');
    }

    $accessToken = getGigaChatAccessToken();
    $payload = [
        'model' => trim((string)($model ?? '')) !== '' ? trim((string)$model) : getGigaChatModelName(),
        'messages' => $messages,
        'temperature' => $temperature,
        'max_tokens' => $maxTokens,
        'stream' => false,
    ];
    $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);

    if ($encodedPayload === false) {
        throw new RuntimeException('Не удалось подготовить запрос к GigaChat.');
    }

    $http = requestGigaChatHttp(
        rtrim((string)GIGACHAT_API_BASE_URL, '/') . '/chat/completions',
        [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $accessToken,
        ],
        $encodedPayload,
        90,
        'GigaChat API',
        'gigachat.devices.sberbank.ru'
    );
    $response = json_decode($http['body'], true);

    if (!is_array($response)) {
        throw new RuntimeException('GigaChat API вернул ответ в неожиданном формате.');
    }

    if ((int)$http['status'] >= 400) {
        throw new RuntimeException(buildGigaChatUserErrorMessage((int)$http['status'], $response));
    }

    return $response;
}

function requestGigaChatText(string $systemPrompt, string $userPrompt, float $temperature = 0.4, int $maxTokens = 3500): array
{
    if (!isGigaChatConfigured()) {
        throw new RuntimeException('GIGACHAT_AUTH_KEY не задан на сервере.');
    }

    $accessToken = getGigaChatAccessToken();
    $payload = [
        'model' => getGigaChatModelName(),
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ],
        'temperature' => $temperature,
        'max_tokens' => $maxTokens,
        'stream' => false,
    ];
    $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);

    if ($encodedPayload === false) {
        throw new RuntimeException('Не удалось подготовить запрос к GigaChat.');
    }

    $http = requestGigaChatHttp(
        rtrim((string)GIGACHAT_API_BASE_URL, '/') . '/chat/completions',
        [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $accessToken,
        ],
        $encodedPayload,
        60,
        'GigaChat API',
        'gigachat.devices.sberbank.ru'
    );
    $rawResponse = $http['body'];
    $statusCode = $http['status'];
    $response = json_decode($rawResponse, true);

    if (!is_array($response)) {
        throw new RuntimeException('GigaChat API вернул ответ в неожиданном формате.');
    }

    if ($statusCode >= 400 && $scope !== 'GIGACHAT_API_PERS' && isGigaChatScopeFormatError($response)) {
        $http = requestGigaChatHttp(
            (string)GIGACHAT_AUTH_URL,
            [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
                'RqUID: ' . createGigaChatUuid(),
                'Authorization: Basic ' . trim((string)GIGACHAT_AUTH_KEY),
            ],
            http_build_query(['scope' => 'GIGACHAT_API_PERS']),
            30,
            'GigaChat OAuth',
            'ngw.devices.sberbank.ru'
        );
        $rawResponse = $http['body'];
        $statusCode = $http['status'];
        $response = json_decode($rawResponse, true);

        if (!is_array($response)) {
            throw new RuntimeException('GigaChat OAuth вернул ответ в неожиданном формате.');
        }
    }

    if ($statusCode >= 400) {
        throw new RuntimeException(buildGigaChatUserErrorMessage($statusCode, $response));
    }

    $content = trim((string)($response['choices'][0]['message']['content'] ?? ''));

    if ($content === '') {
        throw new RuntimeException('GigaChat API не вернул текст. Попробуйте повторить генерацию.');
    }

    return [
        'content' => $content,
        'model' => 'gigachat:' . (string)($response['model'] ?? getGigaChatModelName()),
        'response_id' => (string)($response['id'] ?? ''),
    ];
}

function getGigaChatAccessToken(): string
{
    $cacheKey = 'gigachat_access_token';
    $expiresKey = 'gigachat_access_token_expires_at';

    if (!empty($_SESSION[$cacheKey]) && !empty($_SESSION[$expiresKey]) && (int)$_SESSION[$expiresKey] > time() + 60) {
        return (string)$_SESSION[$cacheKey];
    }

    $scope = getGigaChatScope();
    $body = http_build_query(['scope' => $scope]);

    $http = requestGigaChatHttp(
        (string)GIGACHAT_AUTH_URL,
        [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
            'RqUID: ' . createGigaChatUuid(),
            'Authorization: Basic ' . trim((string)GIGACHAT_AUTH_KEY),
        ],
        $body,
        30,
        'GigaChat OAuth',
        'ngw.devices.sberbank.ru'
    );
    $rawResponse = $http['body'];
    $statusCode = $http['status'];
    $response = json_decode($rawResponse, true);

    if (!is_array($response)) {
        throw new RuntimeException('GigaChat OAuth вернул ответ в неожиданном формате.');
    }

    if ($statusCode >= 400) {
        throw new RuntimeException(buildGigaChatUserErrorMessage($statusCode, $response));
    }

    $token = trim((string)($response['access_token'] ?? ''));

    if ($token === '') {
        throw new RuntimeException('GigaChat OAuth не вернул access_token.');
    }

    $expiresAt = (int)($response['expires_at'] ?? 0);
    if ($expiresAt > 20000000000) {
        $expiresAt = (int)floor($expiresAt / 1000);
    }
    if ($expiresAt <= time()) {
        $expiresAt = time() + 25 * 60;
    }

    $_SESSION[$cacheKey] = $token;
    $_SESSION[$expiresKey] = $expiresAt;

    return $token;
}

function requestGigaChatHttp(string $url, array $headers, string $body, int $timeout, string $serviceName, string $host): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('PHP-расширение curl не включено. Оно нужно для подключения к GigaChat.');
    }

    $curl = curl_init($url);
    if ($curl === false) {
        throw new RuntimeException('Не удалось подготовить HTTP-запрос к ' . $serviceName . '.');
    }

    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => min(15, $timeout),
        CURLOPT_SSL_VERIFYPEER => shouldVerifyGigaChatSsl(),
        CURLOPT_SSL_VERIFYHOST => shouldVerifyGigaChatSsl() ? 2 : 0,
    ]);

    $rawResponse = curl_exec($curl);
    $statusCode = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $curlError = curl_error($curl);

    if ($rawResponse === false) {
        throw new RuntimeException(buildGigaChatConnectionError($serviceName, $host, $curlError));
    }

    return [
        'status' => $statusCode,
        'body' => (string)$rawResponse,
    ];
}

function requestGigaChatBinary(string $url, array $headers, int $timeout, string $serviceName, string $host): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('PHP-расширение curl не включено. Оно нужно для подключения к GigaChat.');
    }

    $curl = curl_init($url);
    if ($curl === false) {
        throw new RuntimeException('Не удалось подготовить HTTP-запрос к ' . $serviceName . '.');
    }

    curl_setopt_array($curl, [
        CURLOPT_HTTPGET => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => min(15, $timeout),
        CURLOPT_SSL_VERIFYPEER => shouldVerifyGigaChatSsl(),
        CURLOPT_SSL_VERIFYHOST => shouldVerifyGigaChatSsl() ? 2 : 0,
    ]);

    $rawResponse = curl_exec($curl);
    $statusCode = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $contentType = (string)curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
    $curlError = curl_error($curl);

    if ($rawResponse === false) {
        throw new RuntimeException(buildGigaChatConnectionError($serviceName, $host, $curlError));
    }

    return [
        'status' => $statusCode,
        'body' => (string)$rawResponse,
        'content_type' => $contentType,
    ];
}

function shouldVerifyGigaChatSsl(): bool
{
    $value = strtolower(trim((string)(defined('GIGACHAT_VERIFY_SSL') ? GIGACHAT_VERIFY_SSL : '1')));

    return !in_array($value, ['0', 'false', 'no', 'off'], true);
}

function getGigaChatScope(): string
{
    $scope = defined('GIGACHAT_SCOPE') ? trim((string)GIGACHAT_SCOPE) : '';
    $scope = preg_replace('/[^\w:.-]/u', '', $scope) ?? '';

    return $scope !== '' ? $scope : 'GIGACHAT_API_PERS';
}

function isGigaChatScopeFormatError(array $response): bool
{
    $message = strtolower(trim((string)($response['message'] ?? $response['error']['message'] ?? $response['error_description'] ?? '')));

    return str_contains($message, 'scope') && str_contains($message, 'format');
}

function buildGigaChatConnectionError(string $serviceName, string $host, string $transportMessage = ''): string
{
    $error = error_get_last();
    $message = trim($transportMessage !== '' ? $transportMessage : (string)($error['message'] ?? ''));

    if (stripos($message, 'certificate') !== false || stripos($message, 'SSL') !== false || stripos($message, 'operation failed') !== false) {
        return $serviceName . ' недоступен из-за ошибки SSL-сертификата. Установите доверенные сертификаты НУЦ Минцифры/Сбера или временно задайте GIGACHAT_VERIFY_SSL=0 для локальной проверки.';
    }

    return 'Не удалось подключиться к ' . $serviceName . '. Проверьте интернет, DNS/SSL и доступ сервера к ' . $host . '.';
}

function parseHttpStatusCode(array $headers): int
{
    $statusLine = $headers[0] ?? '';

    if (preg_match('/\s(\d{3})\s/', $statusLine, $matches) !== 1) {
        return 0;
    }

    return (int)$matches[1];
}

function buildGigaChatUserErrorMessage(int $statusCode, array $response): string
{
    $message = trim((string)($response['message'] ?? $response['error']['message'] ?? $response['error_description'] ?? ''));

    if ($statusCode === 401 || $statusCode === 403) {
        return 'GigaChat отклонил авторизацию. Проверьте GIGACHAT_AUTH_KEY и доступы в кабинете Сбера.';
    }

    if ($statusCode === 404) {
        return 'GigaChat не нашёл выбранную модель. Проверьте GIGACHAT_MODEL.';
    }

    if ($statusCode === 429) {
        return 'GigaChat сообщил о лимите запросов. Попробуйте позже или проверьте тариф/квоты.';
    }

    if ($statusCode >= 500) {
        return 'На стороне GigaChat временная ошибка. Попробуйте повторить генерацию позже.';
    }

    return $message !== ''
        ? 'GigaChat вернул ошибку: ' . $message
        : 'GigaChat вернул ошибку. Проверьте настройки GIGACHAT_AUTH_KEY и GIGACHAT_MODEL.';
}

function createGigaChatUuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
