<?php

declare(strict_types=1);

function openAiApiKey(?string $requestKey = null): string
{
    $key = trim((string)$requestKey);
    if ($key !== '') {
        return $key;
    }

    return trim(presentationEnvValue('OPENAI_API_KEY'));
}

function openAiModelName(): string
{
    $model = trim(presentationEnvValue('OPENAI_MODEL', 'gpt-4.1-mini'));
    return $model !== '' ? $model : 'gpt-4.1-mini';
}

function openAiImageModelName(): string
{
    $model = trim(presentationEnvValue('OPENAI_IMAGE_MODEL', 'gpt-image-2'));
    return $model !== '' ? $model : 'gpt-image-2';
}

function generatePresentationCardsWithOpenAi(string $apiKey, string $title, string $classTitle, array $sourceWords, ?callable $requestCards = null): array
{
    $requestCards ??= 'requestPresentationCards';
    $response = $requestCards($apiKey, $title, $classTitle, $sourceWords);
    $cardsBySource = parseCardsJson($response['text'], $sourceWords, true);

    // A long batch can occasionally omit one item. Retry only missing phrases,
    // rather than discarding all successfully generated cards.
    foreach ($sourceWords as $sourceWord) {
        $key = cardSourceKey((string)$sourceWord);
        if (isset($cardsBySource[$key])) {
            continue;
        }

        $retry = $requestCards($apiKey, $title, $classTitle, [(string)$sourceWord]);
        $cardsBySource += parseCardsJson($retry['text'], [(string)$sourceWord], true);
    }

    $cards = [];
    foreach ($sourceWords as $sourceWord) {
        $key = cardSourceKey((string)$sourceWord);
        if (!isset($cardsBySource[$key])) {
            throw new RuntimeException(($requestCards === 'requestYandexCards' ? 'Яндекс AI' : 'OpenAI') . ' не смог подготовить полную карточку и предложение для теста для выражения «' . normalizeAiWordPresentationCardText((string)$sourceWord) . '». Попробуйте повторить генерацию.');
        }
        $cards[] = $cardsBySource[$key];
    }

    return [
        'cards' => $cards,
        'model' => (string)($response['model'] ?? openAiModelName()),
        'response_id' => (string)($response['response_id'] ?? ''),
    ];
}

function requestPresentationCards(string $apiKey, string $title, string $classTitle, array $sourceWords): array
{
    $payload = [
        'model' => openAiModelName(),
        'instructions' => implode("\n", [
            'You help an English teacher create vocabulary presentation cards.',
            'The teacher input may be a single word, a multi-word expression, or a phrase with parentheses.',
            'Treat every input line as one indivisible learning item and preserve it verbatim.',
            'Return strict JSON only. Do not use markdown. Do not mention API keys or internal settings.',
            'For each learning item, return source_word, english_word, transcription, a simple English definition in hint, image_prompt in English, and example_sentence in English.',
        ]),
        'input' => buildCardsPrompt($title, $classTitle, $sourceWords),
    ];

    $response = openAiJsonRequest('https://api.openai.com/v1/responses', $apiKey, $payload, 90);

    return [
        'text' => extractPresentationApiText($response),
        'model' => (string)($response['model'] ?? openAiModelName()),
        'response_id' => (string)($response['id'] ?? ''),
    ];
}

function buildCardsPrompt(string $title, string $classTitle, array $sourceWords): string
{
    $example = '{"cards":[{"source_word":"lion","english_word":"lion","transcription":"[ˈlaɪən]","hint":"A large wild cat with a mane.","image_prompt":"A friendly lion standing in a sunny savanna, clear educational flashcard style, no text.","example_sentence":"The lion sleeps under a shady tree."}]}';

    return implode("\n", [
        'Presentation title: ' . $title,
        'Class: ' . $classTitle,
        'Teacher learning items in English (one JSON string per line):',
        implode("\n", array_map(static fn($item): string => '- ' . json_encode((string)$item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $sourceWords)),
        '',
        'JSON format:',
        $example,
        '',
        'Requirements:',
        '- return exactly one card for each learning item, including multi-word expressions;',
        '- keep the same order;',
        '- source_word and english_word must both reproduce the complete teacher learning item verbatim; do not shorten, paraphrase, split, or remove parentheses;',
        '- transcription must be short IPA in square brackets;',
        '- hint must be a short and simple definition written only in English; do not translate it into Russian;',
        '- image_prompt must describe a concrete scene that visually represents the entire word or expression, and must request no letters, no text, no logo, no watermark.',
        '- example_sentence must be one short, easy English sentence for pupils;',
        '- example_sentence must be natural English; you may add articles or pronouns and inflect verbs as necessary;',
        '- also return quiz_sentence: a complete grammatical English sentence containing the original learning item verbatim exactly once, for automatic blanking. Do not insert the blank yourself;',
        '- For a shorthand expression, choose a context in which the literal phrase is grammatical. Example: source_word="miss hometown", example_sentence="I miss my hometown when I travel.", quiz_sentence="I miss hometown traditions when I travel.";',
        '- Parenthetical notes are part of the original item and must appear once in quiz_sentence; example: "Our school facilities (a computer room and a swimming pool) help us learn and exercise.";',
    ]);
}

function openAiJsonRequest(string $url, string $apiKey, array $payload, int $timeout, int $maxAttempts = 3): array
{
    if ($apiKey === '' || preg_match('/[\r\n]/', $apiKey)) {
        throw new RuntimeException('Проверьте API-ключ OpenAI.');
    }
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
                'follow_location' => 0,
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

        $lastMessage = buildOpenAiUserErrorMessage($status, []);
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

function extractPresentationApiText(array $response): string
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

function parseCardsJson(string $text, array $sourceWords, bool $allowPartial = false): array
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

    $expected = [];
    foreach ($sourceWords as $sourceWord) {
        $expected[cardSourceKey((string)$sourceWord)] = normalizeAiWordPresentationCardText((string)$sourceWord);
    }

    $normalized = [];
    foreach ($cards as $index => $card) {
        if (!is_array($card)) {
            continue;
        }

        $returnedSource = normalizeAiWordPresentationCardText((string)($card['source_word'] ?? ''));
        $key = cardSourceKey($returnedSource);
        if (!isset($expected[$key])) {
            continue;
        }

        $sourceWord = $expected[$key];
        $englishWord = $sourceWord;
        $transcription = normalizeAiWordPresentationCardText((string)($card['transcription'] ?? ''));
        $hint = normalizeAiWordPresentationCardText((string)($card['hint'] ?? ''));
        $imagePrompt = normalizeAiWordPresentationCardText((string)($card['image_prompt'] ?? ''));
        $exampleSentence = normalizeAiWordPresentationCardText((string)($card['example_sentence'] ?? ''));
        $quizSentence = normalizeAiWordPresentationCardText((string)($card['quiz_sentence'] ?? $exampleSentence));

        if ($sourceWord === '' || $englishWord === '' || $transcription === '' || $hint === '' || $imagePrompt === '' || $exampleSentence === '') {
            continue;
        }

        if (preg_match_all('/(?<![\p{L}\p{N}])' . preg_quote($englishWord, '/') . '(?![\p{L}\p{N}])/iu', $quizSentence) !== 1) {
            continue;
        }

        $normalized[$key] = [
            'source_word' => $sourceWord,
            'english_word' => $englishWord,
            'transcription' => $transcription,
            'hint' => $hint,
            'image_prompt' => $imagePrompt,
            'example_sentence' => $exampleSentence,
            'quiz_sentence' => $quizSentence,
        ];
    }

    if (!$allowPartial && count($normalized) !== count($sourceWords)) {
        throw new RuntimeException('OpenAI returned an incomplete card set. Try again with fewer words.');
    }

    return $normalized;
}

function cardSourceKey(string $value): string
{
    return mb_strtolower(normalizeAiWordPresentationCardText($value), 'UTF-8');
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
