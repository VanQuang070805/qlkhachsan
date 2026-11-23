<?php

declare(strict_types=1);

$host = getenv('DB_HOST') ?: 'mysql';
$port = getenv('DB_PORT') ?: '3306';
$database = getenv('DB_DATABASE') ?: 'qlkhachsan';
$username = getenv('DB_USERNAME') ?: 'royal_app';
$password = (string) (getenv('DB_PASSWORD') ?: '');
$bootstrapMode = getenv('DB_BOOTSTRAP_MODE') ?: 'snapshot';
$snapshotPath = '/var/www/html/database/qlkhachsan.sql';

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database);

echo "[app-init] Waiting for MySQL at {$host}:{$port}...\n";

$pdo = null;
for ($attempt = 1; $attempt <= 60; $attempt++) {
    try {
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
        ]);
        break;
    } catch (Throwable $e) {
        if ($attempt === 60) {
            fwrite(STDERR, "[app-init] Could not connect to MySQL: {$e->getMessage()}\n");
            exit(1);
        }
        sleep(2);
    }
}

$stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'users'");
$tableCount = (int) $stmt->fetchColumn();
$stmt->closeCursor();

if ($tableCount === 0) {
    echo "[app-init] Fresh database volume detected. Bootstrap mode: {$bootstrapMode}\n";
    if ($bootstrapMode === 'snapshot' && is_file($snapshotPath)) {
        echo "[app-init] Importing initial snapshot {$snapshotPath}...\n";
        $sql = (string) file_get_contents($snapshotPath);
        $sql = (string) preg_replace('/^CREATE DATABASE\b.*?;\s*$/mi', '', $sql);
        $sql = (string) preg_replace('/^USE\s+`?[^`;]+`?\s*;\s*$/mi', '', $sql);
        $sql = (string) preg_replace('/\s+CHECK\s*\(\s*json_valid\s*\([^)]*\)\s*\)/i', '', $sql);

        $importStmt = $pdo->query($sql);
        if ($importStmt !== false) {
            do {
                // Advance through all multi-statement results until completion
            } while ($importStmt->nextRowset());
            $importStmt->closeCursor();
        }
        echo "[app-init] Snapshot imported successfully.\n";
    }
} else {
    echo "[app-init] Existing database schema detected.\n";
}
