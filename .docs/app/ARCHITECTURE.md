# Архитектурные правила PHP-проекта

Этот документ описывает соглашения и правила построения проекта на основе эталонной реализации (var 1). Используй его как руководство при рефакторинге существующего кода и при создании новых проектов.

---

## Структура директорий

```
app/
├── core/           # Ядро фреймворка: роутер, БД, авторизация, рендерер, хелперы
├── models/         # Модели — один файл на сущность, только запросы к БД
├── validators/     # Валидаторы — один файл на сущность
├── services/       # Сервисы — переиспользуемая логика (загрузка файлов, email, платежи)
├── helpers/        # Вспомогательные утилиты (логирование и пр.)
└── controllers/    # Контроллеры — один файл на одно действие
    ├── admin/
    │   ├── index.php              # Под-роутер раздела /admin
    │   ├── dashboard.php
    │   ├── products/
    │   │   ├── index.php          # Под-роутер /admin/products
    │   │   ├── add.php
    │   │   ├── edit.php
    │   │   └── delete.php
    │   ├── orders/
    │   │   ├── index.php
    │   │   ├── show.php
    │   │   └── delete.php
    │   └── users/
    │       ├── index.php
    │       └── show.php
    ├── auth/
    │   ├── login.php
    │   └── logout.php
    ├── catalog/
    │   ├── index.php
    │   └── items.php
    ├── cart/
    │   ├── index.php
    │   ├── add.php
    │   ├── update.php
    │   └── remove.php
    └── order/
        ├── index.php
        ├── show.php
        ├── thankyou.php
        └── checkout/
            ├── form.php
            ├── place.php
            └── ...
```

---

## Правило 1: один контроллер — одно действие — один файл

Каждое действие (список, добавление, редактирование, удаление) живёт в **отдельном файле**. Файл называется по действию.

```
# Правильно
app/controllers/admin/products/add.php     ← только логика добавления
app/controllers/admin/products/edit.php    ← только логика редактирования
app/controllers/admin/products/delete.php  ← только логика удаления
app/controllers/admin/products/index.php   ← только диспетчеризация

# Неправильно
app/controllers/admin/products.php         ← все четыре действия в одном файле
```

Если файл контроллера превышает ~80 строк основной логики — это сигнал, что он делает слишком много.

---

## Правило 2: роутинг строится из вложенных диспетчеров

Роутинг — это цепочка `index.php`-файлов, каждый из которых отвечает только за свой уровень URL.

**Главный роутер** (`app/core/router.php`) парсит URL в массив `$segments`:

```php
// URL: /admin/products/42/edit
// $segments = ['admin', 'products', '42', 'edit']
```

**Диспетчер первого уровня** — например, `app/controllers/admin/index.php` — читает `$segments[1]` и подключает нужный под-роутер:

```php
$action = $segments[1] ?? '';

if ($action === 'login') {
    require ROOT . 'app/controllers/auth/login.php';
    return;
}

requireAuth(); // всё ниже требует авторизации

if ($action === 'products') {
    require ROOT . 'app/controllers/admin/products/index.php';
    return;
}

if ($action === 'orders') {
    require ROOT . 'app/controllers/admin/orders/index.php';
    return;
}

http_response_code(404);
require ROOT . 'app/controllers/not-found.php';
```

**Диспетчер второго уровня** — `app/controllers/admin/products/index.php` — читает `$segments[2]` и подключает файл действия:

```php
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

// По умолчанию — список
$products = getAllProducts(limit: 100);
renderTemplate('pages/admin/products.tpl', ['products' => $products]);
```

### Правило именования URL

| Действие       | URL                          | Метод |
|----------------|------------------------------|-------|
| Список         | `/admin/products`            | GET   |
| Форма создания | `/admin/products/add`        | GET   |
| Сохранить      | `/admin/products/add`        | POST  |
| Форма редакт.  | `/admin/products/42/edit`    | GET   |
| Обновить       | `/admin/products/42/edit`    | POST  |
| Удалить        | `/admin/products/42/delete`  | POST  |

Удаление и изменение данных — **только POST**. Проверяй это через `isPost()`.

---

## Правило 3: валидаторы — в отдельной папке

Логика валидации **не живёт в контроллере**. Она выносится в `app/validators/`.

**Именование файла:** `{Сущность}Validator.php`  
**Именование функций:** `validate{Что}(array $data): array`

```
app/validators/
├── ProductValidator.php   → validateProductData(), validateUploadedImages()
├── AuthValidator.php      → validateLoginData()
├── UserValidator.php      → validateUserData()
└── OrderValidator.php     → validateOrderData()
```

Функция-валидатор **всегда возвращает массив ошибок** (пустой — если всё ок):

```php
// app/validators/ProductValidator.php

function validateProductData(array $post): array
{
    $errors = [];

    if (empty(trim($post['title'] ?? ''))) {
        $errors['title'] = 'Введите название товара';
    }

    if (!is_numeric($post['price'] ?? '') || (int)$post['price'] < 0) {
        $errors['price'] = 'Введите корректную цену';
    }

    return $errors;
}
```

В контроллере валидатор подключается через `require` и вызывается напрямую:

```php
// app/controllers/admin/products/add.php

require ROOT . 'app/validators/ProductValidator.php';

$errors = validateProductData($_POST);

if (empty($errors)) {
    // сохраняем...
}
```

---

## Правило 4: модели — только запросы к БД

Файл модели содержит **только функции, работающие с одной таблицей**. Никакой валидации, никакой бизнес-логики.

**Именование файла:** `{Сущность}Model.php`  
**Именование функций:** глагол + сущность (`getProductById`, `createProduct`, `updateProduct`, `deleteProduct`)

```php
// app/models/ProductModel.php

function getAllProducts(int $limit = 12, int $offset = 0): array { ... }
function countProducts(): int { ... }
function getProductById(int $id): array|false { ... }
function createProduct(array $data): int { ... }   // возвращает ID новой записи
function updateProduct(int $id, array $data): void { ... }
function deleteProduct(int $id): void { ... }
```

Все запросы — только через PDO с именованными параметрами (`:param`), никаких интерполяций строк.

```php
// Правильно
$stmt = $db->prepare('SELECT * FROM products WHERE id = :id');
$stmt->execute([':id' => $id]);

// Неправильно — SQL-инъекция
$db->query("SELECT * FROM products WHERE id = $id");
```

---

## Правило 5: сервисы — переиспользуемая инфраструктурная логика

Сервисы — это код, который не относится ни к БД, ни к валидации, но переиспользуется в нескольких контроллерах.

```
app/services/
├── UploadService.php     # загрузка, обработка и удаление файлов
├── EmailService.php      # отправка email
├── OtpService.php        # генерация и проверка OTP
└── YookassaService.php   # интеграция с платёжным шлюзом
```

Сервис подключается в контроллере через `require` и его функции вызываются напрямую.

---

## Правило 6: паттерн POST → Redirect → GET

После любого успешного изменения данных — **всегда делай редирект**. Это предотвращает повторную отправку формы при обновлении страницы.

```php
if (isPost()) {
    $errors = validateProductData($_POST);

    if (empty($errors)) {
        createProduct([...]);
        setFlash('success', 'Товар добавлен');
        redirectTo('admin/products'); // ← обязательно после успеха
    }
}

// Если ошибки есть — рендерим форму снова с $errors
renderTemplate('pages/admin/product-add.tpl', [
    'errors' => $errors,
]);
```

---

## Правило 7: глобальные хелперы в `app/core/helpers.php`

Набор коротких утилит, доступных везде:

```php
// Защита от XSS — используй для ЛЮБЫХ данных из БД или пользователя в шаблоне
e(string $value): string
// Пример в шаблоне: <?= e($product['title']) ?>

// Редирект с завершением скрипта
redirectTo(string $path): never

// Проверка метода запроса
isPost(): bool
isGet(): bool

// Flash-сообщения (одноразовые, через сессию)
setFlash(string $key, string $message): void
getFlash(string $key): ?string

// Завершение с 404
abort404(): never

// Проверка, что запись найдена (иначе 404)
requireFound(mixed $value): void

// Проверка числового ID в URL (иначе 404)
requireNumericId(mixed $value): int
```

---

## Правило 8: подключение файлов через ROOT-константу

Все `require` используют константу `ROOT`, которая указывает на корень проекта:

```php
// Правильно
require ROOT . 'app/models/ProductModel.php';
require ROOT . 'app/validators/ProductValidator.php';

// Неправильно
require __DIR__ . '/../../models/ProductModel.php';
require 'app/models/ProductModel.php';
```

---

## Правило 9: шаблоны — через renderTemplate()

Контроллер не выводит HTML напрямую. Он передаёт данные в шаблон:

```php
renderTemplate('pages/admin/products.tpl', [
    'products' => $products,
    'flash'    => getFlash('success'),
]);
```

В шаблоне все переменные из пользовательских данных экранируются через `e()`:

```php
<h1><?= e($product['title']) ?></h1>
```

---

## Типичный контроллер создания ресурса

```php
<?php
// app/controllers/admin/products/add.php

require ROOT . 'app/validators/ProductValidator.php';
require ROOT . 'app/services/UploadService.php';

$errors  = [];
$oldData = [];

if (isPost()) {
    $oldData = [
        'title' => trim($_POST['title'] ?? ''),
        'price' => $_POST['price'] ?? '',
    ];

    $errors = validateProductData($_POST);

    if (empty($errors)) {
        $productId = createProduct($oldData);
        setFlash('success', 'Товар добавлен');
        redirectTo('admin/products');
    }
}

renderTemplate('pages/admin/product-add.tpl', [
    'errors'  => $errors,
    'oldData' => $oldData,
]);
```

---

## Типичный контроллер редактирования ресурса

```php
<?php
// app/controllers/admin/products/edit.php

require ROOT . 'app/validators/ProductValidator.php';

$product = getProductById($productId); // $productId задан в под-роутере
requireFound($product);

$errors = [];

if (isPost()) {
    $errors = validateProductData($_POST);

    if (empty($errors)) {
        updateProduct($productId, [
            'title' => trim($_POST['title'] ?? ''),
            'price' => (int)($_POST['price'] ?? 0),
        ]);
        setFlash('success', 'Товар обновлён');
        redirectTo('admin/products');
    }
}

renderTemplate('pages/admin/product-edit.tpl', [
    'product' => $product,
    'errors'  => $errors,
]);
```

---

## Типичный контроллер удаления ресурса

```php
<?php
// app/controllers/admin/products/delete.php

$product = getProductById($productId);
requireFound($product);

if (!isPost()) {
    redirectTo('admin/products');
}

deleteProduct($productId);
setFlash('success', 'Товар удалён');
redirectTo('admin/products');
```

---

## Чеклист при создании нового раздела

- [ ] Создать модель: `app/models/{Сущность}Model.php` с CRUD-функциями
- [ ] Создать валидатор: `app/validators/{Сущность}Validator.php`
- [ ] Создать папку контроллеров: `app/controllers/admin/{раздел}/`
- [ ] Создать `index.php` — под-роутер раздела
- [ ] Создать отдельные файлы для каждого действия: `add.php`, `edit.php`, `delete.php`
- [ ] Добавить маршрут в родительский `index.php`
- [ ] Все формы отправляются POST, все успешные операции завершаются `redirectTo()`
- [ ] Все данные из БД/POST в шаблонах проходят через `e()`
- [ ] Все SQL-запросы используют параметризованные запросы PDO

---

## Антипаттерны — чего не делать

| Антипаттерн | Правило |
|---|---|
| Валидация прямо в контроллере | Выноси в `app/validators/` |
| Один файл для list + create + edit + delete | Один файл — одно действие |
| SQL-запросы внутри контроллера | Только через функции модели |
| Интерполяция переменных в SQL | Только PDO с именованными параметрами |
| Вывод данных без `e()` | Всегда экранируй пользовательские данные |
| Удаление/изменение по GET-запросу | Мутирующие операции — только POST |
| `require` с относительным путём | Всегда через `ROOT . 'путь'` |
| Сервисная логика в модели или контроллере | Сервисы — в `app/services/` |
