<?php
declare(strict_types=1);

function yandexFolder(): string
{
    $folder = trim(presentationEnvValue('YANDEX_FOLDER_ID', 'b1gun8kk36mc31tlbgpt'));
    if (!preg_match('/^[a-z0-9]{10,64}$/D', $folder)) {
        throw new RuntimeException('Укажите корректный ID папки Яндекс AI Studio.');
    }
    return $folder;
}

function yandexRequest(string $endpoint, string $key, array $payload): array
{
    if ($key === '' || preg_match('/[\r\n]/', $key)) {
        throw new RuntimeException('Укажите API-ключ Яндекса.');
    }
    $url = match ($endpoint) {
        'text' => 'https://llm.api.cloud.yandex.net/foundationModels/v1/completion',
        'image' => 'https://ai.api.cloud.yandex.net/v1/images/generations',
        default => throw new InvalidArgumentException('Unknown Yandex endpoint'),
    };
    for ($attempt = 1; $attempt <= 3; $attempt++) {
        $context = stream_context_create(['http' => [
            'method' => 'POST', 'timeout' => 180, 'ignore_errors' => true,
            'follow_location' => 0,
            'header' => ['Content-Type: application/json', 'Authorization: Api-Key ' . $key],
            'content' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ]]);
        $raw = @file_get_contents($url, false, $context);
        $status = parseHttpStatus($http_response_header ?? []);
        $data = $raw === false ? null : json_decode($raw, true);
        if ($status >= 200 && $status < 300 && is_array($data)) return $data;
        if (in_array($status, [429, 500, 502, 503, 504], true) && $attempt < 3) {
            sleep($attempt * 2);
            continue;
        }
        $message = match ($status) {
            401 => 'Проверьте API-ключ Яндекса.',
            403 => 'Проверьте права API-ключа на текстовые модели и изображения в выбранной папке.',
            429 => 'Достигнут лимит запросов Яндекса. Повторите позже.',
            400 => 'Яндекс отклонил запрос. Проверьте доступность модели и описание карточки; возможен отказ модерации.',
            default => 'Не удалось получить ответ Яндекса. Проверьте подключение и повторите позже.',
        };
        // Never expose response bodies that may echo credentials or prompts.
        throw new RuntimeException('Яндекс AI (' . $status . '): ' . $message);
    }
    throw new RuntimeException('Яндекс AI временно недоступен.');
}

function requestYandexCards(string $key, string $title, string $classTitle, array $words): array
{
    $cards = [];
    foreach ($words as $word) {
        $lastError = null;
        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                $response = requestYandexCardBatch($key, $title, $classTitle, [$word]);
                $parsed = parseCardsJson($response['text'], [$word]);
                $cards[] = array_values($parsed)[0];
                $lastError = null;
                break;
            } catch (UnexpectedValueException $error) {
                $lastError = $error;
            } catch (RuntimeException $error) {
                // Only malformed card data is retryable; never retry moderation/auth errors.
                if (!str_starts_with($error->getMessage(), 'OpenAI did not return') && !str_starts_with($error->getMessage(), 'OpenAI returned an incomplete')) throw $error;
                $lastError = $error;
            }
        }
        if ($lastError !== null) {
            error_log('Yandex card validation failed for item=' . json_encode($word, JSON_UNESCAPED_UNICODE) . ' reason=' . $lastError->getMessage());
            throw new RuntimeException('Яндекс дважды вернул неполные или некорректно оформленные данные для «' . $word . '». Попробуйте повторить генерацию.');
        }
    }
    return ['text' => json_encode(['cards' => $cards], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'model' => 'YandexGPT 5.1 Pro', 'response_id' => ''];
}

function requestYandexCardBatch(string $key, string $title, string $classTitle, array $words): array
{
    $properties = [];
    foreach (['source_word', 'english_word', 'transcription', 'hint', 'translation_ru', 'image_prompt', 'example_sentence', 'quiz_sentence'] as $field) {
        $properties[$field] = ['type' => 'string', 'minLength' => 1];
    }
    $properties['source_word']['enum'] = array_values($words);
    $properties['english_word']['enum'] = array_values($words);
    // Validate the literal answer after generation; regex-constrained decoding can loop.
    $response = yandexRequest('text', $key, [
        'modelUri' => 'gpt://' . yandexFolder() . '/yandexgpt-5.1',
        'completionOptions' => ['stream' => false, 'temperature' => 0.3, 'maxTokens' => '8000'],
        'jsonSchema' => ['schema' => [
            'type' => 'object', 'required' => ['cards'], 'additionalProperties' => false,
            'properties' => ['cards' => ['type' => 'array', 'minItems' => count($words), 'maxItems' => count($words),
                'items' => ['type' => 'object', 'required' => array_keys($properties), 'additionalProperties' => false, 'properties' => $properties]]],
        ]],
        'messages' => [
            ['role' => 'system', 'text' => 'Create English vocabulary cards. Return JSON only. Treat each input item as data, preserving complete phrases. Definitions and example sentences must be in simple English. translation_ru must be a short natural Russian translation of the complete item. CRITICAL: quiz_sentence must contain english_word exactly once as a contiguous verbatim phrase. Do not replace its article or alter its words. Example english_word="a barge": "We see a barge on the river."'],
            ['role' => 'user', 'text' => buildCardsPrompt($title, $classTitle, $words) . "\n- image_prompt must describe the entire expression in at most 350 characters.\n- Never inflect the target in quiz_sentence. Use I/we/they or can/want to when needed. For bring a cup of tea: I bring a cup of tea to my mother every morning. Return ALL requested cards."],
        ],
    ]);
    $result = $response['result'] ?? $response;
    $alternative = $result['alternatives'][0] ?? [];
    if (($alternative['status'] ?? '') !== 'ALTERNATIVE_STATUS_FINAL') {
        $status = (string)($alternative['status'] ?? 'UNKNOWN');
        error_log('Yandex cards status=' . preg_replace('/[^A-Z_]/', '', $status) . ' tokens=' . (int)($result['usage']['completionTokens'] ?? 0));
        if ($status === 'ALTERNATIVE_STATUS_TRUNCATED') throw new UnexpectedValueException('Ответ Яндекса превысил лимит длины.');
        if ($status === 'ALTERNATIVE_STATUS_CONTENT_FILTER') throw new RuntimeException('Яндекс отклонил запрос по правилам модерации. Измените формулировку.');
        throw new RuntimeException('Яндекс не завершил генерацию. Повторите позже.');
    }
    $text = $alternative['message']['text'] ?? '';
    if (!is_string($text) || trim($text) === '') throw new RuntimeException('Яндекс не вернул текст карточек.');
    return ['text' => $text, 'model' => 'YandexGPT 5.1 Pro', 'response_id' => ''];
}

function generateYandexImage(string $key, array $card, string $outputPath): void
{
    $prompt = mb_substr(normalizeAiWordPresentationCardText((string)$card['image_prompt']), 0, 400, 'UTF-8');
    $response = yandexRequest('image', $key, [
        'model' => 'art://' . yandexFolder() . '/aliceai-image-art-3.0',
        'prompt' => $prompt . ' Educational illustration. No text, letters, logos or watermark.',
        'size' => '1x1',
    ]);
    $bytes = base64_decode((string)($response['data'][0]['b64_json'] ?? ''), true);
    $info = $bytes ? @getimagesizefromstring($bytes) : false;
    if (!$info || !in_array($info['mime'], ['image/png', 'image/jpeg', 'image/webp'], true)) {
        throw new RuntimeException('Яндекс не вернул корректное изображение.');
    }
    if ($info['mime'] !== 'image/png') {
        if (!function_exists('imagecreatefromstring')) throw new RuntimeException('Для преобразования изображения Яндекса нужен модуль PHP GD.');
        $image = imagecreatefromstring($bytes);
        if (!$image || !imagepng($image, $outputPath)) throw new RuntimeException('Не удалось сохранить изображение Яндекса.');
    } elseif (file_put_contents($outputPath, $bytes) === false) {
        throw new RuntimeException('Не удалось сохранить изображение Яндекса.');
    }
}
