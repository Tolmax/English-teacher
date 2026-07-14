# Controllers Documentation

## Обзор

Контроллеры — это **обычные PHP-файлы**, не классы. Каждый файл исполняется через `require` из роутера или родительского контроллера. Контроллер получает данные из модели, обрабатывает запрос и либо рендерит шаблон, либо делает редирект.

**Ключевые принципы:**

- Нет классов, нет OOP — только процедурный код
- Один файл = одно действие (кроме роутер-контроллеров)
- Контроллер не пишет SQL — только вызывает функции моделей
- Всегда заканчивается либо `renderTemplate()`, либо `redirectTo()`, либо `exit`
- POST-действия всегда заканчиваются редиректом (PRG-паттерн)

---

## Структура директории

```
app/controllers/
│
├── home.php                    ← простая страница
├── not-found.php               ← 404
├── logout.php                  ← простое действие
│
├── catalog/
│   ├── index.php               ← роутер + основная страница
│   └── items.php               ← JSON-эндпоинт
│
├── product/
│   └── index.php               ← страница товара
│
├── cart/
│   ├── index.php               ← роутер + страница корзины
│   ├── add.php                 ← POST-действие
│   ├── update.php              ← POST-действие
│   └── remove.php              ← POST-действие
│
├── account/
│   ├── index.php               ← роутер + страница аккаунта
│   ├── edit.php                ← форма редактирования (GET+POST)
│   ├── orders.php              ← список заказов
│   ├── order-show.php          ← просмотр заказа
│   └── order-delete.php        ← POST-действие
│
├── order/
│   ├── index.php               ← роутер
│   ├── show.php
│   ├── thankyou.php
│   ├── checkout.php
│   └── checkout/               ← шаги многошагового процесса
│       ├── form.php
│       ├── place.php
│       └── ...
│
├── auth/
│   ├── login.php               ← admin login (GET+POST)
│   └── logout.php
│
└── admin/
    ├── index.php               ← роутер (с auth-проверкой)
    ├── dashboard.php
    ├── products/
    │   ├── index.php           ← роутер + список
    │   ├── add.php             ← форма добавления (GET+POST)
    │   ├── edit.php            ← форма редактирования (GET+POST)
    │   └── delete.php          ← POST-действие
    ├── orders/
    └── users/
```

---

## Типы контроллеров

### 1. Простая GET-страница

Только загружает данные и рендерит шаблон. Нет обработки POST.

```php
<?php

require ROOT . 'app/models/ProductModel.php';

$featuredProducts = getAllProducts(limit: 4);

renderTemplate('pages/home.tpl', [
    'products' => $featuredProducts,
]);
```

Более сложный вариант — с ID из URL и проверкой существования:

```php
<?php

require ROOT . 'app/models/ProductModel.php';
require ROOT . 'app/models/ProductImageModel.php';

$id      = requireNumericId($segments[1] ?? '');
$product = getProductById($id);
requireFound($product);

$images = getImagesByProductId($id);
$sizes  = json_decode($product['sizes'] ?? '[]', true) ?: [];

renderTemplate('pages/product.tpl', [
    'product' => $product,
    'images'  => $images,
    'sizes'   => $sizes,
]);
```

**Порядок:**
1. `require` моделей/сервисов
2. Извлечь и провалидировать ID из `$segments`
3. Загрузить данные из модели
4. Проверить существование (`requireFound`)
5. `renderTemplate()`

---

### 2. POST-действие (без формы)

Принимает POST, обрабатывает, делает редирект. Нет рендера шаблона.

```php
<?php

require ROOT . 'app/models/ProductModel.php';

if (!isPost()) {
    redirectTo('catalog');
}

$productId = (int)($_POST['product_id'] ?? 0);
if ($productId <= 0) {
    redirectTo('catalog');
}

$product = getProductById($productId);
if (!$product) {
    redirectTo('catalog');
}

$size     = mb_strtoupper(trim($_POST['size'] ?? ''));
$quantity = max(1, (int)($_POST['quantity'] ?? 1));

addToCart(
    $productId,
    $product['title'],
    (int)$product['price'],
    $product['cover_image'],
    $quantity,
    $size
);

redirectTo('cart');
```

**Порядок:**
1. Проверить `isPost()` — если нет, редирект
2. Извлечь и провалидировать входные данные
3. Вызвать модель
4. `redirectTo()` — **всегда**, никогда не рендерить после POST

---

### 3. Форма (GET + POST)

На GET показывает форму, на POST — валидирует и обрабатывает. При ошибках перерендеривает форму с данными.

```php
<?php

require ROOT . 'app/models/UserModel.php';
require ROOT . 'app/validators/UserValidator.php';

$userId = getCurrentUserId();
$user   = getUserById($userId);

if (!$user) {
    unset($_SESSION['user_id']);
    redirectTo('login');
}

$errors = [];
$old    = [
    'name'    => $user['name'],     // на GET — текущие данные
    'surname' => $user['surname'],
    'phone'   => $user['phone'],
    'address' => $user['address'],
];

if (isPost()) {
    $old = [
        'name'    => trim($_POST['name'] ?? ''),    // на POST — данные из формы
        'surname' => trim($_POST['surname'] ?? ''),
        'phone'   => trim($_POST['phone'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
    ];

    $errors = validateProfileData($old);

    if (empty($errors)) {
        updateUser($userId, $old);
        redirectTo('account');
    }
}

renderTemplate('pages/account/edit.tpl', [
    'user'   => $user,
    'old'    => $old,
    'errors' => $errors,
]);
```

**Порядок:**
1. Загрузить текущие данные (для заполнения формы на GET)
2. Инициализировать `$errors = []` и `$old = [данные из БД]`
3. Если `isPost()`:
   - Перезаписать `$old` данными из `$_POST` (всегда trim)
   - Валидировать через валидатор
   - Если `empty($errors)` — сохранить и `redirectTo()`
4. `renderTemplate()` — выполняется при GET и при ошибках POST

**Ключевое:** `renderTemplate()` стоит вне блока `if (isPost())` — это позволяет рендерить форму и на GET, и при ошибках валидации одним кодом.

---

### 4. Форма с файлами (загрузка изображений)

Добавляется валидация файлов через `$_FILES`:

```php
<?php

require ROOT . 'app/validators/ProductValidator.php';
require ROOT . 'app/services/UploadService.php';

$errors  = [];
$oldData = [];

if (isPost()) {
    $oldData = [
        'title'    => trim($_POST['title'] ?? ''),
        'price'    => $_POST['price'] ?? '',
        'category' => trim($_POST['category'] ?? ''),
    ];

    $errors = validateProductData($_POST);

    $imageErrors = validateUploadedImages($_FILES);
    $errors      = array_merge($errors, $imageErrors);

    if (empty($errors)) {
        $productId  = createProduct([...$oldData]);
        $savedPaths = processProductImages($_FILES, $productId, 0);

        foreach ($savedPaths as $index => $path) {
            addProductImage($productId, $path, $index);
        }

        setFlash('success', 'Товар добавлен');
        redirectTo('admin/products');
    }
}

renderTemplate('pages/admin/product-add.tpl', [
    'errors'  => $errors,
    'oldData' => $oldData,
]);
```

Ошибки файлов объединяются с ошибками полей через `array_merge()`.

---

### 5. POST-действие удаления

```php
<?php

require ROOT . 'app/services/UploadService.php';

$product = getProductById($productId);
requireFound($product);

if (!isPost()) {
    redirectTo('admin/products');
}

$imagePaths = deleteAllProductImages($productId);
foreach ($imagePaths as $path) {
    deleteImageFiles($path);
}

deleteProduct($productId);

setFlash('success', 'Товар удалён');
redirectTo('admin/products');
```

`$productId` уже доступен из родительского роутера (`admin/products/index.php`).

---

### 6. Роутер-контроллер

Только диспетчеризация — никакой бизнес-логики. Всегда заканчивается `return` после `require`.

**Простой роутер:**

```php
<?php

require ROOT . 'app/models/CartModel.php';

$subAction = $segments[1] ?? '';

if ($subAction === 'add') {
    require ROOT . 'app/controllers/cart/add.php';
    return;
}

if ($subAction === 'update') {
    require ROOT . 'app/controllers/cart/update.php';
    return;
}

// Default: show cart page
$cart  = getCart();
$total = getCartTotal();

renderTemplate('pages/cart.tpl', [
    'cart'  => $cart,
    'total' => $total,
]);
```

**Роутер с auth-проверкой:**

```php
<?php

$action = $segments[1] ?? '';

// Публичные маршруты — без auth
if ($action === 'login') {
    require ROOT . 'app/controllers/auth/login.php';
    return;
}

// Всё остальное — только для авторизованных
requireAuth();

if ($action === '' || $action === 'dashboard') {
    require ROOT . 'app/controllers/admin/dashboard.php';
    return;
}

if ($action === 'products') {
    require ROOT . 'app/controllers/admin/products/index.php';
    return;
}

http_response_code(404);
require ROOT . 'app/controllers/not-found.php';
```

**Роутер с array-маппингом** (для большого числа sub-action):

```php
<?php

$checkoutAction  = $segments[2] ?? '';
$checkoutActions = [
    'form'        => 'checkout/form.php',
    'otp'         => 'checkout/otp.php',
    'place'       => 'checkout/place.php',
    'verify-otp'  => 'checkout/verify-otp.php',
];

if (isset($checkoutActions[$checkoutAction])) {
    require ROOT . 'app/controllers/order/' . $checkoutActions[$checkoutAction];
} else {
    require ROOT . 'app/controllers/order/checkout.php';
}
return;
```

**Роутер с numeric-сегментом:**

```php
<?php

$subAction = $segments[2] ?? '';

if ($subAction === 'add') {
    require ROOT . 'app/controllers/admin/products/add.php';
    return;
}

if (is_numeric($subAction)) {
    $productId = (int)$subAction;
    $action3   = $segments[3] ?? '';

    if ($action3 === 'edit') {
        require ROOT . 'app/controllers/admin/products/edit.php';
        return;
    }

    if ($action3 === 'delete') {
        require ROOT . 'app/controllers/admin/products/delete.php';
        return;
    }
}

// Default: list
$products = getAllProducts(limit: 100);
renderTemplate('pages/admin/products.tpl', ['products' => $products]);
```

---

### 7. JSON-эндпоинт

```php
<?php

// JSON endpoint for infinite scroll
header('Content-Type: application/json');

$perPage = 12;
$offset  = max(0, (int)($_GET['offset'] ?? 0));

$products = getAllProducts(limit: $perPage, offset: $offset);
$total    = countProducts();
$hasMore  = ($offset + count($products)) < $total;

$items = array_map(static function (array $product): array {
    return [
        'id'          => $product['id'],
        'title'       => $product['title'],
        'price'       => formatPrice((int)$product['price']),
        'cover_image' => $product['cover_image']
            ? HOST . 'uploads/' . $product['cover_image']
            : '',
        'url'         => HOST . 'product/' . $product['id'],
    ];
}, $products);

echo json_encode([
    'success' => true,
    'items'   => $items,
    'hasMore' => $hasMore,
    'total'   => $total,
]);
exit;
```

**Правила JSON-эндпоинта:**
- Первым делом — `header('Content-Type: application/json')`
- Данные формируются через `array_map()`, ключи — явные (не `SELECT *` напрямую)
- URL строятся через `HOST`, цены — через `formatPrice()`
- Заканчивается `exit`, не `return`

---

## Обработка входных данных

### $_POST

```php
// Строки — всегда trim()
$name = trim($_POST['name'] ?? '');

// Числа — всегда (int) + проверка > 0
$id       = (int)($_POST['product_id'] ?? 0);
$quantity = max(1, (int)($_POST['quantity'] ?? 1));

// Строки в uppercase
$size = mb_strtoupper(trim($_POST['size'] ?? ''));

// Опциональные поля с дефолтом
$delivery = $_POST['delivery'] ?? 'courier';
```

### $_GET

```php
// Пагинация
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = max(0, (int)($_GET['offset'] ?? 0));

// Строковые фильтры
$search = trim($_GET['q'] ?? '');
```

### $segments

```php
// Всегда с ?? — никогда прямой доступ без дефолта
$action    = $segments[1] ?? '';
$productId = requireNumericId($segments[2] ?? '');

// Numeric-проверка вручную
if (is_numeric($segments[2] ?? '') && (int)$segments[2] > 0) {
    $id = (int)$segments[2];
}
```

**Важное правило безопасности:** email и критические данные берутся из БД (`getUserById(getCurrentUserId())`), а не из `$_POST`, даже если пользователь мог их передать:

```php
// Правильно — email из БД
$user  = getUserById(getCurrentUserId());
$email = $user['email'];

// Неправильно — email из POST
$email = $_POST['email'];  // Никогда!
```

---

## Валидация

Логика валидации вынесена в отдельные файлы `app/validators/`:

```php
require ROOT . 'app/validators/ProductValidator.php';

$errors = validateProductData($_POST);
```

Валидатор возвращает ассоциативный массив `['field' => 'Сообщение об ошибке']`. Пустой массив = нет ошибок.

Несколько валидаторов объединяются через `array_merge()`:

```php
$errors = array_merge(
    validateProfileData($_POST),
    validateDeliveryData($_POST)
);
```

---

## Аутентификация и авторизация

```php
// Проверка и редирект без лишнего кода
requireUser();   // Редиректит на login если пользователь не авторизован
requireAuth();   // Редиректит если не авторизован admin

// Условный редирект
if (isAdmin()) {
    redirectTo('admin/dashboard');
}

if (isUser()) {
    redirectTo('account');
}
```

`requireUser()` и `requireAuth()` вызываются **в начале контроллера**, до загрузки любых данных.

В роутер-контроллерах `requireAuth()` ставится **после** публичных маршрутов:

```php
if ($action === 'login') {
    require ROOT . 'app/controllers/auth/login.php';
    return;
}

requireAuth();  // ← только здесь, публичный login уже обработан выше
```

---

## Flash-сообщения

Используются для передачи статуса через PRG-редирект:

```php
// В контроллере-обработчике (после успешной операции):
setFlash('success', 'Товар добавлен');
redirectTo('admin/products');

// В контроллере-списке (перед рендером):
$flash = getFlash('success');  // null если нет сообщения

renderTemplate('pages/admin/products.tpl', [
    'products' => $products,
    'flash'    => $flash,
]);
```

---

## Многошаговые формы через сессию

Когда данные нужно передать между шагами (через редиректы):

```php
// В шаге валидации — сохранить ошибки и данные в сессию
if (!empty($errors)) {
    $_SESSION['checkout_errors'] = $errors;
    $_SESSION['checkout_old']    = $formData;
    redirectTo('order/checkout/form');
}

// В шаге отображения формы — прочитать и очистить
$errors  = $_SESSION['checkout_errors'] ?? [];
$oldData = $_SESSION['checkout_old'] ?? [];
unset($_SESSION['checkout_errors'], $_SESSION['checkout_old']);
```

---

## require vs require_once

```php
require ROOT . 'app/models/ProductModel.php';      // обычный случай

require_once ROOT . 'app/models/UserModel.php';    // когда модель может быть
                                                   // подключена из нескольких
                                                   // sub-контроллеров в одном запросе
```

`require_once` используется в роутер-контроллерах, которые подключают модель до диспетчеризации — чтобы sub-контроллер не подключил её повторно.

---

## Правила: что запрещено в контроллерах

- SQL-запросы напрямую — только через функции моделей
- `htmlspecialchars()`, `e()` — это задача шаблона, не контроллера
- Логика рендера (HTML, CSS) — только в шаблонах
- Большие куски бизнес-логики — выносить в `app/services/`
- Рендер после POST без редиректа — нарушает PRG, вызывает повторную отправку при F5

---

## Шаблоны новых контроллеров

### Простая страница

```php
<?php

require ROOT . 'app/models/EntityModel.php';

$id     = requireNumericId($segments[1] ?? '');
$entity = getEntityById($id);
requireFound($entity);

renderTemplate('pages/entity.tpl', [
    'entity' => $entity,
]);
```

### Форма добавления

```php
<?php

require ROOT . 'app/models/EntityModel.php';
require ROOT . 'app/validators/EntityValidator.php';

$errors  = [];
$oldData = [];

if (isPost()) {
    $oldData = [
        'field1' => trim($_POST['field1'] ?? ''),
        'field2' => trim($_POST['field2'] ?? ''),
    ];

    $errors = validateEntityData($_POST);

    if (empty($errors)) {
        createEntity($oldData);
        setFlash('success', 'Запись добавлена');
        redirectTo('admin/entities');
    }
}

renderTemplate('pages/admin/entity-add.tpl', [
    'errors'  => $errors,
    'oldData' => $oldData,
]);
```

### Форма редактирования

```php
<?php

require ROOT . 'app/models/EntityModel.php';
require ROOT . 'app/validators/EntityValidator.php';

$id     = (int)($entityId ?? 0);  // $entityId установлен роутером
$entity = getEntityById($id);
requireFound($entity);

$errors = [];

if (isPost()) {
    $data = [
        'field1' => trim($_POST['field1'] ?? ''),
        'field2' => trim($_POST['field2'] ?? ''),
    ];

    $errors = validateEntityData($_POST);

    if (empty($errors)) {
        updateEntity($id, $data);
        setFlash('success', 'Запись обновлена');
        redirectTo('admin/entities');
    }
}

renderTemplate('pages/admin/entity-edit.tpl', [
    'entity' => $entity,
    'errors' => $errors,
]);
```

### POST-действие

```php
<?php

if (!isPost()) {
    redirectTo('somewhere');
}

$id = requireNumericId($segments[1] ?? '');
$entity = getEntityById($id);
requireFound($entity);

deleteEntity($id);

setFlash('success', 'Запись удалена');
redirectTo('admin/entities');
```

### JSON-эндпоинт

```php
<?php

header('Content-Type: application/json');

$limit  = min(50, max(1, (int)($_GET['limit'] ?? 12)));
$offset = max(0, (int)($_GET['offset'] ?? 0));

$items = getAllEntities(limit: $limit, offset: $offset);

$result = array_map(static function (array $item): array {
    return [
        'id'    => $item['id'],
        'title' => $item['title'],
        'url'   => HOST . 'entities/' . $item['id'],
    ];
}, $items);

echo json_encode(['success' => true, 'items' => $result]);
exit;
```
