<?php

function getDefaultAiTestTemplateKey(): string
{
    return 'quick_check';
}

function getAiTestTemplates(): array
{
    return [
        'quick_check' => [
            'key' => 'quick_check',
            'title' => 'Быстрая проверка',
            'description' => 'Короткий тест по одной теме для проверки понимания после урока.',
            'rules' => '10 вопросов по выбранной теме. Каждый вопрос имеет два варианта ответа и один правильный ответ.',
        ],
        'vocabulary' => [
            'key' => 'vocabulary',
            'title' => 'Словарный тест',
            'description' => 'Проверяет знание слов, переводов, значений и простых примеров употребления.',
            'rules' => '10 вопросов по лексике. Используй слова по теме, простые контексты и два варианта ответа.',
        ],
        'grammar_gap' => [
            'key' => 'grammar_gap',
            'title' => 'Грамматика с пропусками',
            'description' => 'Тренирует выбор правильной грамматической формы в коротких предложениях.',
            'rules' => '10 предложений с пропуском. Два варианта ответа должны отличаться грамматической формой.',
        ],
        'reading_true_false' => [
            'key' => 'reading_true_false',
            'title' => 'Чтение: верно или нет',
            'description' => 'Проверяет понимание короткого текста через утверждения true/false.',
            'rules' => 'Сначала используй короткий текст по теме, затем 10 утверждений с двумя вариантами: True и False.',
        ],
        'mixed' => [
            'key' => 'mixed',
            'title' => 'Смешанный тест',
            'description' => 'Комбинирует лексику, грамматику и понимание коротких фраз по теме.',
            'rules' => '10 вопросов: часть по словам, часть по грамматике, часть по смыслу коротких предложений. Везде два варианта ответа.',
        ],
    ];
}

function getAiTestTemplate(string $key): array
{
    $templates = getAiTestTemplates();
    $normalizedKey = normalizeAiTestTemplateKey($key);

    return $templates[$normalizedKey];
}

function normalizeAiTestTemplateKey(?string $key): string
{
    $key = trim((string)$key);
    $templates = getAiTestTemplates();

    if ($key !== '' && isset($templates[$key])) {
        return $key;
    }

    return getDefaultAiTestTemplateKey();
}

function aiTestTemplateLabel(?string $key): string
{
    $template = getAiTestTemplate((string)$key);

    return $template['title'];
}
