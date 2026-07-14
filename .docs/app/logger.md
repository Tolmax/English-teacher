# Error Logger Documentation

## Overview

Comprehensive error and exception handling system that captures all PHP errors, fatal errors, and exceptions, logs them to daily files with full context, and hides details from users.

**Key Principles:**

- All errors are logged, nothing is silently ignored
- Errors are hidden from users (security first)
- Full context logged: file, line, stack trace, request info
- Log files are organized by date (`errors-YYYY-MM-DD.log`)
- File I/O uses `LOCK_EX` to prevent concurrent write corruption
- No external dependencies — pure PHP

---

## Architecture

**File:** `app/core/error-logger.php`
**Entry point:** `app_register_error_handlers(): void` — called once at startup in `index.php`

```php
require __DIR__ . '/app/core/error-logger.php';
app_register_error_handlers();  // Must be first
```

### Код файла `app/core/error-logger.php`

```php
<?php

function app_register_error_handlers(): void
{
    set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
        $fatal = [E_USER_ERROR, E_RECOVERABLE_ERROR];
        if (in_array($errno, $fatal, true)) {
            throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        }
        app_write_error_log('PHP Warning/Notice', $errstr, $errfile, $errline);
        return true;
    });

    set_exception_handler(function (\Throwable $e): void {
        app_write_error_log('Exception', $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
        http_response_code(500);
        echo 'Internal Server Error';
        exit;
    });

    register_shutdown_function(function (): void {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            app_write_error_log('Fatal Error', $error['message'], $error['file'], $error['line']);
            http_response_code(500);
            echo 'Internal Server Error';
        }
    });
}

function app_write_error_log(string $type, string $message, string $file, int $line, string $trace = ''): void
{
    $logDir = defined('ROOT') ? ROOT . 'storage/logs' : __DIR__ . '/../../storage/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $logFile = $logDir . '/errors-' . date('Y-m-d') . '.log';
    $entry = sprintf(
        "[%s] [%s] %s %s\n  File: %s:%d\n  Method: %s %s\n%s\n",
        date('Y-m-d H:i:s'),
        $type,
        $message,
        '',
        $file,
        $line,
        $_SERVER['REQUEST_METHOD'] ?? '',
        $_SERVER['REQUEST_URI'] ?? '',
        $trace ? "  Trace:\n  " . str_replace("\n", "\n  ", $trace) . "\n" : ''
    );
    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}
```

### Request Flow

```
PHP Error/Warning/Notice
    ↓
set_error_handler()
    ├─ Fatal (E_USER_ERROR, E_RECOVERABLE_ERROR) → throw ErrorException → exception handler
    └─ Non-fatal → app_write_error_log() → return true (suppress default handler)

Uncaught Exception/Error
    ↓
set_exception_handler() → app_write_error_log() → HTTP 500 → exit

Fatal Error at shutdown
    ↓
register_shutdown_function() + error_get_last() → app_write_error_log() → HTTP 500
```

---

## Error Handlers

### 1. set_error_handler() — PHP Warnings & Notices

```php
set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    $fatal = [E_USER_ERROR, E_RECOVERABLE_ERROR];

    if (in_array($errno, $fatal, true)) {
        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    app_write_error_log('PHP Warning/Notice', $errstr, $errfile, $errline);
    return true;  // Suppress default handler
});
```

| Error Type          | Severity     | Action                  |
| ------------------- | ------------ | ----------------------- |
| E_USER_ERROR        | Fatal        | Throw → exception handler |
| E_RECOVERABLE_ERROR | Fatal        | Throw → exception handler |
| E_WARNING           | Warning      | Log and continue        |
| E_NOTICE            | Notice       | Log and continue        |
| E_DEPRECATED        | Deprecation  | Log and continue        |
| E_USER_WARNING      | User warning | Log and continue        |
| E_USER_NOTICE       | User notice  | Log and continue        |

### 2. set_exception_handler() — Uncaught Exceptions

```php
set_exception_handler(function (\Throwable $e): void {
    app_write_error_log(
        'Exception',
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );

    http_response_code(500);
    echo 'Internal Server Error';
    exit;
});
```

### 3. register_shutdown_function() — Fatal Errors

```php
register_shutdown_function(function (): void {
    $error = error_get_last();

    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        app_write_error_log('Fatal Error', $error['message'], $error['file'], $error['line']);

        http_response_code(500);
        echo 'Internal Server Error';
    }
});
```

---

## Error Logging: app_write_error_log()

```php
function app_write_error_log(
    string $type,      // 'Exception', 'PHP Warning/Notice', 'Fatal Error'
    string $message,
    string $file,
    int $line,
    string $trace = ''
): void
```

### Log Directory Structure

```
storage/
└── logs/
    ├── errors-2026-03-25.log
    ├── errors-2026-03-26.log
    └── errors-2026-03-27.log
```

- Uses `ROOT` constant (from `config/app.php`) or falls back to relative path
- Directory is auto-created if missing (`mkdir(..., 0755, true)`)
- One file per day, appended with `FILE_APPEND | LOCK_EX`

### Log Entry Format

**Warning/Notice:**
```
[2026-03-25 23:20:25] [PHP Warning/Notice] Undefined variable $products
  File: /var/www/html/app/controllers/catalog/index.php:19
  Method: GET /catalog

```

**Exception (with trace):**
```
[2026-03-25 23:20:25] [Exception] count(): Argument #1 ($value) must be of type Countable|array, null given
  File: /var/www/html/app/controllers/catalog/index.php:19
  Method: GET /catalog
  Trace:
  #0 /var/www/html/config/routes.php(17): require()
  #1 /var/www/html/index.php(16): require('/var/www/html/c...')
  #2 {main}

```

### Entry Construction

```php
$entry = sprintf(
    "[%s] [%s] %s %s\n  File: %s:%d\n  Method: %s %s\n%s\n",
    date('Y-m-d H:i:s'),
    $type,
    $message,
    '',
    $file,
    $line,
    $_SERVER['REQUEST_METHOD'] ?? '',
    $_SERVER['REQUEST_URI'] ?? '',
    $trace ? "  Trace:\n  " . str_replace("\n", "\n  ", $trace) . "\n" : ''
);
```

---

## Integration with Application

**File:** `index.php` — error handlers must be registered **before** anything that can fail:

```php
<?php

// 1. Register error handlers FIRST
require __DIR__ . '/app/core/error-logger.php';
app_register_error_handlers();

// 2. Load configuration
require __DIR__ . '/config/app.php';
require __DIR__ . '/config/database.php';

// 3. Load services
require ROOT . 'app/core/router.php';
require ROOT . 'app/core/db.php';
require ROOT . 'app/core/auth.php';
require ROOT . 'app/core/renderer.php';
require ROOT . 'app/core/helpers.php';
require ROOT . 'vendor/autoload.php';

// 4. Route request
require ROOT . 'config/routes.php';
```

**Not protected** (happen before handler registration):
- Syntax errors in `index.php` itself
- Syntax errors in `error-logger.php`
- Autoloader failures

---

## Accessing Logs

```bash
# Today's errors
cat storage/logs/errors-$(date +%Y-%m-%d).log

# Last 20 entries
tail -20 storage/logs/errors-2026-03-26.log

# Follow in real-time
tail -f storage/logs/errors-$(date +%Y-%m-%d).log

# Filter by type
grep "Exception" storage/logs/errors-2026-03-26.log

# Filter by file
grep "ProductModel.php" storage/logs/errors-2026-03-26.log

# Check log sizes
du -h storage/logs/

# Archive logs older than 30 days
find storage/logs -name "*.log" -mtime +30 -exec gzip {} \;
```

---

## Companion Logger: Email Logger

**File:** `app/helpers/mail-logger.php` — logs every outgoing email to disk (separate from error logs).

```
storage/mail-log/
├── 2026-03-25_232025_507a3f2c.txt
└── ...
```

```php
require ROOT . 'app/helpers/mail-logger.php';

logEmail(
    'user@example.com',
    'Order Confirmation #12345',
    '<h1>Thank you!</h1><p>Your order is confirmed.</p>',
    'From: noreply@shop.ru\nContent-Type: text/html; charset=UTF-8'
);
```

Each file contains: Date, To, Subject, Headers, and full HTML body.

---

## Configuration & Customization

### Change Log Directory

In `app/core/error-logger.php`, modify:

```php
$logDir = defined('ROOT') ? ROOT . 'custom/logs' : __DIR__ . '/../../custom/logs';
```

### Add Query String or IP to Log Entry

```php
$entry = sprintf(
    "[%s] [%s] Client: %s %s\n  File: %s:%d\n  Method: %s %s\n  Query: %s\n%s\n",
    date('Y-m-d H:i:s'),
    $type,
    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    $message,
    $file,
    $line,
    $_SERVER['REQUEST_METHOD'] ?? '',
    $_SERVER['REQUEST_URI'] ?? '',
    $_SERVER['QUERY_STRING'] ?? '',
    $trace ? "  Trace:\n  " . str_replace("\n", "\n  ", $trace) . "\n" : ''
);
```

### Add Trace to Warnings

By default, warnings don't include a stack trace. To add one:

```php
app_write_error_log('PHP Warning/Notice', $errstr, $errfile, $errline, (new \Exception())->getTraceAsString());
```

---

## Best Practices

**1. Always register handlers first** — before any `require` that might fail.

**2. Let the system catch errors** — don't wrap every call in try/catch just to call `error_log()`. The system already logs everything automatically.

**3. Never expose error details to users** — the exception handler already returns a generic `500 Internal Server Error`. Don't add `$e->getMessage()` to the response.

**4. Use appropriate exception types:**

```php
throw new \Exception('Invalid email format');       // validation
throw new \ErrorException('Cannot write to cache'); // internal system error
```

**5. Directory permissions:**

```bash
chmod -R 755 storage/
sudo chown -R www-data:www-data storage/  # for Apache/Nginx
```

**6. Set up log rotation** (crontab):

```bash
0 2 * * * find /path/to/storage/logs -name "*.log" -mtime +30 -exec gzip {} \;
```

---

## Troubleshooting

### Logs not being created

1. Check directory exists and is writable: `ls -ld storage/logs/`
2. Create if missing: `mkdir -p storage/logs && chmod 755 storage/logs`
3. Verify `app_register_error_handlers()` is the first call in `index.php`

### Partial log entries

Should not happen — code uses `FILE_APPEND | LOCK_EX`. Verify both flags are present in the `file_put_contents()` call.

### Memory exhaustion not logged

Shutdown handler catches most fatals, but not out-of-memory errors (they occur during memory allocation). Monitor separately:

```bash
php -d memory_limit=256M index.php
```

### Stack trace missing

Only exceptions include a trace. Warnings don't by default — see "Add Trace to Warnings" above.

---

## Security

**1. Never expose internals to users** — users always see only `500 Internal Server Error`.

**2. Block web access to logs** — if `storage/` is under webroot:

```htaccess
# storage/logs/.htaccess
<FilesMatch "\.(log)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

Or nginx:

```nginx
location ~ /storage/logs/ {
    deny all;
}
```

**3. Sanitize sensitive data before it reaches logs:**

```php
$safe_message = preg_replace('/password["\']?\s*[:=]\s*["\']?[^"\']+["\']?/i', 'password=***', $e->getMessage());
app_write_error_log('Exception', $safe_message, ...);
```

**4. Restrict log file permissions:**

```bash
chmod 600 storage/logs/*.log
chmod 700 storage/logs/
```
