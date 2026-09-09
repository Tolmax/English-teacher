<?php
// CLI-only, exact account update. Never resets or prints the password hash.
if (PHP_SAPI !== 'cli') exit(1);
$path = $argv[1] ?? '';
if (!is_file($path)) throw new RuntimeException('Database not found');
$db = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$db->exec('PRAGMA busy_timeout=10000');
$db->exec('BEGIN IMMEDIATE');
try {
    $user = $db->query('SELECT * FROM users WHERE id = 1')->fetch();
    if (!$user || $user['email'] !== 'admin@site.com' || $user['role'] !== 'teacher') {
        throw new RuntimeException('Account does not match the verified target');
    }
    if (!password_verify('admin', $user['password_hash'])) {
        throw new RuntimeException('Existing password differs; nothing changed');
    }
    $columns = array_column($db->query('PRAGMA table_info(users)')->fetchAll(), 'name');
    if (!in_array('login', $columns, true)) $db->exec('ALTER TABLE users ADD COLUMN login TEXT');
    $db->exec('CREATE UNIQUE INDEX IF NOT EXISTS users_login_unique ON users(login)');
    $stmt = $db->prepare('UPDATE users SET login = :login, updated_at = datetime(\'now\') WHERE id = :id');
    $stmt->execute([':login' => 'IrinaMaksakova1999', ':id' => 1]);
    $saved = $db->query('SELECT login, password_hash FROM users WHERE id = 1')->fetch();
    if ($saved['password_hash'] !== $user['password_hash'] || $saved['login'] !== 'IrinaMaksakova1999') {
        throw new RuntimeException('Verification failed');
    }
    $db->exec('COMMIT');
    echo "Login updated; original password hash unchanged.\n";
} catch (Throwable $error) {
    $db->exec('ROLLBACK');
    throw $error;
}
