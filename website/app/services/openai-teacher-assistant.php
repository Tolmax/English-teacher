<?php

require_once ROOT . 'app/services/yandex-service.php';

function generateAiTeachingMaterial(array $material, array $knowledgeContext = [], ?string $provider = null): array
{
    $provider = getAiTextProvider($provider);

    if ($provider === 'yandex') {
        if (!isYandexConfigured()) {
            throw new RuntimeException('Яндекс AI не настроен. Укажите ключ и ID папки на сервере.');
        }

        return requestYandexTeachingMaterial($material, $knowledgeContext);
    }

    if (trim((string)OPENAI_API_KEY) === '') {
        return generateMockAiTeachingMaterial($material, $knowledgeContext);
    }

    return requestOpenAiTeachingMaterial($material, $knowledgeContext);
}

function getOpenAiModelName(): string
{
    $model = trim((string)OPENAI_MODEL);

    return $model !== '' ? $model : 'gpt-4.1-mini';
}

function getOpenAiImageModelName(): string
{
    $model = defined('OPENAI_IMAGE_MODEL') ? trim((string)OPENAI_IMAGE_MODEL) : '';

    return $model !== '' ? $model : 'gpt-image-2';
}

function getAiTextProvider(?string $provider = null): string
{
    $provider = strtolower(trim((string)($provider ?? (defined('AI_TEXT_PROVIDER') ? AI_TEXT_PROVIDER : 'openai'))));

    return $provider === 'yandex' ? 'yandex' : 'openai';
}

function getAiImageProvider(?string $provider = null): string
{
    $provider = strtolower(trim((string)($provider ?? (defined('AI_IMAGE_PROVIDER') ? AI_IMAGE_PROVIDER : 'openai'))));

    if (in_array($provider, ['openai', 'yandex', 'mock'], true)) {
        return $provider;
    }

    return 'openai';
}

function getAiTextProviderOptions(): array
{
    return [
        'openai' => [
            'label' => 'OpenAI',
            'model' => getOpenAiModelName(),
            'configured' => trim((string)OPENAI_API_KEY) !== '',
        ],
        'yandex' => [
            'label' => 'Яндекс AI',
            'model' => getYandexModelName(),
            'configured' => isYandexConfigured(),
        ],
    ];
}

function getAiImageProviderOptions(): array
{
    return [
        'openai' => [
            'label' => 'OpenAI',
            'model' => getOpenAiImageModelName(),
            'configured' => trim((string)OPENAI_API_KEY) !== '',
        ],
        'yandex' => [
            'label' => 'Яндекс AI',
            'model' => function_exists('getYandexImageModelName') ? getYandexImageModelName() : getYandexModelName(),
            'configured' => isYandexConfigured(),
        ],
        'mock' => [
            'label' => 'Mock',
            'model' => 'local-gd',
            'configured' => true,
        ],
    ];
}

function hasConfiguredAiTextProvider(?string $provider = null): bool
{
    $provider = getAiTextProvider($provider);

    return $provider === 'yandex'
        ? isYandexConfigured()
        : trim((string)OPENAI_API_KEY) !== '';
}

function hasConfiguredAiImageProvider(?string $provider = null): bool
{
    $provider = getAiImageProvider($provider);

    if ($provider === 'mock') {
        return true;
    }

    return $provider === 'yandex'
        ? isYandexConfigured()
        : trim((string)OPENAI_API_KEY) !== '';
}

function getAiGenerationModeStatus(): array
{
    $provider = getAiTextProvider();
    $options = getAiTextProviderOptions();
    $current = $options[$provider];

    if (!$current['configured']) {
        return [
            'mode' => 'mock',
            'badge' => 'mock',
            'model' => $current['model'],
            'message' => 'Выбран ' . $current['label'] . ', но ключ не задан. Будет создан mock-материал без внешнего API-запроса.',
            'hint' => 'Настройте ключ на сервере или выберите провайдера, у которого ключ уже задан.',
            'alert_class' => 'alert alert--warning',
            'providers' => $options,
            'selected_provider' => $provider,
        ];
    }

    return [
        'mode' => $provider,
        'badge' => $current['label'] . ' API',
        'model' => $current['model'],
        'message' => 'Включён режим ' . $current['label'] . ': новые материалы можно генерировать через выбранного провайдера.',
        'hint' => 'API-ключ задан на сервере и не показывается в браузере.',
        'alert_class' => 'alert alert--success',
        'providers' => $options,
        'selected_provider' => $provider,
    ];
}

function generateMockAiTeachingMaterial(array $material, array $knowledgeContext = []): array
{
    $typeLabel = aiTeachingMaterialTypeLabel($material['material_type']);
    $duration = trim((string)($material['estimated_duration'] ?? '')) !== ''
        ? $material['estimated_duration']
        : '15 минут';
    $content = implode("\n\n", [
        '# ' . $typeLabel . ': ' . $material['topic'],
        'Класс: ' . $material['class_title'] . '. Предмет: ' . $material['subject'] . '.',
        'Время выполнения: ' . $duration . '.',
        'Цель: повторить тему "' . $material['topic'] . '" и закрепить её в короткой практике.',
        "Задание 1. Прочитайте правило и подчеркните ключевые слова.\n- Запишите 3 примера по теме.\n- Объясните один пример своими словами.",
        "Задание 2. Выполните мини-практику.\n1. Составьте 5 предложений.\n2. Найдите и исправьте 2 ошибки.\n3. Придумайте один вопрос однокласснику.",
        "Рефлексия.\n- Что получилось легко?\n- Какой момент нужно повторить на следующем уроке?",
        'Это mock-материал для локальной проверки без OPENAI_API_KEY/YANDEX_API_KEY.',
    ]);

    if (trim((string)($knowledgeContext['text'] ?? '')) !== '') {
        $sourceTitles = [];

        foreach (($knowledgeContext['sources'] ?? []) as $source) {
            $sourceTitles[] = trim((string)($source['title'] ?? ''));
        }

        $sourceTitles = array_values(array_filter($sourceTitles));
        $sourceSummary = $sourceTitles === [] ? 'выбранные источники' : implode(', ', $sourceTitles);
        $content .= "\n\nИсточники базы знаний учтены: " . $sourceSummary . '.';
    }

    return [
        'content' => $content,
        'model' => 'mock',
        'response_id' => 'mock-' . date('YmdHis'),
    ];
}

function requestOpenAiTeachingMaterial(array $material, array $knowledgeContext = []): array
{
    $payload = [
        'model' => getOpenAiModelName(),
        'instructions' => buildAiTeachingSystemInstruction(),
        'input' => buildAiTeachingMaterialPrompt($material, $knowledgeContext),
    ];
    $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);

    if ($encodedPayload === false) {
        throw new RuntimeException('Не удалось подготовить запрос к OpenAI.');
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . trim((string)OPENAI_API_KEY),
            ],
            'content' => $encodedPayload,
            'ignore_errors' => true,
            'timeout' => 45,
        ],
    ]);

    $rawResponse = @file_get_contents('https://api.openai.com/v1/responses', false, $context);

    if ($rawResponse === false) {
        throw new RuntimeException('Не удалось подключиться к OpenAI API. Проверьте интернет, DNS/SSL и доступ сервера к api.openai.com.');
    }

    $statusCode = parseOpenAiStatusCode($http_response_header ?? []);
    $response = json_decode($rawResponse, true);

    if (!is_array($response)) {
        throw new RuntimeException('OpenAI API вернул ответ в неожиданном формате. Попробуйте повторить генерацию позже.');
    }

    if ($statusCode >= 400) {
        throw new RuntimeException(buildOpenAiUserErrorMessage($statusCode, $response));
    }

    $responseStatus = (string)($response['status'] ?? '');

    if (in_array($responseStatus, ['failed', 'cancelled', 'incomplete'], true)) {
        throw new RuntimeException(buildOpenAiResponseStatusMessage($responseStatus, $response));
    }

    $content = extractOpenAiText($response);

    if ($content === '') {
        throw new RuntimeException('OpenAI API не вернул текст материала. Попробуйте уточнить тему или инструкцию и запустить генерацию ещё раз.');
    }

    return [
        'content' => $content,
        'model' => (string)($response['model'] ?? getOpenAiModelName()),
        'response_id' => (string)($response['id'] ?? ''),
    ];
}

function requestYandexTeachingMaterial(array $material, array $knowledgeContext = []): array
{
    $result = requestYandexText(
        buildAiTeachingSystemInstruction(),
        buildAiTeachingMaterialPrompt($material, $knowledgeContext),
        0.4,
        3500
    );

    return [
        'content' => $result['content'],
        'model' => $result['model'],
        'response_id' => $result['response_id'],
    ];
}

function buildAiTeachingSystemInstruction(): string
{
    return implode("\n", [
        'Ты помощник учителя английского языка.',
        'Создавай учебные материалы для школьников.',
        'Пиши понятно, структурировано и без лишней воды.',
        'Если переданы источники базы знаний, используй их как основную опору и не выдумывай факты вне темы.',
        'Не публикуй материал автоматически, только подготовь текст для учителя.',
        'Не проси и не упоминай API-ключи, токены или другие секреты.',
    ]);
}

function buildAiTeachingMaterialPrompt(array $material, array $knowledgeContext = []): string
{
    $parts = [
        'Подготовь учебный материал по данным ниже.',
        '',
        'Предмет: ' . limitAiPromptText((string)$material['subject'], 300),
        'Класс: ' . limitAiPromptText((string)$material['class_title'], 100),
        'Тема: ' . limitAiPromptText((string)$material['topic'], 500),
        'Тип материала: ' . aiTeachingMaterialTypeLabel((string)$material['material_type']),
        'Время выполнения: ' . limitAiPromptText((string)($material['estimated_duration'] ?: 'не указано'), 100),
        'Язык результата: ' . (($material['language'] ?? 'ru') === 'en' ? 'English' : 'русский'),
        '',
        'Инструкция учителя:',
        limitAiPromptText((string)($material['instructions'] ?: 'Сделай короткий практический материал с заданиями и ответами для учителя.'), 3000),
        '',
        'Исходные заметки учителя:',
        limitAiPromptText((string)($material['source_notes'] ?: 'Нет дополнительных заметок.'), 5000),
    ];

    if (trim((string)($knowledgeContext['text'] ?? '')) !== '') {
        $parts[] = '';
        $parts[] = 'Источники базы знаний:';
        $parts[] = limitAiPromptText((string)$knowledgeContext['text'], 6500);
    }

    return implode("\n", $parts);
}

function limitAiPromptText(string $text, int $maxLength): string
{
    $text = trim($text);
    $length = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);

    if ($text === '' || $length <= $maxLength) {
        return $text;
    }

    $shortText = function_exists('mb_substr') ? mb_substr($text, 0, $maxLength) : substr($text, 0, $maxLength);

    return $shortText . "\n[Текст сокращён до безопасного лимита запроса.]";
}

function parseOpenAiStatusCode(array $headers): int
{
    $statusLine = $headers[0] ?? '';

    if (preg_match('/\s(\d{3})\s/', $statusLine, $matches) !== 1) {
        return 0;
    }

    return (int)$matches[1];
}

function buildOpenAiUserErrorMessage(int $statusCode, array $response): string
{
    $errorCode = (string)($response['error']['code'] ?? '');
    $errorType = (string)($response['error']['type'] ?? '');

    if ($errorCode === 'unsupported_country_region_territory') {
        return 'OpenAI API не принимает запросы с текущего региона или IP хостинга. Ключ задан, но удалённый сервер находится в неподдерживаемой зоне для OpenAI.';
    }

    if ($statusCode === 401 || str_contains($errorCode, 'invalid_api_key')) {
        return 'OpenAI API отклонил ключ. Проверьте значение OPENAI_API_KEY на сервере.';
    }

    if ($statusCode === 403) {
        return 'OpenAI API запретил доступ. Проверьте права ключа и доступность выбранной модели.';
    }

    if ($statusCode === 404) {
        return 'OpenAI API не нашёл выбранную модель. Проверьте OPENAI_MODEL на сервере.';
    }

    if ($statusCode === 408) {
        return 'OpenAI API не успел обработать запрос. Попробуйте повторить генерацию.';
    }

    if ($statusCode === 429 || str_contains($errorType, 'rate_limit')) {
        return 'OpenAI API сообщил о лимите запросов или баланса. Попробуйте позже или проверьте тариф/лимиты.';
    }

    if ($statusCode >= 500) {
        return 'На стороне OpenAI API временная ошибка. Попробуйте повторить генерацию позже.';
    }

    return 'OpenAI API вернул ошибку. Проверьте настройки OPENAI_API_KEY и OPENAI_MODEL.';
}

function buildOpenAiResponseStatusMessage(string $status, array $response): string
{
    $reason = (string)($response['incomplete_details']['reason'] ?? '');

    if ($status === 'incomplete' && $reason === 'max_output_tokens') {
        return 'OpenAI API остановил ответ из-за лимита длины. Уточните инструкцию или сделайте материал короче.';
    }

    if ($status === 'incomplete') {
        return 'OpenAI API вернул неполный ответ. Попробуйте повторить генерацию или сократить исходные заметки.';
    }

    if ($status === 'cancelled') {
        return 'OpenAI API отменил генерацию. Попробуйте запустить её ещё раз.';
    }

    return 'OpenAI API не смог завершить генерацию. Попробуйте изменить инструкцию или повторить позже.';
}

function extractOpenAiText(array $response): string
{
    if (isset($response['output_text']) && is_string($response['output_text'])) {
        return trim($response['output_text']);
    }

    $parts = [];

    foreach (($response['output'] ?? []) as $output) {
        foreach (($output['content'] ?? []) as $content) {
            if (($content['type'] ?? '') === 'output_text' && isset($content['text'])) {
                $parts[] = (string)$content['text'];
                continue;
            }

            if (($content['type'] ?? '') === 'text' && isset($content['text'])) {
                $parts[] = (string)$content['text'];
                continue;
            }

            if (isset($content['text']) && is_array($content['text']) && isset($content['text']['value'])) {
                $parts[] = (string)$content['text']['value'];
                continue;
            }

            if (($content['type'] ?? '') === 'refusal' && isset($content['refusal'])) {
                throw new RuntimeException('OpenAI API отказался выполнять запрос. Измените инструкцию и попробуйте снова.');
            }
        }
    }

    if (isset($response['choices'][0]['message']['content']) && is_string($response['choices'][0]['message']['content'])) {
        $parts[] = $response['choices'][0]['message']['content'];
    }

    return trim(implode("\n", $parts));
}
