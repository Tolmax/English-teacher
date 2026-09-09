<?php

function getAllProducts(): array
{
    $pdo  = getDB();
    $stmt = $pdo->query('SELECT * FROM products ORDER BY created_at DESC');
    return $stmt->fetchAll();
}

function getProductById(int $id): array|false
{
    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function createProduct(array $data): int
{
    $pdo  = getDB();
    $stmt = $pdo->prepare('
        INSERT INTO products (name, description, price, category, slug)
        VALUES (:name, :description, :price, :category, :slug)
    ');
    $stmt->execute([
        ':name'        => $data['name'],
        ':description' => $data['description'],
        ':price'       => $data['price'],
        ':category'    => $data['category'],
        ':slug'        => $data['slug'],
    ]);
    return (int)$pdo->lastInsertId();
}

function updateProduct(int $id, array $data): void
{
    $pdo  = getDB();
    $stmt = $pdo->prepare('
        UPDATE products
        SET name = :name, description = :description, price = :price,
            category = :category, slug = :slug
        WHERE id = :id
    ');
    $stmt->execute([
        ':name'        => $data['name'],
        ':description' => $data['description'],
        ':price'       => $data['price'],
        ':category'    => $data['category'],
        ':slug'        => $data['slug'],
        ':id'          => $id,
    ]);
}

function deleteProduct(int $id): void
{
    $pdo  = getDB();
    $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
    $stmt->execute([$id]);
}

function generateProductSlug(string $name): string
{
    $translit = [
        'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'yo',
        'ж'=>'zh','з'=>'z','и'=>'i','й'=>'j','к'=>'k','л'=>'l','м'=>'m',
        'н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u',
        'ф'=>'f','х'=>'kh','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'sch',
        'ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
    ];
    $slug = mb_strtolower($name);
    $slug = strtr($slug, $translit);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug ?: 'product';
}

function makeUniqueSlug(string $base, ?int $excludeId = null): string
{
    $pdo  = getDB();
    $slug = $base;
    $i    = 1;
    while (true) {
        if ($excludeId !== null) {
            $stmt = $pdo->prepare('SELECT id FROM products WHERE slug = ? AND id != ?');
            $stmt->execute([$slug, $excludeId]);
        } else {
            $stmt = $pdo->prepare('SELECT id FROM products WHERE slug = ?');
            $stmt->execute([$slug]);
        }
        if (!$stmt->fetch()) {
            break;
        }
        $slug = $base . '-' . $i;
        $i++;
    }
    return $slug;
}
