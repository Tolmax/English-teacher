<?php
function getDB(): PDO
{
    static $db;
    return $db ??= new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
}
require dirname(__DIR__) . '/website/app/models/user.php';
require dirname(__DIR__) . '/website/app/validators/auth.php';
getDB()->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, email TEXT UNIQUE, login TEXT UNIQUE, password_hash TEXT)');
$hash = password_hash('admin', PASSWORD_DEFAULT);
$stmt = getDB()->prepare('INSERT INTO users VALUES (1, :email, :login, :hash)');
$stmt->execute([':email' => 'admin@site.com', ':login' => 'IrinaMaksakova1999', ':hash' => $hash]);
$user = getUserByLogin('IrinaMaksakova1999');
if (!$user || !password_verify('admin', $user['password_hash'])) throw new RuntimeException('New login failed');
if (getUserByLogin('admin@site.com') !== false) throw new RuntimeException('Old login still works');
if (getUserByLogin("' OR 1=1 --") !== false) throw new RuntimeException('Unsafe lookup');
if (validateLoginData(['email' => 'IrinaMaksakova1999', 'password' => 'admin'])) throw new RuntimeException('Username rejected');
if (!validateLoginData(['email' => '', 'password' => ''])) throw new RuntimeException('Empty credentials accepted');
echo "PASS: new login, unchanged password, old login disabled, validation, parameterized lookup\n";
