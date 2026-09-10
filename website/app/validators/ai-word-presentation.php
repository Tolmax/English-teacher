<?php

function validateAiWordPresentationData(array $data): array
{
    $errors = [];
    $title = trim((string)($data['title'] ?? ''));
    $words = normalizeAiWordPresentationWords($data['source_words_text'] ?? ($data['source_words'] ?? ''));

    if ((int)($data['class_id'] ?? 0) <= 0) {
        $errors['class_id'] = 'Выберите класс.';
    }

    if ($title === '') {
        $errors['title'] = 'Введите название презентации.';
    }

    if ($words === []) {
        $errors['source_words_text'] = 'Введите хотя бы одно слово или выражение на русском или английском.';
    }

    if (count($words) > 20) {
        $errors['source_words_text'] = 'Можно ввести не больше 20 слов или выражений.';
    }

    foreach ($words as $word) {
        if (mb_strlen($word) > 240 || preg_match('/^[A-Za-zА-Яа-яЁё][A-Za-zА-Яа-яЁё0-9\s\x{2019}\x{2018}\x{0027}(),.!?\/-]*$/u', $word) !== 1) {
            $errors['source_words_text'] = 'Введите слова или выражения на русском или английском языке, до 240 символов каждое.';
            break;
        }
    }

    return $errors;
}

function validateAiWordPresentationCardsData(array $cards): array
{
    $errors = [];

    foreach ($cards as $card) {
        if (!is_array($card)) {
            continue;
        }

        $englishWord = trim((string)($card['english_word'] ?? ''));
        $quiz = trim((string)($card['quiz_sentence'] ?? ''));
        if ($quiz !== '' && ($englishWord === '' || preg_match_all('/(?<![\p{L}\p{N}])' . preg_quote($englishWord, '/') . '(?![\p{L}\p{N}])/iu', $quiz) !== 1)) {
            $errors['cards'] = 'Предложение для теста должно содержать изучаемое выражение целиком ровно один раз.';
            break;
        }
        if ($englishWord === '') {
            $errors['cards'] = 'У каждой карточки должно быть английское слово.';
            break;
        }

        if (!isValidAiWordPresentationEnglishWord($englishWord)) {
            $errors['cards'] = 'В карточках английское слово должно быть написано латиницей.';
            break;
        }

        if (trim((string)($card['transcription'] ?? '')) === '') {
            $errors['cards'] = 'У каждой карточки должна быть транскрипция.';
            break;
        }

        if (trim((string)($card['hint'] ?? '')) === '') {
            $errors['cards'] = 'У каждой карточки должна быть короткая подсказка.';
            break;
        }

        if (trim((string)($card['translation_ru'] ?? '')) === '') {
            $errors['cards'] = 'У каждой карточки должен быть перевод на русский язык для обратной стороны колоды.';
            break;
        }

        if (trim((string)($card['image_prompt'] ?? '')) === '') {
            $errors['cards'] = 'У каждой карточки должно быть описание картинки.';
            break;
        }
    }

    return $errors;
}

function normalizeAiWordPresentationWords(array|string $rawWords): array
{
    if (is_array($rawWords)) {
        $items = $rawWords;
    } else {
        $items = preg_split('/[\r\n,;]+/u', (string)$rawWords) ?: [];
    }

    $words = [];
    $seen = [];

    foreach ($items as $item) {
        $word = trim((string)$item);
        $word = preg_replace('/\s+/u', ' ', $word) ?? $word;

        if ($word === '') {
            continue;
        }

        $key = function_exists('mb_strtolower') ? mb_strtolower($word) : strtolower($word);
        if (isset($seen[$key])) {
            continue;
        }

        $seen[$key] = true;
        $words[] = $word;
    }

    return $words;
}

function isValidAiWordPresentationEnglishWord(string $word): bool
{
    return strlen($word) <= 240 && preg_match('/^[A-Za-z][A-Za-z0-9\s\x{2019}\x{2018}\x{0027}(),.!?\/-]*$/u', trim($word)) === 1;
}
