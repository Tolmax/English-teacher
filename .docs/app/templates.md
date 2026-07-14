# Template System — Полная документация-инструкция

## Обзор

Проект использует **самописную систему шаблонов на чистом PHP** без внешних движков (Twig, Blade и т.п.). Шаблоны — это обычные `.tpl`-файлы с PHP-вставками. Система работает через единственную функцию `renderTemplate()`.

---

## 1. Функция рендера

**Файл:** `app/core/renderer.php`

```php
function renderTemplate(string $template, array $data = []): void
{
    $templatePath = ROOT . 'templates/' . $template;

    if (!file_exists($templatePath)) {
        throw new \RuntimeException('Template not found: ' . $template);
    }

    extract($data, EXTR_SKIP);
    include $templatePath;
}
```

Принимает **относительный путь** от `templates/`. Ключи массива `$data` становятся локальными переменными через `extract()`. Флаг `EXTR_SKIP` защищает от перезаписи существующих переменных.

**Правила передачи данных:**
- Передавать только то, что нужно шаблону; ключи — строго snake_case.
- Числа приводить к `int` до передачи или в шаблоне через `(int)`.
- Не передавать бизнес-логику, SQL-запросы или объекты PDO.

### Вызов из контроллера

```php
// app/controllers/catalog/index.php

renderTemplate('pages/catalog.tpl', [
    'products' => $products,
    'total'    => $total,
    'hasMore'  => $hasMore,
    'perPage'  => $perPage,
]);
```

---

## 2. Структура папки templates/ пример

```
templates/
├── layouts/          # Референс/документация — напрямую не используются
│   ├── main.tpl
│   └── admin.tpl
├── partials/         # Переиспользуемые фрагменты страниц
│   ├── header.tpl        # <html><head>...<header> публичного сайта
│   ├── footer.tpl        # <footer>...</html> публичного сайта
│   ├── admin-header.tpl  # <html><head>...<main> панели администратора
│   └── admin-footer.tpl  # </main></div>...</html> панели администратора
├── components/       # Независимые переиспользуемые UI-блоки
│   └── product-card.tpl
└── pages/            # Шаблоны страниц (точка входа для renderTemplate)
    ├── home.tpl
    ├── catalog.tpl
    ├── product.tpl
    ├── cart.tpl
    ├── thank-you.tpl
    ├── 404.tpl
    ├── checkout.tpl
    ├── checkout/
    │   ├── entry.tpl
    │   ├── email.tpl
    │   ├── otp.tpl
    │   ├── register.tpl
    │   ├── order_form.tpl
    │   ├── guest_form.tpl
    │   └── post_order.tpl
    ├── auth/
    │   └── login.tpl
    ├── account/
    │   ├── index.tpl
    │   ├── edit.tpl
    │   ├── orders.tpl
    │   └── order-show.tpl
    ├── order/
    │   └── show.tpl
    └── admin/
        ├── login.tpl
        ├── dashboard.tpl
        ├── products.tpl
        ├── product-add.tpl
        ├── product-edit.tpl
        ├── orders.tpl
        ├── order-show.tpl
        ├── users.tpl
        └── user-show.tpl
```

---

## 3. Типы шаблонов и правила их написания

### 3.1 Partials — подключаемые фрагменты

Подключаются через `include` внутри page-шаблона:

```php
include ROOT . 'templates/partials/header.tpl';
// ... контент страницы ...
include ROOT . 'templates/partials/footer.tpl';
```

**header.tpl** — содержит `<!DOCTYPE html>`, `<head>`, открывающий `<body>`, `<header>`. Не закрывает `<body>` и `<html>` — это делает footer.tpl. Читает `$pageTitle` из scope:

```php
<title><?= e($pageTitle ?? 'PEARLS — Женская одежда и аксессуары') ?></title>
```

Также вызывает `isUser()` для рендера иконки профиля/входа.

**footer.tpl** — содержит `<footer>`, `<script>` с main.js, `</body>`, `</html>`. Переменных не принимает.

**admin-header.tpl** — содержит `<!DOCTYPE html>`, `<head>`, layout-обёртку с sidebar и открывающий `<main class="admin-content">`. Читает:
- `$pageTitle` — заголовок вкладки браузера.
- `$activeNav` — идентификатор активного пункта меню (`'dashboard'`, `'products'`, `'product-add'`, `'orders'`, `'users'`).

```php
class="admin-sidebar__link <?= ($activeNav ?? '') === 'dashboard' ? 'is-active' : '' ?>"
```

**admin-footer.tpl** — содержит `</main>`, `</div>`, `<script>`, `</body>`, `</html>`. Переменных не принимает.

---

### 3.2 Pages — шаблоны страниц

Основная точка входа в `renderTemplate()`. Каждый page-шаблон устанавливает переменные для partials, подключает header, выводит контент, подключает footer.

**Публичная страница:**

```php
<?php
$pageTitle = 'Каталог — PEARLS';
include ROOT . 'templates/partials/header.tpl';
?>

  <main class="container">
    <!-- контент -->
  </main>

<?php include ROOT . 'templates/partials/footer.tpl'; ?>
```

**Страница администратора:**

```php
<?php
$pageTitle = 'Товары';
$activeNav = 'products';
include ROOT . 'templates/partials/admin-header.tpl';
?>

      <!-- контент внутри <main class="admin-content"> -->
      <div class="admin-content__header">
        <h1 class="admin-content__title">Товары</h1>
      </div>

<?php include ROOT . 'templates/partials/admin-footer.tpl'; ?>
```

**Важно:** в admin-шаблонах контент пишется с отступом в 6 пробелов — он вложен внутри `<main>`, открытого в `admin-header.tpl`.

---

### 3.3 Components — независимые UI-блоки

Подключаются в цикле или в любом месте шаблона. Используют переменную `$product`, которая должна быть определена в scope **до** подключения:

```php
<?php foreach ($products as $product): ?>
  <?php include ROOT . 'templates/components/product-card.tpl'; ?>
<?php endforeach; ?>
```

Компонент — чистый HTML-фрагмент, не подключает partials и не устанавливает `$pageTitle`.

---

### 3.4 Динамическая вставка шага (sub-template)

В многошаговых процессах page-шаблон подключает нужный под-шаблон по переменной `$step`:

```php
// templates/pages/checkout.tpl
<?php include ROOT . 'templates/pages/checkout/' . str_replace('-', '_', $step) . '.tpl'; ?>
```

```php
renderTemplate('pages/checkout.tpl', [
    'cart'  => $cart,
    'total' => $total,
    'step'  => 'entry',   // → включает checkout/entry.tpl
]);
```

Под-шаблоны шагов — HTML-фрагменты формы, не полные страницы, не подключают header/footer.

**Именование:** дефисы в имени шага заменяются на подчёркивания (`order-form` → `order_form.tpl`).

---

## 4. Обязательные правила безопасности — функция e()

**Все** строковые переменные перед выводом в HTML обязаны проходить через `e()`:

```php
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
```

### Когда использовать e()

```php
// Текстовый вывод:
<?= e($product['title']) ?>
<?= e($user['name']) ?>

// Атрибуты HTML:
<input value="<?= e($oldData['title'] ?? '') ?>">
<img alt="<?= e($product['title']) ?>">

// data-атрибуты со строками:
data-initial-tags="<?= e(implode(',', $sizes)) ?>"
```

### Когда НЕ использовать e()

```php
// Числа, приведённые к int:
<input value="<?= (int)$product['id'] ?>">
<span><?= (int)$product['price'] ?></span>

// Цена через formatPrice() — уже безопасна:
<?= formatPrice((int)$product['price']) ?>

// Константа HOST — не пользовательские данные:
href="<?= HOST ?>catalog"
```

### Вывод в JS-контексте (onclick)

```php
onsubmit="return confirm('Удалить товар «<?= e(addslashes($product['title'])) ?>»?')"
```

---

## 5. Паттерны вывода данных

### Условный вывод (empty-check)

```php
<?php if (!empty($product['cover_image'])): ?>
  <img src="<?= HOST ?>uploads/<?= e($product['cover_image']) ?>">
<?php else: ?>
  <div class="product-card__image-placeholder"></div>
<?php endif; ?>
```

### Nullsafe с оператором ??

```php
<?= e($pageTitle ?? 'PEARLS — Женская одежда и аксессуары') ?>

value="<?= e($oldData['title'] ?? '') ?>"

value="<?= e($old['name'] ?? $contact['name'] ?? '') ?>"
```

### Цикл foreach

```php
<?php foreach ($products as $product): ?>
  <?php include ROOT . 'templates/components/product-card.tpl'; ?>
<?php endforeach; ?>
```

### Шаблонная логика (допустимо)

Небольшая "шаблонная" логика допустима — декодирование JSON, форматирование дат, маппинг меток:

```php
<?php
$sizes = json_decode($product['sizes'] ?? '[]', true) ?: [];
$sizesStr = !empty($sizes) ? implode(', ', $sizes) : 'One size';

$statusLabels = [
  'new'      => 'Ожидает оплаты',
  'paid'     => 'Оплачен',
  'canceled' => 'Отменён',
];
$statusLabel = $statusLabels[$order['status'] ?? 'new'] ?? 'Неизвестен';
$date = date('d.m.Y', strtotime($order['created_at']));
?>
```

**Запрещено в шаблонах:** SQL-запросы, вызовы модели, вызовы сервисов, работа с сессиями, редиректы.

### Flash-сообщения

```php
<?php if (!empty($flash)): ?>
  <div class="admin-flash" role="status"><?= e($flash) ?></div>
<?php endif; ?>
```

---

## 6. Паттерны работы с формами

### Поле с ошибкой валидации

```php
<div class="form__field <?= !empty($errors['name']) ? 'has-error' : '' ?>">
  <label class="form__label" for="name">Имя *</label>
  <input class="form__input <?= !empty($errors['name']) ? 'is-error' : '' ?>"
    type="text" id="name" name="name"
    value="<?= e($old['name'] ?? '') ?>"
    required>
  <?php if (!empty($errors['name'])): ?>
    <span class="form__error"><?= e($errors['name']) ?></span>
  <?php endif; ?>
</div>
```

Враппер `.form__field` получает `has-error`, `<input>` — `is-error`, ошибка — в `<span class="form__error">`, старые данные — из `$old['field']`.

### Баннер ошибок формы

```php
<?php if (!empty($errors)): ?>
  <div class="form__error-banner" role="alert">
    Пожалуйста, исправьте ошибки в форме
  </div>
<?php endif; ?>
```

### Действие формы

```php
<form action="<?= HOST ?>admin/products/add" method="post" enctype="multipart/form-data">
```

`action` — через `HOST`. `method="post"` — для всех мутирующих форм. `enctype="multipart/form-data"` — только при загрузке файлов.

### Форма удаления с подтверждением

```php
<form action="<?= HOST ?>admin/products/<?= (int)$product['id'] ?>/delete"
      method="post"
      onsubmit="return confirm('Удалить товар «<?= e(addslashes($product['title'])) ?>»?')">
  <button type="submit" class="btn btn--danger">Удалить</button>
</form>
```

---

## 7. HTML-конвенции в шаблонах

- **CSS-классы** — только BEM, только lowercase: `block__element`, `block--modifier`.
- **Атрибуты доступности** — `aria-label` на иконочных кнопках, `role="alert"` на ошибках, `role="status"` на flash-сообщениях.
- **data-атрибуты** — для JS-хуков без бизнес-логики в шаблоне:

```html
<div class="catalog-grid"
     data-catalog-grid
     data-has-more="<?= $hasMore ? 'true' : 'false' ?>"
     data-offset="<?= count($products) ?>"
     data-per-page="<?= (int)$perPage ?>"
     data-endpoint="<?= HOST ?>catalog/items">
```

- **Lazy loading** — `loading="lazy"` на всех `<img>`, кроме первой видимой.
- **Изображения** — всегда с `alt`, содержащим название товара через `e()`.
- **Инлайн-стили** — допустимы для уникальных случаев, которые не заслуживают отдельного CSS-класса.

---

## 8. Layouts — устаревший паттерн

Файлы `templates/layouts/main.tpl` и `templates/layouts/admin.tpl` **не используются**. Они дублируют partials и хранятся как референс.

Актуальная компоновка — через **partials** (header + footer), подключаемые напрямую из page-шаблона.

---

## 9. Как создать новый шаблон

### Шаг 1 — Определить тип

| Ситуация | Тип | Папка |
|----------|-----|-------|
| Полная страница публичного сайта | page | `templates/pages/` |
| Полная страница панели администратора | page | `templates/pages/admin/` |
| Переиспользуемый фрагмент (карточка, строка таблицы) | component | `templates/components/` |
| Шаг многошагового процесса | sub-template | `templates/pages/{процесс}/` |

### Шаг 2 — Написать шаблон

Следовать каноническим паттернам из **Раздела 3.2**. Для публичной страницы — include header/footer, для admin — include admin-header/admin-footer с `$pageTitle` и `$activeNav`.

### Шаг 3 — Добавить вызов из контроллера

```php
$items = getAllItems();

renderTemplate('pages/my-section/index.tpl', [
    'items' => $items,
]);
```

### Шаг 4 — Проверить чек-лист

- [ ] Все строки из БД и `$_POST`/`$_GET` выводятся через `e()`
- [ ] Числа приводятся к `(int)` перед выводом
- [ ] Цены форматируются через `formatPrice()`
- [ ] Ссылки строятся через `HOST`
- [ ] Пути к ресурсам из `assets/` строятся через `HOST`
- [ ] Пути к include строятся через `ROOT`
- [ ] Нет SQL-запросов, вызовов модели, редиректов
- [ ] CSS-классы следуют BEM
- [ ] Изображения имеют `alt` и `loading="lazy"`
- [ ] Поля формы с ошибками имеют классы `has-error` / `is-error`

---

## 10. Справка по helper-функциям и константам

Определены в `app/core/helpers.php` и `config/app.php`, доступны глобально:

| Функция | Сигнатура | Назначение |
|---------|-----------|------------|
| `e()` | `e(string $value): string` | XSS-экранирование для HTML-вывода |
| `formatPrice()` | `formatPrice(int $price): string` | Форматирование числа в `1 500 ₽` |
| `isUser()` | `isUser(): bool` | Проверка авторизации пользователя |
| `isAdmin()` | `isAdmin(): bool` | Проверка прав администратора |

| Константа | Пример значения | Использование |
|-----------|----------------|---------------|
| `ROOT` | `/var/www/html/` | Файловые пути: `ROOT . 'templates/...'` |
| `HOST` | `https://example.com/` | URL-ссылки: `HOST . 'catalog'` |
