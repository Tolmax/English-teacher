<?php

require_once ROOT . 'app/services/openai-teacher-assistant.php';

function generateAiWordPresentationCards(array $presentation, ?string $provider = null): array
{
    $sourceWords = aiWordPresentationSourceWords($presentation);

    if ($sourceWords === []) {
        throw new RuntimeException('В черновике нет английских слов для генерации карточек.');
    }

    if (trim((string)OPENAI_API_KEY) === '') {
        return [
            'cards' => generateMockAiWordPresentationCards($sourceWords),
            'model' => 'mock',
            'response_id' => 'mock-word-cards-' . date('YmdHis'),
        ];
    }

    return requestOpenAiWordPresentationCards($presentation, $sourceWords);
}

function requestOpenAiWordPresentationCards(array $presentation, array $sourceWords): array
{
    $payload = [
        'model' => getOpenAiModelName(),
        'instructions' => buildAiWordPresentationSystemInstruction(),
        'input' => buildAiWordPresentationPrompt($presentation, $sourceWords),
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
        throw new RuntimeException('Не удалось подключиться к OpenAI API. Проверьте доступ сервера к api.openai.com.');
    }

    $statusCode = parseOpenAiStatusCode($http_response_header ?? []);
    $response = json_decode($rawResponse, true);

    if (!is_array($response)) {
        throw new RuntimeException('OpenAI API вернул ответ в неожиданном формате.');
    }

    if ($statusCode >= 400) {
        throw new RuntimeException(buildOpenAiUserErrorMessage($statusCode, $response));
    }

    $cards = parseAiWordPresentationCardsJson(extractOpenAiText($response), $sourceWords);

    return [
        'cards' => $cards,
        'model' => (string)($response['model'] ?? getOpenAiModelName()),
        'response_id' => (string)($response['id'] ?? ''),
    ];
}

function buildAiWordPresentationSystemInstruction(): string
{
    return implode("\n", [
        'Ты помощник учителя английского языка.',
        'Создавай словарные карточки для школьной презентации.',
        'Учитель уже дал слова на английском языке, переводить с русского не нужно.',
        'Для каждого слова верни транскрипцию, короткую подсказку на русском и описание картинки.',
        'Ответ возвращай строго в JSON без markdown и без пояснений.',
        'Не используй HTML.',
        'Не упоминай API-ключи, токены и внутренние настройки.',
    ]);
}

function buildAiWordPresentationPrompt(array $presentation, array $sourceWords): string
{
    $exampleJson = '{"cards":[{"source_word":"lion","english_word":"lion","transcription":"[ˈlaɪən]","hint":"Большая дикая кошка с гривой.","image_prompt":"A friendly lion standing in a sunny savanna, clear educational flashcard style."}]}';

    return implode("\n", [
        'Подготовь карточки для ИИ-презентации слов.',
        '',
        'Название презентации: ' . limitAiPromptText((string)($presentation['title'] ?? 'Word presentation'), 300),
        'Класс: ' . limitAiPromptText((string)($presentation['class_title'] ?? ''), 100),
        '',
        'Слова учителя на английском:',
        implode(', ', $sourceWords),
        '',
        'Формат JSON:',
        $exampleJson,
        '',
        'Требования:',
        '- верни ровно столько cards, сколько слов передано;',
        '- порядок cards должен совпадать с порядком слов;',
        '- source_word должен быть исходным словом учителя;',
        '- english_word должен быть нормальным вариантом этого же английского слова для показа;',
        '- transcription должна быть короткой английской IPA-транскрипцией в квадратных скобках;',
        '- hint: одно короткое русское объяснение для ученика;',
        '- image_prompt: короткое описание картинки на английском для будущей иллюстрации;',
        '- не добавляй лишние карточки и не пропускай слова.',
    ]);
}

function parseAiWordPresentationCardsJson(string $text, array $sourceWords): array
{
    $json = trim($text);
    $json = preg_replace('/^```(?:json)?/i', '', $json) ?? $json;
    $json = preg_replace('/```$/', '', trim($json)) ?? $json;
    $json = extractAiWordPresentationJsonObject($json);
    $data = json_decode(trim($json), true);

    if (!is_array($data)) {
        throw new RuntimeException('OpenAI не вернул корректный JSON карточек.');
    }

    $cards = $data['cards'] ?? $data;

    if (!is_array($cards)) {
        throw new RuntimeException('OpenAI не вернул список карточек.');
    }

    $normalizedCards = normalizeAiWordPresentationGeneratedCards($cards, $sourceWords);

    if (count($normalizedCards) !== count($sourceWords)) {
        throw new RuntimeException('OpenAI вернул неполный набор карточек. Попробуйте повторить генерацию.');
    }

    return $normalizedCards;
}

function extractAiWordPresentationJsonObject(string $text): string
{
    $start = strpos($text, '{');

    if ($start === false) {
        return trim($text);
    }

    $depth = 0;
    $inString = false;
    $escape = false;
    $length = strlen($text);

    for ($index = $start; $index < $length; $index++) {
        $char = $text[$index];

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
                return substr($text, $start, $index - $start + 1);
            }
        }
    }

    return trim($text);
}

function normalizeAiWordPresentationGeneratedCards(array $cards, array $sourceWords): array
{
    $normalizedCards = [];

    foreach ($sourceWords as $index => $sourceWord) {
        $card = $cards[$index] ?? null;

        if (!is_array($card)) {
            continue;
        }

        $sourceWord = normalizeAiWordPresentationCardText((string)$sourceWord);
        $englishWord = normalizeAiWordPresentationCardText((string)($card['english_word'] ?? $sourceWord));
        $transcription = normalizeAiWordPresentationCardText((string)($card['transcription'] ?? ''));
        $hint = normalizeAiWordPresentationCardText((string)($card['hint'] ?? ''));
        $imagePrompt = normalizeAiWordPresentationCardText((string)($card['image_prompt'] ?? ''));

        if ($sourceWord === '' || $englishWord === '' || $transcription === '' || $hint === '' || $imagePrompt === '') {
            continue;
        }

        $normalizedCards[] = [
            'source_word' => $sourceWord,
            'english_word' => $englishWord,
            'transcription' => $transcription,
            'hint' => $hint,
            'image_prompt' => $imagePrompt,
        ];
    }

    return $normalizedCards;
}

function generateMockAiWordPresentationCards(array $sourceWords): array
{
    $cards = [];

    foreach (array_slice($sourceWords, 0, 10) as $sourceWord) {
        $word = normalizeAiWordPresentationCardText((string)$sourceWord);

        if ($word === '') {
            continue;
        }

        $cards[] = [
            'source_word' => $word,
            'english_word' => $word,
            'transcription' => '[' . strtolower($word) . ']',
            'hint' => 'Короткая подсказка для слова "' . $word . '".',
            'image_prompt' => 'A simple bright educational flashcard illustration for the English word "' . $word . '".',
        ];
    }

    return normalizeAiWordPresentationCards($cards);
}
