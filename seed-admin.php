<?php
$config = require __DIR__ . '/api/config/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}",
    $db['user'], $db['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$email    = 'admin@pasabuy.test';
$password = 'admin12345';

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) { echo "Admin already exists: $email\n"; exit; }

$pdo->prepare("INSERT INTO users (email, password_hash, role, status, email_verified_at, created_at, updated_at)
               VALUES (?, ?, 'admin', 'accepted', NOW(), NOW(), NOW())")
    ->execute([$email, password_hash($password, PASSWORD_BCRYPT)]);

echo "✅ Admin created\nEmail: $email\nPassword: $password\n";