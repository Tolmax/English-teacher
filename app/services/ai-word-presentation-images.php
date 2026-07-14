<?php

require_once ROOT . 'app/services/openai-teacher-assistant.php';
require_once ROOT . 'app/models/ai-word-presentation.php';

function prepareAiWordPresentationImages(array $cards, int $materialId): array
{
    $imageDir = ROOT . 'uploads/materials/' . $materialId . '/images/';

    if (!is_dir($imageDir) && !mkdir($imageDir, 0755, true)) {
        throw new RuntimeException('Не удалось создать папку для изображений презентации.');
    }

    $preparedCards = [];
    $provider = trim((string)OPENAI_API_KEY) !== '' ? 'openai' : 'mock';

    foreach (array_values($cards) as $index => $card) {
        $card = is_array($card) ? $card : [];
        $existingRelativePath = ltrim(str_replace('\\', '/', trim((string)($card['image_path'] ?? ''))), '/');
        $existingPath = $existingRelativePath !== '' ? ROOT . $existingRelativePath : '';

        if ($existingPath !== '' && is_file($existingPath) && filesize($existingPath) > 0) {
            $card['image_path'] = $existingRelativePath;
            $card['image_mime'] = (string)($card['image_mime'] ?? 'image/png') ?: 'image/png';
            $preparedCards[] = $card;
            continue;
        }

        $imageName = 'card_' . str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) . '.png';
        $imagePath = $imageDir . $imageName;
        $tempPath = $imagePath . '.tmp';
        $relativePath = 'uploads/materials/' . $materialId . '/images/' . $imageName;

        if (is_file($tempPath)) {
            @unlink($tempPath);
        }

        try {
            createAiWordPresentationImage($card, $tempPath, $provider);
            ensureAiWordPresentationImageFile($tempPath);

            if (is_file($imagePath)) {
                @unlink($imagePath);
            }

            if (!rename($tempPath, $imagePath)) {
                throw new RuntimeException('Не удалось сохранить изображение презентации.');
            }
        } catch (Throwable $exception) {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }

            throw $exception;
        }

        $card['image_path'] = $relativePath;
        $card['image_mime'] = 'image/png';
        $preparedCards[] = $card;
    }

    return normalizeAiWordPresentationCards($preparedCards);
}

function createAiWordPresentationImage(array $card, string $outputPath, string $provider): void
{
    if ($provider !== 'openai' || !hasConfiguredAiImageProvider('openai')) {
        createMockAiWordPresentationImage($card, $outputPath);
        return;
    }

    requestOpenAiWordPresentationImage($card, $outputPath);
}

function requestOpenAiWordPresentationImage(array $card, string $outputPath): void
{
    $payload = [
        'model' => getOpenAiImageModelName(),
        'prompt' => buildAiWordPresentationImagePrompt($card),
        'size' => '1024x1024',
        'quality' => 'low',
        'output_format' => 'png',
    ];
    $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);

    if ($encodedPayload === false) {
        throw new RuntimeException('Не удалось подготовить запрос изображения к OpenAI.');
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
            'timeout' => 130,
        ],
    ]);

    $rawResponse = @file_get_contents('https://api.openai.com/v1/images/generations', false, $context);

    if ($rawResponse === false) {
        throw new RuntimeException('Не удалось подключиться к OpenAI Image API. Проверьте доступ сервера к api.openai.com.');
    }

    $statusCode = parseOpenAiStatusCode($http_response_header ?? []);
    $response = json_decode($rawResponse, true);

    if (!is_array($response)) {
        throw new RuntimeException('OpenAI Image API вернул ответ в неожиданном формате.');
    }

    if ($statusCode >= 400) {
        throw new RuntimeException(buildOpenAiUserErrorMessage($statusCode, $response));
    }

    $imageBase64 = (string)($response['data'][0]['b64_json'] ?? '');
    if ($imageBase64 === '') {
        throw new RuntimeException('OpenAI Image API не вернул изображение. Попробуйте повторить сборку.');
    }

    $imageBytes = base64_decode($imageBase64, true);
    if ($imageBytes === false || $imageBytes === '') {
        throw new RuntimeException('Не удалось декодировать изображение OpenAI.');
    }

    saveAiWordPresentationImageBytes($imageBytes, $outputPath);
}

function requestGigaChatWordPresentationImage(array $card, string $outputPath): void
{
    $response = requestGigaChatChatCompletion(
        [
            [
                'role' => 'system',
                'content' => 'Ты создаёшь школьные иллюстрации для карточек английских слов. Верни только изображение, без длинного текста.',
            ],
            [
                'role' => 'user',
                'content' => buildGigaChatWordPresentationImagePrompt($card),
            ],
        ],
        getGigaChatImageModelName(),
        0.2,
        800
    );

    $imageBytes = extractGigaChatImageBytes($response);
    saveAiWordPresentationImageBytes($imageBytes, $outputPath);
}

function buildAiWordPresentationImagePrompt(array $card): string
{
    $word = normalizeAiWordPresentationCardText((string)($card['english_word'] ?? $card['source_word'] ?? 'word'));
    $description = normalizeAiWordPresentationCardText((string)($card['image_prompt'] ?? ''));

    return implode("\n", [
        'Create a square photorealistic educational image for an English vocabulary flashcard.',
        'Target word: ' . $word . '.',
        'Visual idea: ' . ($description !== '' ? $description : 'A clear object or scene that represents the word.'),
        'Requirements:',
        '- no written text, no letters, no watermark, no logo;',
        '- one clear central subject;',
        '- bright classroom-friendly lighting;',
        '- simple clean background;',
        '- child-safe and suitable for school.',
    ]);
}

function buildGigaChatWordPresentationImagePrompt(array $card): string
{
    $word = normalizeAiWordPresentationCardText((string)($card['english_word'] ?? $card['source_word'] ?? 'word'));
    $description = normalizeAiWordPresentationCardText((string)($card['image_prompt'] ?? ''));

    return implode("\n", [
        'Нарисуй квадратную фотореалистичную иллюстрацию для учебной карточки английского слова.',
        'Слово: ' . $word . '.',
        'Идея картинки: ' . ($description !== '' ? $description : 'понятный предмет или простая сцена, которая объясняет слово.'),
        'Важно: без букв, без текста, без логотипов, без водяных знаков.',
        'Один главный объект, светлый школьный стиль, безопасно для детей.',
    ]);
}

function extractGigaChatImageBytes(array $response): string
{
    $references = extractGigaChatImageReferences($response);

    foreach ($references as $reference) {
        $bytes = loadGigaChatImageReference($reference);

        if ($bytes !== '') {
            return $bytes;
        }
    }

    throw new RuntimeException('GigaChat не вернул изображение для карточки. Попробуйте повторить сборку или временно выберите AI_IMAGE_PROVIDER=mock.');
}

function extractGigaChatImageReferences(array $response): array
{
    $message = $response['choices'][0]['message'] ?? [];
    $contentValue = is_array($message) ? ($message['content'] ?? '') : '';
    $content = is_string($contentValue) ? $contentValue : json_encode($contentValue, JSON_UNESCAPED_UNICODE);
    $content = is_string($content) ? $content : '';
    $references = [];

    foreach ((array)($message['attachments'] ?? []) as $attachment) {
        if (!is_array($attachment)) {
            continue;
        }

        foreach (['file_id', 'id', 'url', 'download_url'] as $key) {
            $value = trim((string)($attachment[$key] ?? ''));
            if ($value !== '') {
                $references[] = $value;
            }
        }
    }

    foreach ((array)($response['attachments'] ?? []) as $attachment) {
        if (!is_array($attachment)) {
            continue;
        }

        foreach (['file_id', 'id', 'url', 'download_url'] as $key) {
            $value = trim((string)($attachment[$key] ?? ''));
            if ($value !== '') {
                $references[] = $value;
            }
        }
    }

    if ($content !== '') {
        if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/iu', $content, $matches)) {
            $references = array_merge($references, $matches[1]);
        }

        if (preg_match_all('/!\[[^\]]*]\(([^)]+)\)/u', $content, $matches)) {
            $references = array_merge($references, $matches[1]);
        }

        if (preg_match_all('/(?:file|gigachat):\/\/([A-Za-z0-9._:-]+)/u', $content, $matches)) {
            $references = array_merge($references, $matches[1]);
        }

        if (preg_match_all('/\b[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\b/iu', $content, $matches)) {
            $references = array_merge($references, $matches[0]);
        }
    }

    $references = array_map(static fn($value) => trim(html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8')), $references);
    $references = array_filter($references, static fn($value) => $value !== '');

    return array_values(array_unique($references));
}

function loadGigaChatImageReference(string $reference): string
{
    if (str_starts_with($reference, 'data:image/')) {
        $parts = explode(',', $reference, 2);
        $bytes = base64_decode($parts[1] ?? '', true);

        return is_string($bytes) ? $bytes : '';
    }

    $accessToken = getGigaChatAccessToken();
    $headers = [
        'Accept: image/png,image/jpeg,image/*;q=0.9,*/*;q=0.8',
        'Authorization: Bearer ' . $accessToken,
    ];
    $url = normalizeGigaChatImageReferenceUrl($reference);
    $http = requestGigaChatBinary(
        $url,
        $headers,
        90,
        'GigaChat Files API',
        parse_url($url, PHP_URL_HOST) ?: 'gigachat.devices.sberbank.ru'
    );

    if ((int)$http['status'] >= 400) {
        $decoded = json_decode($http['body'], true);
        $message = is_array($decoded)
            ? buildGigaChatUserErrorMessage((int)$http['status'], $decoded)
            : 'GigaChat не смог отдать сгенерированное изображение.';
        throw new RuntimeException($message);
    }

    return (string)$http['body'];
}

function normalizeGigaChatImageReferenceUrl(string $reference): string
{
    $reference = trim($reference);

    if (preg_match('/^https?:\/\//i', $reference) === 1) {
        return $reference;
    }

    $reference = preg_replace('/^\/?files\//i', '', $reference) ?? $reference;
    $reference = preg_replace('/\/content$/i', '', $reference) ?? $reference;

    return rtrim((string)GIGACHAT_API_BASE_URL, '/') . '/files/' . rawurlencode($reference) . '/content';
}

function saveAiWordPresentationImageBytes(string $imageBytes, string $outputPath): void
{
    if ($imageBytes === '') {
        throw new RuntimeException('Изображение для карточки оказалось пустым.');
    }

    if (function_exists('imagecreatefromstring')) {
        $image = @imagecreatefromstring($imageBytes);

        if ($image === false) {
            throw new RuntimeException('Сервис вернул файл, но это не похоже на PNG/JPG-изображение.');
        }

        $directory = dirname($outputPath);
        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            imagedestroy($image);
            throw new RuntimeException('Не удалось создать папку для изображения презентации.');
        }

        $saved = imagepng($image, $outputPath);
        imagedestroy($image);

        if (!$saved) {
            throw new RuntimeException('Не удалось сохранить изображение презентации.');
        }

        return;
    }

    if (strncmp($imageBytes, "\x89PNG", 4) !== 0) {
        throw new RuntimeException('Для сохранения JPG-изображений на сервере нужна PHP-библиотека GD.');
    }

    if (file_put_contents($outputPath, $imageBytes) === false) {
        throw new RuntimeException('Не удалось сохранить изображение презентации.');
    }
}

function ensureAiWordPresentationImageFile(string $path): void
{
    if (!is_file($path) || filesize($path) <= 0) {
        throw new RuntimeException('Изображение для карточки не было создано. Попробуйте повторить сборку PPTX.');
    }

    if (function_exists('getimagesize')) {
        $size = @getimagesize($path);
        if (!is_array($size) || empty($size[0]) || empty($size[1])) {
            throw new RuntimeException('Созданный файл не похож на рабочее изображение.');
        }
    }
}

function createMockAiWordPresentationImage(array $card, string $outputPath): void
{
    if (!function_exists('imagecreatetruecolor')) {
        createFallbackAiWordPresentationImage($outputPath);
        return;
    }

    $word = normalizeAiWordPresentationCardText((string)($card['english_word'] ?? $card['source_word'] ?? 'word'));
    $image = imagecreatetruecolor(1024, 1024);
    $background = imagecolorallocate($image, 235, 241, 250);
    $accent = imagecolorallocate($image, 49, 95, 184);
    $text = imagecolorallocate($image, 30, 38, 51);
    imagefilledrectangle($image, 0, 0, 1024, 1024, $background);
    imagefilledellipse($image, 512, 420, 430, 430, imagecolorallocate($image, 216, 226, 241));
    imagefilledellipse($image, 512, 420, 300, 300, imagecolorallocate($image, 255, 255, 255));
    imagerectangle($image, 90, 90, 934, 934, $accent);
    imagestring($image, 5, 430, 760, $word !== '' ? $word : 'word', $text);

    if (!imagepng($image, $outputPath)) {
        imagedestroy($image);
        throw new RuntimeException('Не удалось сохранить тестовую картинку презентации.');
    }

    imagedestroy($image);
}

function createFallbackAiWordPresentationImage(string $outputPath): void
{
    $pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAEAAAAAsCAIAAADkCJnPAAAACXBIWXMAAAsTAAALEwEAmpwYAAAARklEQVRoge3PQQ0AIBDAMMC/5+ONAvZoFSzZnZndGR6w56sAAQIECBAgQIAAAQIECBAgQIAAAQIECBAgQIAAAQIECBAg4LsD8ZABqRLuHhoAAAAASUVORK5CYII=';
    $bytes = base64_decode($pngBase64, true);

    if ($bytes === false || file_put_contents($outputPath, $bytes) === false) {
        throw new RuntimeException('Не удалось сохранить fallback-картинку презентации.');
    }
}

function deleteAiWordPresentationGeneratedImages(int $materialId): void
{
    $imageDir = ROOT . 'uploads/materials/' . $materialId . '/images/';

    if (!is_dir($imageDir)) {
        return;
    }

    foreach (glob($imageDir . '*') ?: [] as $path) {
        if (is_file($path)) {
            unlink($path);
        }
    }

    @rmdir($imageDir);
}

function deleteAiWordPresentationMaterialDirectory(int $materialId): bool
{
    if ($materialId <= 0) {
        return true;
    }

    $basePath = ROOT . 'uploads/materials/';
    $targetPath = $basePath . $materialId;
    $baseDir = realpath($basePath);
    $targetDir = realpath($targetPath);

    if ($targetDir === false) {
        return true;
    }

    if ($baseDir === false || !str_starts_with($targetDir, $baseDir)) {
        return false;
    }

    $deleted = true;

    try {
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($targetDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
    } catch (Throwable) {
        return false;
    }

    foreach ($items as $item) {
        $path = $item->getPathname();

        if ($item->isDir()) {
            @chmod($path, 0775);
            if (is_dir($path) && !@rmdir($path)) {
                $deleted = false;
            }
            continue;
        }

        @chmod($path, 0664);
        if (is_file($path) && !@unlink($path)) {
            $deleted = false;
        }
    }

    @chmod($targetDir, 0775);
    if (is_dir($targetDir) && !@rmdir($targetDir)) {
        $deleted = false;
    }

    return $deleted && !is_dir($targetDir);
}
