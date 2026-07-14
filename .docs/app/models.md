# Models Documentation

## Обзор

Модели в проекте — это **обычные PHP-файлы с глобальными функциями**, без классов и ORM. Один файл = одна сущность. Каждая функция выполняет один конкретный SQL-запрос или операцию с сессией.

**Ключевые принципы:**

- Нет классов, нет OOP — только функции
- Один файл модели = одна таблица/сущность
- Все запросы через PDO с параметрами (никакой конкатенации SQL)
- Модели не содержат бизнес-логики, валидации и редиректов
- Модели не вызывают другие сервисы — только работают с хранилищем данных

---

## Подключение к БД: getDB()

**Файл:** `app/core/db.php`

```php
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dbPath = ROOT . 'database/database.sqlite';

    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');

    return $pdo;
}
```

- **Singleton через `static`** — соединение создаётся один раз за запрос
- **`FETCH_ASSOC`** — все результаты автоматически возвращаются как ассоциативные массивы
- **`ERRMODE_EXCEPTION`** — любая ошибка SQL бросает `PDOException`, которую перехватывает error-logger

В каждой функции модели вызов начинается с `$db = getDB();`.

---

## Структура файла модели

```
app/models/
├── ProductModel.php       ← таблица products
├── ProductImageModel.php  ← таблица product_images
├── UserModel.php          ← таблица users
├── OrderModel.php         ← таблица orders
├── OrderItemModel.php     ← таблица order_items
├── PaymentModel.php       ← таблица payments
├── OtpModel.php           ← таблица otp_codes
└── CartModel.php          ← сессия $_SESSION['cart'] (без БД)
```

Модели подключаются из контроллеров через `require`:

```php
require ROOT . 'app/models/ProductModel.php';
require ROOT . 'app/models/ProductImageModel.php';
```

---

## Правила именования функций

Имена функций — глагол + существительное, camelCase. Паттерн по типу операции:

| Операция | Паттерн | Пример |
|----------|---------|--------|
| Получить список | `getAll{Entity}()` | `getAllProducts()` |
| Получить по ID | `get{Entity}ById()` | `getProductById()` |
| Получить по полю | `get{Entity}By{Field}()` | `getUserByEmail()` |
| Получить связанные | `get{Entities}By{Parent}Id()` | `getImagesByProductId()` |
| Создать | `create{Entity}()` | `createProduct()` |
| Обновить всё | `update{Entity}()` | `updateProduct()` |
| Обновить поле | `update{Entity}{Field}()` | `updateProductCoverImage()` |
| Обновить статус | `update{Entity}Status()` | `updateOrderStatus()` |
| Удалить | `delete{Entity}()` | `deleteProduct()` |
| Посчитать | `count{Entities}()` | `countProducts()` |
| Специальное | произвольное | `attachUserToOrder()`, `hideOrder()` |

---

## Типы возвращаемых значений

| Ситуация | Тип | Когда |
|----------|-----|-------|
| Список записей | `array` | `fetchAll()` — всегда массив, пустой если ничего нет |
| Одна запись, может не существовать | `array\|false` | `fetch()` — false если не найдено |
| Одна запись, null вместо false | `?array` | `fetch()` + явная конвертация `?: null` |
| ID созданной записи | `int` | `(int)$db->lastInsertId()` |
| Нет результата | `void` | UPDATE / DELETE без возврата |
| Булевый результат | `bool` | проверки типа `verifyOtp()` |
| Путь к файлу | `string\|null` | delete с возвратом пути для удаления файла |

---

## Стили параметров запросов

В проекте используются оба стиля PDO — они **взаимозаменяемы**, но не смешиваются в одном запросе.

### Именованные параметры (`:name`)

Применяются в сложных запросах с несколькими параметрами — улучшает читаемость:

```php
function createProduct(array $data): int
{
    $db   = getDB();
    $stmt = $db->prepare('
        INSERT INTO products (title, price, category)
        VALUES (:title, :price, :category)
    ');
    $stmt->execute([
        ':title'    => $data['title'],
        ':price'    => (int)$data['price'],
        ':category' => $data['category'] ?? '',
    ]);
    return (int)$db->lastInsertId();
}
```

### Позиционные параметры (`?`)

Применяются в простых запросах с 1–3 параметрами:

```php
function getUserByEmail(string $email): ?array
{
    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    return $row ?: null;
}
```

---

## Канонические паттерны функций

### getAll — список записей

```php
function getAllProducts(int $limit = 12, int $offset = 0): array
{
    $db   = getDB();
    $stmt = $db->prepare('
        SELECT id, title, price, category, cover_image, created_at
        FROM products
        ORDER BY created_at DESC, id DESC
        LIMIT :limit OFFSET :offset
    ');
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
```

**Важно:** для `LIMIT` и `OFFSET` обязателен `bindValue()` с флагом `PDO::PARAM_INT` — SQLite иначе трактует их как строки и игнорирует.

### getById — одна запись по ID

```php
function getProductById(int $id): array|false
{
    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM products WHERE id = :id');
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}
```

Возвращает `array|false`. Контроллер проверяет результат через `requireFound()`.

### getBy{Field} — одна запись по произвольному полю

```php
function getUserByEmail(string $email): ?array
{
    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    return $row ?: null;  // явно возвращаем null вместо false
}
```

### create — создание записи

```php
function createOrder(array $data, ?int $userId = null): int
{
    $db   = getDB();
    $stmt = $db->prepare('
        INSERT INTO orders (name, email, total, user_id, created_at)
        VALUES (:name, :email, :total, :user_id, datetime(\'now\'))
    ');
    $stmt->execute([
        ':name'    => $data['name'],
        ':email'   => $data['email'],
        ':total'   => (int)$data['total'],
        ':user_id' => $userId,
    ]);
    return (int)$db->lastInsertId();
}
```

- Всегда возвращает `int` (ID новой записи)
- Числовые поля приводятся к `(int)` при вставке
- `datetime('now')` — встроенная функция SQLite для текущего времени
- Необязательные поля получают дефолт через `?? ''` или `?? null`

### update — обновление записи

```php
function updateProduct(int $id, array $data): void
{
    $db   = getDB();
    $stmt = $db->prepare('
        UPDATE products
        SET title = :title,
            price = :price,
            category = :category
        WHERE id = :id
    ');
    $stmt->execute([
        ':title'    => $data['title'],
        ':price'    => (int)$data['price'],
        ':category' => $data['category'] ?? '',
        ':id'       => $id,
    ]);
}
```

- Возвращает `void`
- ID всегда последний параметр в execute-массиве (для читаемости)

### update одного поля

```php
function updateProductCoverImage(int $id, string $coverImage): void
{
    $db   = getDB();
    $stmt = $db->prepare('UPDATE products SET cover_image = :cover_image WHERE id = :id');
    $stmt->execute([':cover_image' => $coverImage, ':id' => $id]);
}
```

Когда нужно обновить только одно поле — отдельная функция, не перегружать общий `update()`.

### delete — удаление

```php
function deleteProduct(int $id): void
{
    $db   = getDB();
    $stmt = $db->prepare('DELETE FROM products WHERE id = :id');
    $stmt->execute([':id' => $id]);
}
```

Если перед удалением нужно вернуть данные (например, путь к файлу для удаления с диска):

```php
function deleteProductImage(int $id): ?string
{
    $image = getImageById($id);
    if (!$image) {
        return null;
    }

    $db   = getDB();
    $stmt = $db->prepare('DELETE FROM product_images WHERE id = :id');
    $stmt->execute([':id' => $id]);

    return $image['path'];  // путь возвращается для удаления файла в контроллере/сервисе
}
```

### count — подсчёт записей

```php
function countProducts(): int
{
    $db = getDB();
    return (int)$db->query('SELECT COUNT(*) FROM products')->fetchColumn();
}
```

Для простых COUNT без параметров — `query()` вместо `prepare()`.

### Запрос с JOIN

```php
function getAllUsersWithOrderCount(): array
{
    $db = getDB();
    return $db->query(
        'SELECT u.*, COUNT(o.id) AS order_count
         FROM users u
         LEFT JOIN orders o ON o.user_id = u.id
         GROUP BY u.id
         ORDER BY u.created_at DESC, u.id DESC'
    )->fetchAll();
}
```

---

## Сессионная модель (без БД)

CartModel не использует базу данных — работает только с `$_SESSION`:

```php
/**
 * Cart is stored in session: $_SESSION['cart']
 * Structure: [ 'product_id:size' => [ product_id, title, price, cover_image, quantity, size ] ]
 *
 * Using composite key product_id:size so the same product with different sizes
 * occupies separate cart lines.
 */

function getCart(): array
{
    return $_SESSION['cart'] ?? [];
}

function addToCart(int $productId, string $title, int $price, string $coverImage, int $quantity, string $size): void
{
    $key = $productId . ':' . $size;
    if (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$key] = [
            'product_id'  => $productId,
            'title'       => $title,
            'price'       => $price,
            'cover_image' => $coverImage,
            'quantity'    => $quantity,
            'size'        => $size,
        ];
    }
}

function clearCart(): void
{
    $_SESSION['cart'] = [];
}
```

Такие модели пишутся так же — глобальные функции в отдельном файле, но вместо `getDB()` работают с `$_SESSION`.

---

## Сложная бизнес-логика в модели (OTP)

Когда логика принадлежит конкретной сущности и не требует внешних зависимостей — допустимо оставить её в модели:

```php
function verifyOtp(string $email, string $code): bool
{
    $db   = getDB();
    $now  = date('Y-m-d H:i:s');
    $stmt = $db->prepare(
        'SELECT id FROM otp_codes
         WHERE email = ? AND code = ? AND used_at IS NULL AND expires_at > ?
         ORDER BY id DESC LIMIT 1'
    );
    $stmt->execute([$email, $code, $now]);
    $row = $stmt->fetch();

    if (!$row) {
        return false;
    }

    invalidateOtp((int)$row['id']);  // пометить как использованный
    return true;
}

function invalidateOtp(int $id): void
{
    $db   = getDB();
    $stmt = $db->prepare("UPDATE otp_codes SET used_at = datetime('now') WHERE id = ?");
    $stmt->execute([$id]);
}
```

---

## Что запрещено в моделях

- SQL-инъекции через конкатенацию строк: `'WHERE id = ' . $id` — **никогда**
- Бизнес-логика, не связанная с хранением: отправка email, расчёт скидок, редиректы
- Вызовы `renderTemplate()`, `redirectTo()`, работа с `$_POST`/`$_GET`
- Вызовы других сервисов (EmailService, OtpService) — только модели
- Транзакции внутри одной модельной функции, охватывающие несколько сущностей — транзакции управляются из сервиса/контроллера

---

## Шаблон нового файла модели

```php
<?php

function getAll{Entities}(): array
{
    $db = getDB();
    return $db->query('SELECT * FROM {table} ORDER BY created_at DESC')->fetchAll();
}

function get{Entity}ById(int $id): array|false
{
    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM {table} WHERE id = :id');
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

function create{Entity}(array $data): int
{
    $db   = getDB();
    $stmt = $db->prepare('
        INSERT INTO {table} (field1, field2, created_at)
        VALUES (:field1, :field2, datetime(\'now\'))
    ');
    $stmt->execute([
        ':field1' => $data['field1'],
        ':field2' => $data['field2'] ?? '',
    ]);
    return (int)$db->lastInsertId();
}

function update{Entity}(int $id, array $data): void
{
    $db   = getDB();
    $stmt = $db->prepare('
        UPDATE {table}
        SET field1 = :field1,
            field2 = :field2
        WHERE id = :id
    ');
    $stmt->execute([
        ':field1' => $data['field1'],
        ':field2' => $data['field2'] ?? '',
        ':id'     => $id,
    ]);
}

function delete{Entity}(int $id): void
{
    $db   = getDB();
    $stmt = $db->prepare('DELETE FROM {table} WHERE id = :id');
    $stmt->execute([':id' => $id]);
}
```

---

## Подключение модели в контроллере

```php
// app/controllers/catalog/index.php

require ROOT . 'app/models/ProductModel.php';

$products = getAllProducts(limit: $perPage, offset: $offset);
$total    = countProducts();
$product  = getProductById($id);

requireFound($product);  // abort 404 если не найдено
```

Несколько моделей подключаются через несколько `require`:

```php
require ROOT . 'app/models/ProductModel.php';
require ROOT . 'app/models/ProductImageModel.php';
```
