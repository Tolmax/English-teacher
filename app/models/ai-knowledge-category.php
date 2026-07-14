<?php

function getAiKnowledgeCategories(): array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT *
         FROM ai_knowledge_categories
         ORDER BY sort_order ASC, title ASC, id ASC'
    );
    $stmt->execute();

    return $stmt->fetchAll();
}

function getAiKnowledgeCategoryById(int $id): array|false
{
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM ai_knowledge_categories WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    return $stmt->fetch();
}

function seedDefaultAiKnowledgeCategories(): void
{
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT OR IGNORE INTO ai_knowledge_categories (title, slug, description, sort_order)
         VALUES (:title, :slug, :description, :sort_order)'
    );

    $categories = [
        ['Vocabulary', 'vocabulary', 'Words, phrases and topic vocabulary.', 10],
        ['Grammar', 'grammar', 'Rules, examples and grammar drills.', 20],
        ['Speaking', 'speaking', 'Dialogues, questions and classroom speaking prompts.', 30],
        ['Reading', 'reading', 'Texts and reading comprehension tasks.', 40],
        ['Writing', 'writing', 'Writing plans, useful phrases and examples.', 50],
    ];

    foreach ($categories as $category) {
        $stmt->execute([
            ':title' => $category[0],
            ':slug' => $category[1],
            ':description' => $category[2],
            ':sort_order' => $category[3],
        ]);
    }
}
