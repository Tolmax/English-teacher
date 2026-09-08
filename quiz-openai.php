<?php

declare(strict_types=1);

function openAiApiKey(?string $requestKey = null): string
{
    $key = trim((string)$requestKey);
    if ($key !== '') {
        return $key;
    }

    return trim(envValue('OPENAI_API_KEY'));
}

function openAiModelName(): string
{
    $model = trim(envValue('OPENAI_MODEL', 'gpt-4.1-mini'));
    return $model !== '' ? $model : 'gpt-4.1-mini';
}

function openAiImageModelName(): string
{
    $model = trim(envValue('OPENAI_IMAGE_MODEL', 'gpt-image-2'));
    return $model !== '' ? $model : 'gpt-image-2';
}

function generatePresentationCardsWithOpenAi(string $apiKey, string $title, string $classTitle, array $sourceWords): array
{
    $payload = [
        'model' => openAiModelName(),
        'instructions' => implode("\n", [
            'You help an English teacher create vocabulary presentation cards.',
            'Return strict JSON only. Do not use markdown. Do not mention API keys or internal settings.',
            'For each English word, return source_word, english_word, transcription, hint in Russian, image_prompt in English, and example_sentence in English.',
        ]),
        'input' => buildCardsPrompt($title, $classTitle, $sourceWords),
    ];

    $response = openAiJsonRequest('https://api.openai.com/v1/responses', $apiKey, $payload, 90);
    $text = extractOpenAiText($response);
    $cards = parseCardsJson($text, $sourceWords);

    return [
        'cards' => $cards,
        'model' => (string)($response['model'] ?? openAiModelName()),
        'response_id' => (string)($response['id'] ?? ''),
    ];
}

function buildCardsPrompt(string $title, string $classTitle, array $sourceWords): string
{
    $example = '{"cards":[{"source_word":"lion","english_word":"lion","transcription":"[ˈlaɪən]","hint":"Большая дикая кошка с гривой.","image_prompt":"A friendly lion standing in a sunny savanna, clear educational flashcard style, no text.","example_sentence":"The lion sleeps under a shady tree."}]}';

    return implode("\n", [
        'Presentation title: ' . $title,
        'Class: ' . $classTitle,
        'Teacher words in English:',
        implode(', ', $sourceWords),
        '',
        'JSON format:',
        $example,
        '',
        'Requirements:',
        '- return exactly one card for each word;',
        '- keep the same order;',
        '- source_word must match the teacher word;',
        '- transcription must be short IPA in square brackets;',
        '- hint must be one short Russian explanation for pupils;',
        '- image_prompt must be in English and must request no letters, no text, no logo, no watermark.',
        '- example_sentence must be one short, easy English sentence for pupils;',
        '- example_sentence must contain english_word exactly once in a natural context;',
    ]);
}

function generateCardImageWithOpenAi(string $apiKey, array $card, string $outputPath): void
{
    $word = normalizeText((string)($card['english_word'] ?? $card['source_word'] ?? 'word'));
    $description = normalizeText((string)($card['image_prompt'] ?? ''));
    $prompt = implode("\n", [
        'Create a square photorealistic educational image for an English vocabulary flashcard.',
        'Target word: ' . $word . '.',
        'Visual idea: ' . ($description !== '' ? $description : 'A clear object or scene that represents the word.'),
        'No written text, no letters, no watermark, no logo.',
        'One clear central subject, bright classroom-friendly lighting, child-safe.',
    ]);

    $payload = [
        'model' => openAiImageModelName(),
        'prompt' => $prompt,
        'size' => '1024x1024',
        'quality' => 'low',
        'output_format' => 'png',
    ];

    $response = openAiJsonRequest('https://api.openai.com/v1/images/generations', $apiKey, $payload, 150);
    $imageBase64 = (string)($response['data'][0]['b64_json'] ?? '');
    if ($imageBase64 === '') {
        throw new RuntimeException('OpenAI Image API did not return an image. Try again or reduce the number of words.');
    }

    $bytes = base64_decode($imageBase64, true);
    if ($bytes === false || $bytes === '') {
        throw new RuntimeException('Could not decode the generated image.');
    }

    $dir = dirname($outputPath);
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        throw new RuntimeException('Could not create the image folder.');
    }

    if (file_put_contents($outputPath, $bytes) === false) {
        throw new RuntimeException('Could not save the generated image.');
    }
}

function openAiJsonRequest(string $url, string $apiKey, array $payload, int $timeout, int $maxAttempts = 3): array
{
    $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($encoded === false) {
        throw new RuntimeException('Could not prepare the OpenAI request.');
    }

    $lastMessage = 'OpenAI API error.';
    $lastStatus = 0;

    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey,
                ],
                'content' => $encoded,
                'ignore_errors' => true,
                'timeout' => $timeout,
            ],
        ]);

        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) {
            $lastMessage = 'Could not connect to OpenAI API. Check internet access and the API key.';
            if ($attempt < $maxAttempts) {
                sleep($attempt * 2);
                continue;
            }
            throw new RuntimeException($lastMessage);
        }

        $status = parseHttpStatus($http_response_header ?? []);
        $lastStatus = $status;
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            $lastMessage = 'OpenAI returned an unexpected response.';
            if ($attempt < $maxAttempts && openAiShouldRetry($status)) {
                sleep($attempt * 2);
                continue;
            }
            throw new RuntimeException($lastMessage);
        }

        if ($status < 400) {
            return $decoded;
        }

        $lastMessage = (string)($decoded['error']['message'] ?? 'OpenAI API error.');
        if ($attempt < $maxAttempts && openAiShouldRetry($status)) {
            sleep($attempt * 2);
            continue;
        }

        break;
    }

    if (openAiShouldRetry($lastStatus)) {
        throw new RuntimeException('OpenAI временно не смог обработать запрос после нескольких попыток. Повторите генерацию чуть позже. Последняя ошибка ' . $lastStatus . ': ' . $lastMessage);
    }

    throw new RuntimeException('OpenAI error ' . $lastStatus . ': ' . $lastMessage);
}

function openAiShouldRetry(int $status): bool
{
    return in_array($status, [0, 429, 500, 502, 503, 504], true);
}

function parseHttpStatus(array $headers): int
{
    foreach ($headers as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d+)/', (string)$header, $m)) {
            return (int)$m[1];
        }
    }

    return 0;
}

function extractOpenAiText(array $response): string
{
    if (isset($response['output_text']) && is_string($response['output_text'])) {
        return $response['output_text'];
    }

    $parts = [];
    foreach ((array)($response['output'] ?? []) as $item) {
        foreach ((array)($item['content'] ?? []) as $content) {
            if (isset($content['text']) && is_string($content['text'])) {
                $parts[] = $content['text'];
            }
        }
    }

    $text = trim(implode("\n", $parts));
    if ($text === '') {
        throw new RuntimeException('OpenAI returned an empty text response.');
    }

    return $text;
}

function parseCardsJson(string $text, array $sourceWords): array
{
    $json = trim($text);
    $json = preg_replace('/^```(?:json)?/i', '', $json) ?? $json;
    $json = preg_replace('/```$/', '', trim($json)) ?? $json;
    $json = extractJsonObject($json);
    $data = json_decode($json, true);

    if (!is_array($data)) {
        throw new RuntimeException('OpenAI did not return valid card JSON.');
    }

    $cards = $data['cards'] ?? $data;
    if (!is_array($cards)) {
        throw new RuntimeException('OpenAI did not return a card list.');
    }

    $normalized = [];
    foreach ($sourceWords as $index => $sourceWord) {
        $card = $cards[$index] ?? null;
        if (!is_array($card)) {
            continue;
        }

        $sourceWord = normalizeText((string)$sourceWord);
        $englishWord = normalizeText((string)($card['english_word'] ?? $sourceWord));
        $transcription = normalizeText((string)($card['transcription'] ?? ''));
        $hint = normalizeText((string)($card['hint'] ?? ''));
        $imagePrompt = normalizeText((string)($card['image_prompt'] ?? ''));
        $exampleSentence = normalizeText((string)($card['example_sentence'] ?? ''));

        if ($sourceWord === '' || $englishWord === '' || $transcription === '' || $hint === '' || $imagePrompt === '' || $exampleSentence === '') {
            continue;
        }

        if (preg_match('/(?<![\p{L}\p{N}])' . preg_quote($englishWord, '/') . '(?![\p{L}\p{N}])/iu', $exampleSentence) !== 1) {
            continue;
        }

        $normalized[] = [
            'source_word' => $sourceWord,
            'english_word' => $englishWord,
            'transcription' => $transcription,
            'hint' => $hint,
            'image_prompt' => $imagePrompt,
            'example_sentence' => $exampleSentence,
        ];
    }

    if (count($normalized) !== count($sourceWords)) {
        throw new RuntimeException('OpenAI returned an incomplete card set. Try again with fewer words.');
    }

    return $normalized;
}

function extractJsonObject(string $text): string
{
    $start = strpos($text, '{');
    if ($start === false) {
        return trim($text);
    }

    $depth = 0;
    $inString = false;
    $escape = false;
    $length = strlen($text);

    for ($i = $start; $i < $length; $i++) {
        $char = $text[$i];
        if ($escape) {
            $escape = false;
            continue;
        }
        if ($char === '\\') {
            $escape = $inString;
            continue;
        }
        if ($char === '"') {
            $inString = !$inString;
            continue;
        }
        if ($inString) {
            continue;
        }
        if ($char === '{') {
            $depth++;
        }
        if ($char === '}') {
            $depth--;
            if ($depth === 0) {
                return substr($text, $start, $i - $start + 1);
            }
        }
    }

    return trim($text);
}
