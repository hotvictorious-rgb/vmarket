<?php

$dbPath = __DIR__ . '/hysam/database/database.sqlite';
$pdo = new PDO("sqlite:" . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$email = strtolower(trim(getenv('SUPER_ADMIN_EMAIL') ?: 'admin@admin.com'));
$name = getenv('SUPER_ADMIN_NAME') ?: 'Victorious Super Admin';

$pdo->exec("UPDATE users SET email = '{$email}', name = '{$name}' WHERE id = 'admin-user-1' OR role = 'admin'");
echo "✅ POS Super Admin record successfully synchronized with .env credentials ({$email})!\n";
