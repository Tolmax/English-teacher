<?php

function getProductImages(int $productId): array
{
    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC');
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
}

function getProductCoverImage(int $productId): array|false
{
    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC LIMIT 1');
    $stmt->execute([$productId]);
    return $stmt->fetch();
}

function addProductImage(int $productId, string $filename, int $sortOrder = 0): void
{
    $pdo  = getDB();
    $stmt = $pdo->prepare('INSERT INTO product_images (product_id, filename, sort_order) VALUES (?, ?, ?)');
    $stmt->execute([$productId, $filename, $sortOrder]);
}

function deleteProductImage(int $id): void
{
    $pdo  = getDB();
    $stmt = $pdo->prepare('DELETE FROM product_images WHERE id = ?');
    $stmt->execute([$id]);
}

function deleteAllProductImages(int $productId): void
{
    $pdo  = getDB();
    $stmt = $pdo->prepare('DELETE FROM product_images WHERE product_id = ?');
    $stmt->execute([$productId]);
}
