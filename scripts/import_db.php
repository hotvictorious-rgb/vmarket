<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
]);

echo "Dropping and recreating database vmarket...\n";
$pdo->exec("DROP DATABASE IF EXISTS vmarket");
$pdo->exec("CREATE DATABASE vmarket CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE vmarket");
$pdo->exec("SET FOREIGN_KEY_CHECKS=0");

echo "Reading database.sql...\n";
$file = __DIR__ . '/../backend/vmarket-web/installation/backup/database.sql';
$handle = fopen($file, 'r');
if (!$handle) {
    die("Cannot open $file\n");
}

$query = '';
$executed = 0;
$errors = 0;

while (($line = fgets($handle)) !== false) {
    $trimmed = trim($line);
    if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*')) {
        continue;
    }
    $query .= $line;
    if (str_ends_with($trimmed, ';')) {
        $res = $pdo->exec($query);
        if ($res === false && $pdo->errorCode() !== '00000') {
            $errors++;
        } else {
            $executed++;
        }
        $query = '';
    }
}
fclose($handle);

$pdo->exec("SET FOREIGN_KEY_CHECKS=1");

echo "Finished import: $executed queries executed, $errors skipped.\n";

$stmt = $pdo->query("DESCRIBE business_settings");
if ($stmt) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['Field'] === 'id') {
            echo "business_settings.id Extra: " . $row['Extra'] . "\n";
        }
    }
}
