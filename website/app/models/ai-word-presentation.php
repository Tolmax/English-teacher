<?php

function getAiWordPresentationsForAdmin(): array
{
    $db = getDB();

    return $db->query(
        'SELECT ai_word_presentations.*,
                classes.title AS class_title,
                classes.slug AS class_slug,
                materials.title AS material_title,
                material_files.original_name AS pptx_original_name
         FROM ai_word_presentations
         INNER JOIN classes ON classes.id = ai_word_presentations.class_id
         LEFT JOIN materials ON materials.id = ai_word_presentations.material_id
         LEFT JOIN material_files ON material_files.id = ai_word_presentations.pptx_file_id
         ORDER BY ai_word_presentations.updated_at DESC, ai_word_presentations.id DESC'
    )->fetchAll();
}

function getAiWordPresentationById(int $id): array|false
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT ai_word_presentations.*,
                classes.title AS class_title,
                classes.slug AS class_slug,
                materials.title AS material_title,
                material_files.original_name AS pptx_original_name
         FROM ai_word_presentations
         INNER JOIN classes ON classes.id = ai_word_presentations.class_id
         LEFT JOIN materials ON materials.id = ai_word_presentations.material_id
         LEFT JOIN material_files ON material_files.id = ai_word_presentations.pptx_file_id
         WHERE ai_word_presentations.id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => $id]);

    return $stmt->fetch();
}

function getPublishedAiWordPresentationsByMaterialIds(array $materialIds): array
{
    $materialIds = array_values(array_unique(array_filter(array_map('intval', $materialIds))));

    if (empty($materialIds)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($materialIds), '?'));
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT ai_word_presentations.*
         FROM ai_word_presentations
         WHERE status = "published"
           AND material_id IN (' . $placeholders . ')'
    );
    $stmt->execute($materialIds);

    $presentationsByMaterial = [];
    foreach ($stmt->fetchAll() as $presentation) {
        $presentationsByMaterial[(int)$presentation['material_id']] = $presentation;
    }

    return $presentationsByMaterial;
}

function createAiWordPresentation(array $data): int
{
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO ai_word_presentations
            (material_id, class_id, title, source_words, cards_json, pptx_file_id, status, model, response_id)
         VALUES
            (:material_id, :class_id, :title, :source_words, :cards_json, :pptx_file_id, :status, :model, :response_id)'
    );
    $stmt->execute(aiWordPresentationParams($data));

    return (int)$db->lastInsertId();
}

function updateAiWordPresentation(int $id, array $data): void
{
    $db = getDB();
    $params = aiWordPresentationParams($data);
    $params[':id'] = $id;

    $stmt = $db->prepare(
        'UPDATE ai_word_presentations
         SET material_id = :material_id,
             class_id = :class_id,
             title = :title,
             source_words = :source_words,
             cards_json = :cards_json,
             pptx_file_id = :pptx_file_id,
             status = :status,
             model = :model,
             response_id = :response_id,
             updated_at = datetime("now")
         WHERE id = :id'
    );
    $stmt->execute($params);
}

function updateAiWordPresentationCards(int $id, array $cards, string $status, string $model = '', string $responseId = ''): void
{
    $db = getDB();
    $stmt = $db->prepare(
        'UPDATE ai_word_presentations
         SET cards_json = :cards_json,
             status = :status,
             model = :model,
             response_id = :response_id,
             updated_at = datetime("now")
         WHERE id = :id'
    );
    $stmt->execute([
        ':id' => $id,
        ':cards_json' => encodeAiWordPresentationCards($cards),
        ':status' => normalizeAiWordPresentationStatus($status),
        ':model' => trim($model),
        ':response_id' => trim($responseId),
    ]);
}

function publishAiWordPresentation(int $id, int $materialId, int $pptxFileId): void
{
    $db = getDB();
    $stmt = $db->prepare(
        'UPDATE ai_word_presentations
         SET material_id = :material_id,
             pptx_file_id = :pptx_file_id,
             status = "published",
             updated_at = datetime("now")
         WHERE id = :id'
    );
    $stmt->execute([
        ':id' => $id,
        ':material_id' => $materialId,
        ':pptx_file_id' => $pptxFileId,
    ]);
}

function deleteAiWordPresentation(int $id): void
{
    $db = getDB();
    $stmt = $db->prepare('DELETE FROM ai_word_presentations WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

function aiWordPresentationParams(array $data): array
{
    return [
        ':material_id' => !empty($data['material_id']) ? (int)$data['material_id'] : null,
        ':class_id' => (int)$data['class_id'],
        ':title' => trim((string)$data['title']),
        ':source_words' => encodeAiWordPresentationWords($data['source_words'] ?? []),
        ':cards_json' => encodeAiWordPresentationCards($data['cards_json'] ?? []),
        ':pptx_file_id' => !empty($data['pptx_file_id']) ? (int)$data['pptx_file_id'] : null,
        ':status' => normalizeAiWordPresentationStatus((string)($data['status'] ?? 'draft')),
        ':model' => trim((string)($data['model'] ?? '')),
        ':response_id' => trim((string)($data['response_id'] ?? '')),
    ];
}

function aiWordPresentationSourceWords(array $presentation): array
{
    $words = json_decode((string)($presentation['source_words'] ?? '[]'), true);

    if (!is_array($words)) {
        return [];
    }

    return array_values(array_filter(
        array_map(static fn($word): string => trim((string)$word), $words),
        static fn(string $word): bool => $word !== ''
    ));
}

function aiWordPresentationSourceWordsText(array $presentation): string
{
    return implode("\n", aiWordPresentationSourceWords($presentation));
}

function aiWordPresentationWordsCount(array $presentation): int
{
    return count(aiWordPresentationSourceWords($presentation));
}

function aiWordPresentationCards(array $presentation): array
{
    $cards = json_decode((string)($presentation['cards_json'] ?? '[]'), true);

    if (!is_array($cards)) {
        return [];
    }

    return normalizeAiWordPresentationCards($cards);
}

function aiWordPresentationStatusLabel(string $status): string
{
    return match ($status) {
        'ready' => 'Готово',
        'published' => 'Опубликовано',
        default => 'Черновик',
    };
}

function normalizeAiWordPresentationStatus(string $status): string
{
    return in_array($status, ['draft', 'ready', 'published'], true) ? $status : 'draft';
}

function encodeAiWordPresentationWords(array|string $words): string
{
    $normalizedWords = is_array($words) ? $words : normalizeAiWordPresentationWordsForStorage($words);
    $json = json_encode(array_values($normalizedWords), JSON_UNESCAPED_UNICODE);

    return $json !== false ? $json : '[]';
}

function normalizeAiWordPresentationWordsForStorage(string $rawWords): array
{
    $items = preg_split('/[\r\n,;]+/u', $rawWords) ?: [];
    $words = [];

    foreach ($items as $item) {
        $word = trim((string)$item);

        if ($word !== '') {
            $words[] = $word;
        }
    }

    return $words;
}

function encodeAiWordPresentationCards(array|string $cards): string
{
    if (is_string($cards)) {
        $decoded = json_decode($cards, true);
        $cards = is_array($decoded) ? $decoded : [];
    }

    $json = json_encode(normalizeAiWordPresentationCards($cards), JSON_UNESCAPED_UNICODE);

    return $json !== false ? $json : '[]';
}

function normalizeAiWordPresentationCards(array $cards): array
{
    $normalizedCards = [];

    foreach ($cards as $card) {
        if (!is_array($card)) {
            continue;
        }

        $sourceWord = normalizeAiWordPresentationCardText((string)($card['source_word'] ?? ''));
        $englishWord = normalizeAiWordPresentationCardText((string)($card['english_word'] ?? ''));
        $transcription = normalizeAiWordPresentationCardText((string)($card['transcription'] ?? ''));
        $hint = normalizeAiWordPresentationCardText((string)($card['hint'] ?? ''));
        $imagePrompt = normalizeAiWordPresentationCardText((string)($card['image_prompt'] ?? ''));
        $imagePath = normalizeAiWordPresentationCardText((string)($card['image_path'] ?? ''));
        $imageMime = normalizeAiWordPresentationCardText((string)($card['image_mime'] ?? ''));

        if ($sourceWord === '' && $englishWord === '') {
            continue;
        }

        if ($englishWord === '') {
            $englishWord = $sourceWord;
        }

        if ($sourceWord === '') {
            $sourceWord = $englishWord;
        }

        $normalizedCards[] = [
            'source_word' => $sourceWord,
            'english_word' => $englishWord,
            'transcription' => $transcription,
            'hint' => $hint,
            'example_sentence' => normalizeAiWordPresentationCardText((string)($card['example_sentence'] ?? '')),
            'quiz_sentence' => normalizeAiWordPresentationCardText((string)($card['quiz_sentence'] ?? $card['example_sentence'] ?? '')),
            'image_prompt' => $imagePrompt,
            'image_path' => $imagePath,
            'image_mime' => $imageMime,
        ];

        if (count($normalizedCards) >= 20) {
            break;
        }
    }

    return $normalizedCards;
}

function normalizeAiWordPresentationCardText(string $value): string
{
    $value = trim($value);
    $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

    return $value;
}
