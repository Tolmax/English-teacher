<?php

require_once ROOT . 'app/services/ai-knowledge-context.php';

function buildAiTestLessonContext(?array $source, string $lesson): array
{
    if ($source === null) {
        if ($lesson !== '') {
            throw new RuntimeException('Для урока выберите учебник из базы знаний.');
        }
        return [];
    }
    if (($source['status'] ?? '') !== 'active' || !empty($source['archived_at'])) {
        throw new RuntimeException('Источник недоступен. Выберите активный учебник.');
    }
    $text = aiKnowledgeContextPlainText((string)($source['extracted_text'] ?? ''));
    if ($text === '') {
        throw new RuntimeException('В учебнике нет текста для ИИ. Заполните «Текст источника» в базе знаний: сам файл PDF или скан автоматически не читается.');
    }
    if ($lesson === '') {
        throw new RuntimeException('Укажите номер или название урока из выбранного учебника.');
    }

    $fragment = selectAiTestLessonText($text, (string)$source['title'], $lesson);
    if (aiKnowledgeContextLength($fragment) > 5500) {
        throw new RuntimeException('Раздел слишком большой для одного теста. Сохраните нужный урок или его часть отдельным источником (до 5500 символов) и выберите его.');
    }
    if (aiKnowledgeContextLength($fragment) < 40) {
        throw new RuntimeException('Найден только заголовок или слишком короткий фрагмент. Добавьте полный текст урока, а не только оглавление.');
    }

    return [
        'text' => 'Учебник: ' . limitAiKnowledgeContextText(aiKnowledgeContextPlainText((string)$source['title']), 255)
            . "\nУрок: " . $lesson . "\nУчебный материал:\n" . $fragment,
        'lesson_reference' => $lesson,
        'source_count' => 1,
        'sources' => [['id' => (int)$source['id'], 'title' => (string)$source['title']]],
    ];
}

function aiTestLessonHeadingMatches(string $heading, string $lesson): bool
{
    $heading = trim($heading, " \t\r\n#");
    $lesson = trim($lesson);
    $marker = '(?:lesson|unit|module|урок|раздел|глава)';
    // A bare number matches a numbered heading, not a page number or Unit 30.
    if (preg_match('/^\d+[a-zа-я]?$/iu', $lesson)) {
        return preg_match('/(?:^|[^\p{L}\p{N}])' . $marker . '\s*№?\s*0*' . preg_quote($lesson, '/') . '(?![\p{L}\p{N}]|\.\d)/iu', $heading) === 1;
    }
    if (preg_match('/^' . $marker . '\s*№?\s*\d+[a-zа-я]?$/iu', $lesson)) {
        $pattern = preg_replace('/\s+/u', '\\s*', preg_quote($lesson, '/'));
        return preg_match('/(?:^|[^\p{L}\p{N}])' . $pattern . '(?![\p{L}\p{N}]|\.\d)/iu', $heading) === 1;
    }
    // A title may be entered alone or together with its numbered heading.
    $withoutNumber = preg_replace('/^' . $marker . '\s*№?\s*\d+[a-zа-я]?\s*(?:[.:—–-]\s*)?/iu', '', $heading);
    return preg_match('/^' . preg_quote($lesson, '/') . '$/iu', $heading) === 1
        || preg_match('/^' . preg_quote($lesson, '/') . '$/iu', trim((string)$withoutNumber)) === 1;
}

function selectAiTestLessonText(string $text, string $title, string $lesson): string
{
    $lines = preg_split('/\R/u', $text) ?: [];
    $matches = [];
    foreach ($lines as $index => $line) {
        if (aiKnowledgeContextLength($line) <= 180 && aiTestLessonHeadingMatches($line, $lesson)) {
            $matches[] = $index;
        }
    }
    if (count($matches) > 1) {
        throw new RuntimeException('Урок встречается несколько раз (возможно, в оглавлении). Уточните название или сохраните нужный урок отдельным источником.');
    }
    if ($matches === []) {
        if (aiTestLessonHeadingMatches($title, $lesson)) {
            return $text;
        }
        throw new RuntimeException('Урок не найден в тексте выбранного учебника. Проверьте номер/название и заголовки в «Тексте источника».');
    }
    $start = $matches[0];
    $end = count($lines);
    for ($index = $start + 1; $index < count($lines); $index++) {
        if (preg_match('/^\s*#*\s*(?:lesson|unit|module|урок|раздел|глава)\s*№?\s*\d+/iu', $lines[$index])) {
            $end = $index;
            break;
        }
    }
    $body = trim(implode("\n", array_slice($lines, $start + 1, $end - $start - 1)));
    if (aiKnowledgeContextLength($body) < 40) {
        throw new RuntimeException('Найден заголовок без достаточного текста урока. Добавьте содержание, а не только оглавление.');
    }
    return trim($lines[$start] . "\n" . $body);
}
