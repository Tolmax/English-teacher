<?php

const AI_KNOWLEDGE_CONTEXT_SOURCE_LIMIT = 1800;
const AI_KNOWLEDGE_CONTEXT_TOTAL_LIMIT = 6500;

function buildAiKnowledgeContextForMaterial(int $materialId): array
{
    $sources = getActiveAiKnowledgeSourcesByMaterialId($materialId);
    $usedSources = [];
    $blocks = [];
    $totalLength = 0;

    foreach ($sources as $source) {
        $remaining = AI_KNOWLEDGE_CONTEXT_TOTAL_LIMIT - $totalLength;

        if ($remaining <= 0) {
            break;
        }

        $block = buildAiKnowledgeSourceContextBlock($source, min(AI_KNOWLEDGE_CONTEXT_SOURCE_LIMIT, $remaining));

        if ($block === '') {
            continue;
        }

        $blocks[] = $block;
        $totalLength += aiKnowledgeContextLength($block);
        $usedSources[] = [
            'id' => (int)$source['id'],
            'title' => (string)$source['title'],
            'source_type' => (string)$source['source_type'],
            'description' => (string)($source['description'] ?? ''),
            'tags' => (string)($source['tags'] ?? ''),
        ];
    }

    $contextText = limitAiKnowledgeContextText(implode("\n\n---\n\n", $blocks), AI_KNOWLEDGE_CONTEXT_TOTAL_LIMIT);

    return [
        'text' => $contextText,
        'sources' => $usedSources,
        'source_count' => count($usedSources),
    ];
}

function buildAiKnowledgeSourceContextBlock(array $source, int $maxLength): string
{
    $parts = [
        'Название: ' . aiKnowledgeContextPlainText((string)$source['title']),
        'Тип: ' . aiKnowledgeSourceTypeLabel((string)$source['source_type']),
    ];

    $description = aiKnowledgeContextPlainText((string)($source['description'] ?? ''));
    if ($description !== '') {
        $parts[] = 'Описание: ' . $description;
    }

    $tags = aiKnowledgeContextPlainText((string)($source['tags'] ?? ''));
    if ($tags !== '') {
        $parts[] = 'Теги: ' . $tags;
    }

    $text = aiKnowledgeContextPlainText((string)($source['extracted_text'] ?? ''));
    if ($text !== '') {
        $parts[] = "Фрагмент материала:\n" . $text;
    }

    return limitAiKnowledgeContextText(implode("\n", $parts), $maxLength);
}

function aiKnowledgeContextPlainText(string $text): string
{
    $text = strip_tags($text);
    $text = preg_replace('/sk-[A-Za-z0-9_\-]{20,}/', '[секрет удалён]', $text) ?? $text;
    $text = preg_replace('/OPENAI_API_KEY\s*=\s*\S+/i', 'OPENAI_API_KEY=[секрет удалён]', $text) ?? $text;
    $text = preg_replace('/Bearer\s+[A-Za-z0-9._\-]+/i', 'Bearer [секрет удалён]', $text) ?? $text;
    $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
    $text = preg_replace("/\R{3,}/", "\n\n", $text) ?? $text;

    return trim($text);
}

function limitAiKnowledgeContextText(string $text, int $maxLength): string
{
    $text = trim($text);

    if ($text === '') {
        return '';
    }

    if (aiKnowledgeContextLength($text) <= $maxLength) {
        return $text;
    }

    $shortText = function_exists('mb_substr') ? mb_substr($text, 0, $maxLength) : substr($text, 0, $maxLength);

    return trim($shortText) . "\n[Фрагмент источника сокращён до безопасного лимита.]";
}

function aiKnowledgeContextLength(string $text): int
{
    return function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
}
