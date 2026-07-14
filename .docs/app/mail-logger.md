# Mail Logger Documentation

## What is logEmail()?

`logEmail()` — функция для логирования копии каждого отправляемого письма в текстовый файл. Используется для отладки и архивирования писем в разработке и продакшне.

**Location:** `app/helpers/mail-logger.php`

---

## Function Signature

```php
/**
 * mail-logger.php
 *
 * Writes a copy of every outgoing email to a .txt file in storage/mail-log/.
 * Useful for development environments where mail() is not configured.
 */

function logEmail(string $to, string $subject, string $body, string $headers): void
{
    $logDir = ROOT . 'storage/mail-log';

    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $timestamp = date('Y-m-d_His');
    $filename  = $logDir . '/' . $timestamp . '_' . uniqid() . '.txt';

    $content  = 'Date: '    . date('Y-m-d H:i:s') . "\n";
    $content .= 'To: '      . $to                 . "\n";
    $content .= 'Subject: ' . $subject             . "\n";
    $content .= 'Headers: ' . $headers             . "\n";
    $content .= str_repeat('-', 40) . "\n";
    $content .= $body . "\n";

    file_put_contents($filename, $content);
}
```

**Parameters:**

- `$to` — email адрес получателя
- `$subject` — заголовок письма (может быть закодирован в base64)
- `$body` — тело письма
- `$headers` — заголовки письма (From, Content-Type, etc.)

---

## Usage Rule

**Вызывать `logEmail()` перед каждым `mail()` вызовом** — во всех функциях `app/services/EmailService.php`:

```php
logEmail($email, $subject, $body, $headers);         // Log first
mail($email, $subject, base64_encode($body), $headers);  // Send second
```

**Почему сначала лог:** если `mail()` упадёт, запись в лог всё равно сохранится.

### Текущие места вызова

- `sendOrderNotification()` — уведомление администратора о новом заказе
- `sendWelcomeEmail()` — приветственное письмо новому пользователю
- `sendOtpEmail()` — письмо с OTP-кодом для верификации

### Шаблон для новых email-функций

```php
function sendMyNewEmail(string $email, string $param): void
{
    $subject  = '=?UTF-8?B?' . base64_encode('My Subject') . '?=';

    $body  = 'Hello!' . "\r\n";
    $body .= 'Your parameter: ' . $param . "\r\n";

    $headers  = 'From: PEARLS <noreply@pearls.ru>' . "\r\n";
    $headers .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
    $headers .= 'Content-Transfer-Encoding: base64' . "\r\n";

    logEmail($email, $subject, $body, $headers);              // LOG FIRST
    mail($email, $subject, base64_encode($body), $headers);   // SEND SECOND
}
```

---

## Storage Location

```
storage/mail-log/
└── YYYY-MM-DD_HHmmss_uniqueid.txt
    2026-04-03_004022_69cf0c76ce77c.txt
```

Имя файла: дата + время + `uniqid()` (защита от коллизий при одновременной отправке нескольких писем).

### Example Log Entry

```
Date: 2026-04-03 00:40:22
To: admin@pearls.ru
Subject: =?UTF-8?B?0J3QvtCy0YvQuSD...?=
Headers: From: PEARLS <noreply@pearls.ru>
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: base64

----------------------------------------
Новый заказ #49
----------------------------------------

Покупатель: Мария Лебедева
Email: name@example.com
Телефон: +7 (912) 345-67-89
Адрес: г. Ростов-на-Дону, пр-т Ворошиловский, д. 14, кв. 3
Доставка: Курьером

Состав заказа:
  — Коричневая рифлёная блуза (M) × 1 = 1 665 ₽

Итого: 1 665 ₽
```

---

## How logEmail() Works

```php
function logEmail(string $to, string $subject, string $body, string $headers): void
{
    $logDir = ROOT . 'storage/mail-log';

    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $filename = $logDir . '/' . date('Y-m-d_His') . '_' . uniqid() . '.txt';

    $content  = 'Date: '    . date('Y-m-d H:i:s') . "\n";
    $content .= 'To: '      . $to                 . "\n";
    $content .= 'Subject: ' . $subject             . "\n";
    $content .= 'Headers: ' . $headers             . "\n";
    $content .= str_repeat('-', 40) . "\n";
    $content .= $body . "\n";

    file_put_contents($filename, $content);
}
```

- Авто-создаёт директорию если не существует
- Уникальные имена файлов через `timestamp + uniqid()`
- Не выбрасывает исключений, не проверяет результат записи

---

## Common Mistakes

❌ **Логировать после `mail()`** — если `mail()` упадёт, лог не сохранится:

```php
// Wrong
mail($email, $subject, $body, $headers);
logEmail($email, $subject, $body, $headers);
```

❌ **Передавать в лог закодированное тело** — в логе будет нечитаемый base64:

```php
// Wrong — body in log will be unreadable base64
logEmail($email, $subject, base64_encode($body), $headers);
mail($email, $subject, base64_encode($body), $headers);

// Right — log gets plain text, mail() gets encoded
logEmail($email, $subject, $body, $headers);
mail($email, $subject, base64_encode($body), $headers);
```

---

## Accessing Logs

```bash
# List recent emails
ls -lt storage/mail-log/ | head -10

# View latest email
cat storage/mail-log/$(ls -t storage/mail-log | head -1)

# Count total logged emails
ls storage/mail-log | wc -l

# Find emails to specific address
grep "To: user@example.com" storage/mail-log/*.txt

# Find emails with specific subject
grep "verification" storage/mail-log/*.txt

# View latest 5 emails
for f in $(ls -t storage/mail-log | head -5); do cat "storage/mail-log/$f"; echo "---"; done
```

---

## When NOT to Use

- Внешние сервисы отправки (SendGrid, Mailgun, AWS SES) — они ведут свои логи
- Массовые рассылки тысячам получателей — соображения дискового пространства

**Текущее приложение** использует нативный `mail()` → всегда логировать через `logEmail()`.
