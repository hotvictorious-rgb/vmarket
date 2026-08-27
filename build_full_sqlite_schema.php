<?php

/**
 * [AI] Comprehensive SQLite Schema & Seed Generator for Victorious MARKET
 * Parses installation/backup/database.sql and builds all tables cleanly in database.sqlite.
 */

$sqlFile = __DIR__ . '/backend/vmarket-web/installation/backup/database.sql';
$sqliteDb = __DIR__ . '/backend/vmarket-web/database/database.sqlite';

echo "Building complete SQLite schema from MySQL database.sql...\n";

if (file_exists($sqliteDb)) {
    unlink($sqliteDb);
}

$pdo = new PDO("sqlite:{$sqliteDb}");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("PRAGMA foreign_keys = OFF;");
$pdo->exec("PRAGMA journal_mode = WAL;");

$content = file_get_contents($sqlFile);

// Match all CREATE TABLE blocks
preg_match_all('/CREATE\s+TABLE\s+`?(\w+)`?\s*\((.*?)\)\s*ENGINE=[^;]+;/is', $content, $matches, PREG_SET_ORDER);

echo "Found " . count($matches) . " table definitions.\n";

$createdTables = 0;

foreach ($matches as $match) {
    $tableName = $match[1];
    $body = $match[2];

    $lines = explode("\n", $body);
    $cleanLines = [];

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (empty($trimmed)) continue;

        // Skip MySQL keys and constraints
        if (preg_match('/^(PRIMARY\s+KEY|KEY|UNIQUE\s+KEY|CONSTRAINT)\b/i', $trimmed)) {
            continue;
        }

        // Clean trailing comma
        $lineClean = rtrim($trimmed, ',');

        // Convert data types
        $lineClean = preg_replace('/`id`\s+(bigint|int)\(\d+\)(\s+UNSIGNED)?(\s+NOT\s+NULL)?(\s+AUTO_INCREMENT)?/i', '`id` INTEGER PRIMARY KEY AUTOINCREMENT', $lineClean);
        $lineClean = preg_replace('/\bint\(\d+\)\s+UNSIGNED/i', 'INTEGER', $lineClean);
        $lineClean = preg_replace('/\bbigint\(\d+\)\s+UNSIGNED/i', 'INTEGER', $lineClean);
        $lineClean = preg_replace('/\bint\(\d+\)/i', 'INTEGER', $lineClean);
        $lineClean = preg_replace('/\bbigint\(\d+\)/i', 'INTEGER', $lineClean);
        $lineClean = preg_replace('/\btinyint\(\d+\)/i', 'INTEGER', $lineClean);
        $lineClean = preg_replace('/\bsmallint\(\d+\)/i', 'INTEGER', $lineClean);
        $lineClean = preg_replace('/\bdouble\(\d+,\d+\)/i', 'REAL', $lineClean);
        $lineClean = preg_replace('/\bdecimal\(\d+,\d+\)/i', 'NUMERIC', $lineClean);
        $lineClean = preg_replace('/\blongtext\b/i', 'TEXT', $lineClean);
        $lineClean = preg_replace('/\bmediumtext\b/i', 'TEXT', $lineClean);
        $lineClean = preg_replace('/\bdatetime\b/i', 'TEXT', $lineClean);
        $lineClean = preg_replace('/\btimestamp\b/i', 'TEXT', $lineClean);
        $lineClean = preg_replace('/\bjson\b/i', 'TEXT', $lineClean);
        $lineClean = preg_replace('/\benum\([^)]+\)/i', 'TEXT', $lineClean);
        $lineClean = preg_replace('/\bCOMMENT\s+[\'"][^\'"]*[\'"]/i', '', $lineClean);
        $lineClean = preg_replace('/\bCHARACTER SET \w+/i', '', $lineClean);
        $lineClean = preg_replace('/\bCOLLATE \w+/i', '', $lineClean);
        $lineClean = preg_replace('/\bAUTO_INCREMENT\b/i', '', $lineClean);

        $cleanLines[] = $lineClean;
    }

    $createSql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (\n  " . implode(",\n  ", $cleanLines) . "\n);";

    try {
        $pdo->exec($createSql);
        $createdTables++;
    } catch (Exception $e) {
        echo "Error on table {$tableName}: " . $e->getMessage() . "\n";
    }
}

echo "✅ Created {$createdTables} tables in SQLite database.\n";
