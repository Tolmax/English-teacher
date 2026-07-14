# Router Documentation

## Overview

Lightweight URL-to-controller mapping system. Parses URL path into segments and dispatches to the appropriate controller file.

**Key Principles:**

- No OOP abstraction — functions only, simple and explicit
- Centralized route definitions in `config/routes.php`
- URL segments accessible via `$segments` array in controllers
- Nested routes handled inside controllers via segment-based checking
- No regex routing — exact string matching on first segment

---

## Architecture

### Request Flow

```
User Request
    ↓
index.php (entry point)
    ↓
parseRoute() → $segments = ['catalog', 'page=2']
    ↓
config/routes.php → checks $module = $segments[0]
    ↓
if $module === 'catalog' → load app/controllers/catalog/index.php
    ↓
Controller logic → renderTemplate() → HTML response
```

---

## URL Parsing: parseRoute()

**Location:** `app/core/router.php`

```php
function parseRoute(): array
{
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $uri        = $_SERVER['REQUEST_URI'];
    $path       = parse_url($uri, PHP_URL_PATH);
    $basePath   = dirname($scriptName);

    $relativePath = '/' . ltrim(
        preg_replace('/' . preg_quote($basePath, '/') . '/', '', $path, 1),
        '/'
    );

    $segments = explode('/', trim($relativePath, '/'));
    $segments = array_values(array_filter($segments, static fn($s) => $s !== ''));

    return $segments;
}
```

Strips base path, splits by `/`, filters empty segments. Query string is not included.

### Examples

| URL                      | $segments                                 |
| ------------------------ | ----------------------------------------- |
| `/`                      | `[]`                                      |
| `/catalog`               | `['catalog']`                             |
| `/catalog?page=2`        | `['catalog']`                             |
| `/product/123`           | `['product', '123']`                      |
| `/admin/products/5/edit` | `['admin', 'products', '5', 'edit']`      |

---

## Route Definitions: config/routes.php

Maps first segment to a controller file. No business logic here.

```php
<?php

$segments = parseRoute();
$module   = $segments[0] ?? '/';

$routes = [
    '/'       => ROOT . 'app/controllers/home.php',
    'catalog' => ROOT . 'app/controllers/catalog/index.php',
    'product' => ROOT . 'app/controllers/product/index.php',
    'cart'    => ROOT . 'app/controllers/cart/index.php',
    'order'   => ROOT . 'app/controllers/order/index.php',
    'payment' => ROOT . 'app/controllers/payment/index.php',
    'login'   => ROOT . 'app/controllers/login/index.php',
    'logout'  => ROOT . 'app/controllers/logout.php',
    'account' => ROOT . 'app/controllers/account/index.php',
    'admin'   => ROOT . 'app/controllers/admin/index.php',
    '404'     => ROOT . 'app/controllers/not-found.php',
];

if (isset($routes[$module])) {
    require $routes[$module];
} else {
    http_response_code(404);
    require $routes['404'];
    exit;
}
```

Root `/` matches empty `$segments` (base domain). Unmatched module → 404.

---

## Controllers: Handling Nested Routes

Controllers receive the full `$segments` array and handle sub-routing internally.

### Simple Controller (Single File)

No nested routing needed — just fetch data and render.

**`app/controllers/home.php`:**

```php
<?php

require ROOT . 'app/models/ProductModel.php';

$featuredProducts = getAllProducts(limit: 4);

renderTemplate('pages/home.tpl', [
    'products' => $featuredProducts,
]);
```

### Sub-Action Routing

**`app/controllers/cart/index.php`:**

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

if ($subAction === 'remove') {
    require ROOT . 'app/controllers/cart/remove.php';
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

**Important:** Always use `return;` after including sub-controllers to stop further execution.

### Multi-Level Nested Routing

**`app/controllers/admin/products/index.php`:**

```php
<?php

require ROOT . 'app/models/ProductModel.php';
require ROOT . 'app/models/ProductImageModel.php';

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

// /admin/products — list
$products = getAllProducts(limit: 100);
renderTemplate('pages/admin/products.tpl', ['products' => $products]);
```

URL patterns: `/admin/products` → list, `/admin/products/add` → add, `/admin/products/5/edit` → edit.

### Array Mapping for Many Sub-Actions

For routes with many sub-actions, use a mapping array instead of long if-chains.

**`app/controllers/order/index.php`:**

```php
<?php

$subAction = $segments[1] ?? '';

if ($subAction === 'checkout') {
    $checkoutAction = $segments[2] ?? '';

    $checkoutActions = [
        'form'         => 'checkout/form.php',
        'login'        => 'checkout/login.php',
        'otp'          => 'checkout/otp.php',
        'choose'       => 'checkout/choose.php',
        'check-email'  => 'checkout/check-email.php',
        'verify-otp'   => 'checkout/verify-otp.php',
        'guest-verify' => 'checkout/guest-verify.php',
        'place'        => 'checkout/place.php',
    ];

    if (isset($checkoutActions[$checkoutAction])) {
        require ROOT . 'app/controllers/order/' . $checkoutActions[$checkoutAction];
    } else {
        require ROOT . 'app/controllers/order/checkout.php';
    }
    return;
}

if ($subAction === 'thank-you') {
    $thankYouId = $segments[2] ?? '';
    if (!is_numeric($thankYouId) || (int)$thankYouId <= 0) {
        http_response_code(404);
        require ROOT . 'app/controllers/not-found.php';
        return;
    }
    require ROOT . 'app/controllers/order/thankyou.php';
    return;
}

if (is_numeric($subAction) && (int)$subAction > 0) {
    require ROOT . 'app/controllers/order/show.php';
    return;
}

http_response_code(404);
require ROOT . 'app/controllers/not-found.php';
```

### Protected Routes with Authentication

```php
<?php

$action = $segments[1] ?? '';

// Public actions — no auth
if ($action === 'login') {
    require ROOT . 'app/controllers/auth/login.php';
    return;
}

// Auth check for all remaining routes
requireAuth();

if ($action === '' || $action === 'dashboard') {
    require ROOT . 'app/controllers/admin/dashboard.php';
    return;
}

if ($action === 'products') {
    require ROOT . 'app/controllers/admin/products/index.php';
    return;
}

// No match = 404
http_response_code(404);
require ROOT . 'app/controllers/not-found.php';
```

---

## Helper Functions

**Location:** `app/core/helpers.php`

```php
function abort404(): never
{
    http_response_code(404);
    renderTemplate('pages/404.tpl');
    exit;
}

function requireNumericId(mixed $value): int
{
    if (!is_numeric($value) || (int)$value <= 0) abort404();
    return (int)$value;
}

function requireFound(mixed $value): void
{
    if (empty($value)) abort404();
}

function redirectTo(string $path): never
{
    header('Location: ' . HOST . ltrim($path, '/'));
    exit;
}
```

**HTTP method helpers:**

```php
isPost(): bool   // true if $_SERVER['REQUEST_METHOD'] === 'POST'
isGet(): bool    // true if $_SERVER['REQUEST_METHOD'] === 'GET'
```

**Session flash messages:**

```php
setFlash('success', 'Product added!');
redirectTo('admin/products');

// In next request:
$flash = getFlash('success');  // Returns message and deletes it
```

**Authentication:**

```php
isAdmin(): bool       // Check if admin session exists
requireAuth(): void   // Redirect to login if not authenticated
isUser(): bool        // Check if user logged in
requireUser(): void   // Redirect if user not authenticated
```

---

## Safe Segment Extraction

Always use null coalescing — never access `$segments[n]` directly:

```php
$action = $segments[1] ?? '';      // Default to empty string
$id     = $segments[2] ?? null;    // Default to null
$page   = (int)($segments[3] ?? 1); // Default to 1

// For numeric IDs — validates and aborts with 404 if invalid:
$id = requireNumericId($segments[1] ?? '');
```

---

## Common Gotchas

**1. Missing `return;` after sub-controller include:**

```php
// Wrong — code below still runs
if ($action === 'add') {
    require ROOT . 'app/controllers/cart/add.php';
}
$cart = getCart();  // Runs even for 'add'!

// Right
if ($action === 'add') {
    require ROOT . 'app/controllers/cart/add.php';
    return;
}
$cart = getCart();  // Only runs if action !== 'add'
```

**2. Unhandled routes silently falling through:**

```php
// Wrong
if ($action === 'products') {
    require ROOT . 'app/controllers/products.php';
}
renderTemplate('...');  // Renders for ANY unmatched action!

// Right
if ($action === 'products' || $action === '') {
    require ROOT . 'app/controllers/products.php';
    return;
}
http_response_code(404);
require ROOT . 'app/controllers/not-found.php';
```

---

## Routing Decision Flow

```
New route needed?
  → Add key to $routes in config/routes.php

Simple page (no sub-actions)?
  → Single file: app/controllers/newpage.php

Multiple sub-actions?
  → Directory: app/controllers/newpage/index.php

Many sub-actions (5+)?
  → Use array mapping pattern (see order/index.php)

Requires authentication?
  → Call requireAuth() before routing to protected controllers
```

---

## Controller File Organization

```
app/controllers/
│
├── Single files (simple pages)
│   ├── home.php
│   └── logout.php
│
└── Directories (multi-action pages)
    ├── catalog/
    │   ├── index.php     ← Router + main view
    │   └── items.php     ← JSON endpoint
    ├── cart/
    │   ├── index.php     ← Router + main view
    │   ├── add.php
    │   ├── update.php
    │   └── remove.php
    ├── order/
    │   ├── index.php     ← Router
    │   ├── checkout.php
    │   ├── checkout/
    │   │   ├── form.php
    │   │   ├── otp.php
    │   │   └── ...
    │   └── show.php
    └── admin/
        ├── index.php     ← Main router (auth check)
        ├── dashboard.php
        ├── products/
        │   ├── index.php
        │   ├── add.php
        │   └── edit.php
        ├── orders/
        └── users/
```

---

## Extending the Router

### Adding a New Route

1. Create `app/controllers/newpage.php` (or `newpage/index.php` for sub-actions)
2. Add to `config/routes.php`:

```php
'newpage' => ROOT . 'app/controllers/newpage.php',
```

### Adding Sub-Actions to Existing Route

1. Convert `app/controllers/myroute.php` → `app/controllers/myroute/index.php`
2. Add routing logic:

```php
$subAction = $segments[1] ?? '';

if ($subAction === 'action1') {
    require ROOT . 'app/controllers/myroute/action1.php';
    return;
}

renderTemplate('pages/myroute.tpl', [...]);
```

---

## Testing Routes

```bash
# GET requests
curl http://localhost/
curl http://localhost/catalog
curl http://localhost/product/123
curl http://localhost/admin/products/5/edit

# POST request
curl -X POST http://localhost/cart/add \
  -d "product_id=123&quantity=1"

# Should return 404
curl http://localhost/invalid
curl http://localhost/product/abc
```
