<?php

// Определяем действие из URL-сегментов
// /admin/products          → list
// /admin/products/create   → create
// /admin/products/{id}/edit   → edit
// /admin/products/{id}/delete → delete

$rawAction = $segments[2] ?? 'list';
$productId = null;

if (is_numeric($rawAction)) {
    $productId = requireNumericId($rawAction);
    $rawAction = $segments[3] ?? 'edit';
}

// --- LIST ---
if ($rawAction === 'list') {
    $products = getAllProducts();
    renderTemplate('pages/admin/products/list.tpl', [
        'products' => $products,
    ]);
    return;
}

// --- CREATE ---
if ($rawAction === 'create') {
    if (isPost()) {
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price       = (int)($_POST['price'] ?? 0);
        $category    = trim($_POST['category'] ?? '');
        $errors      = [];

        if ($name === '') {
            $errors[] = 'Название обязательно';
        }
        if ($price <= 0) {
            $errors[] = 'Цена должна быть больше нуля';
        }

        if (empty($errors)) {
            $slugBase = generateProductSlug($name);
            $slug     = makeUniqueSlug($slugBase);

            $id = createProduct([
                'name'        => $name,
                'description' => $description,
                'price'       => $price,
                'category'    => $category,
                'slug'        => $slug,
            ]);

            // Сохраняем загруженные изображения
            if (!empty($_FILES['images']['name'][0])) {
                foreach ($_FILES['images']['name'] as $i => $originalName) {
                    if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) {
                        continue;
                    }
                    $file = [
                        'name'     => $originalName,
                        'tmp_name' => $_FILES['images']['tmp_name'][$i],
                        'error'    => $_FILES['images']['error'][$i],
                        'size'     => $_FILES['images']['size'][$i],
                        'type'     => $_FILES['images']['type'][$i],
                    ];
                    $validationError = validateUploadedImage($file);
                    if ($validationError === null) {
                        $filename = saveProductImage($file, $id);
                        if ($filename !== false) {
                            addProductImage($id, $filename, $i);
                        }
                    }
                }
            }

            setFlash('success', 'Товар «' . $name . '» добавлен');
            redirectTo('admin/products');
        }

        renderTemplate('pages/admin/products/form.tpl', [
            'product' => null,
            'images'  => [],
            'errors'  => $errors,
            'input'   => $_POST,
        ]);
        return;
    }

    renderTemplate('pages/admin/products/form.tpl', [
        'product' => null,
        'images'  => [],
        'errors'  => [],
        'input'   => [],
    ]);
    return;
}

// --- Для edit и delete нужен ID ---
if ($productId === null) {
    abort404();
}

$product = getProductById($productId);
requireFound($product);

// --- EDIT ---
if ($rawAction === 'edit') {
    if (isPost()) {
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price       = (int)($_POST['price'] ?? 0);
        $category    = trim($_POST['category'] ?? '');
        $errors      = [];

        if ($name === '') {
            $errors[] = 'Название обязательно';
        }
        if ($price <= 0) {
            $errors[] = 'Цена должна быть больше нуля';
        }

        if (empty($errors)) {
            $slugBase = generateProductSlug($name);
            $slug     = makeUniqueSlug($slugBase, $productId);

            updateProduct($productId, [
                'name'        => $name,
                'description' => $description,
                'price'       => $price,
                'category'    => $category,
                'slug'        => $slug,
            ]);

            // Добавляем новые изображения
            if (!empty($_FILES['images']['name'][0])) {
                $existingCount = count(getProductImages($productId));
                foreach ($_FILES['images']['name'] as $i => $originalName) {
                    if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) {
                        continue;
                    }
                    $file = [
                        'name'     => $originalName,
                        'tmp_name' => $_FILES['images']['tmp_name'][$i],
                        'error'    => $_FILES['images']['error'][$i],
                        'size'     => $_FILES['images']['size'][$i],
                        'type'     => $_FILES['images']['type'][$i],
                    ];
                    $validationError = validateUploadedImage($file);
                    if ($validationError === null) {
                        $filename = saveProductImage($file, $productId);
                        if ($filename !== false) {
                            addProductImage($productId, $filename, $existingCount + $i);
                        }
                    }
                }
            }

            setFlash('success', 'Товар «' . $name . '» обновлён');
            redirectTo('admin/products');
        }

        $images = getProductImages($productId);
        renderTemplate('pages/admin/products/form.tpl', [
            'product' => $product,
            'images'  => $images,
            'errors'  => $errors,
            'input'   => $_POST,
        ]);
        return;
    }

    $images = getProductImages($productId);
    renderTemplate('pages/admin/products/form.tpl', [
        'product' => $product,
        'images'  => $images,
        'errors'  => [],
        'input'   => [],
    ]);
    return;
}

// --- DELETE ---
if ($rawAction === 'delete' && isPost()) {
    $images = getProductImages($productId);
    foreach ($images as $image) {
        deleteProductImageFile($productId, $image['filename']);
    }
    deleteProduct($productId);
    setFlash('success', 'Товар удалён');
    redirectTo('admin/products');
}

abort404();
