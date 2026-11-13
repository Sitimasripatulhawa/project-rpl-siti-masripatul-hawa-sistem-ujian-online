<?php
/**
 * Database Migration & Seeder Runner
 * AI Online Exam & Proctoring System
 */

require_once __DIR__ . '/../app/config/config.php';

echo "========================================================\n";
echo " AI Online Exam & Proctoring System - Migration Runner\n";
echo "========================================================\n\n";

try {
    // 1. Connect to MySQL server without DB first (to ensure DB exists)
    $dsnNoDb = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4';
    $pdo = new PDO($dsnNoDb, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "[1/4] Checking and creating database `" . DB_NAME . "`...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("USE `" . DB_NAME . "`;");
    echo "      Database `" . DB_NAME . "` is ready.\n\n";

    // 2. Read and execute schema.sql
    echo "[2/4] Executing database schema (13 tables)...\n";
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found at: " . $schemaFile);
    }

    $schemaSql = file_get_contents($schemaFile);
    $pdo->exec($schemaSql);
    echo "      Schema created successfully.\n\n";

    // 3. Read and execute seeds.sql
    echo "[3/4] Seeding initial data (Admin, Teacher, Students, Class, Subject, Questions)...\n";
    $seedsFile = __DIR__ . '/seeds.sql';
    if (!file_exists($seedsFile)) {
        throw new Exception("Seeds file not found at: " . $seedsFile);
    }

    $seedsSql = file_get_contents($seedsFile);
    $pdo->exec($seedsSql);
    echo "      Seed data inserted successfully.\n\n";

    // 4. Verify & Report Database Tables & Records
    echo "[4/4] Validating tables, foreign keys, and records...\n";
    $tablesStmt = $pdo->query("SHOW TABLES");
    $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

    echo "      ---------------------------------------------------------\n";
    echo "      Table Name                     | Record Count\n";
    echo "      ---------------------------------------------------------\n";
    foreach ($tables as $table) {
        $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
        $count = $countStmt->fetchColumn();
        printf("      %-30s | %d\n", $table, $count);
    }
    echo "      ---------------------------------------------------------\n";

    // Foreign Keys Count
    $fkStmt = $pdo->query("
        SELECT COUNT(*) as fk_count 
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $fkCount = $fkStmt->fetchColumn();
    echo "\n      Total Foreign Keys Verified: {$fkCount}\n";

    // Password verification check
    $userStmt = $pdo->query("SELECT username, email, role, password FROM users LIMIT 1");
    $testUser = $userStmt->fetch();
    $passwordValid = password_verify('password123', $testUser['password']);
    echo "      Password Hash Check (admin / password123): " . ($passwordValid ? "PASSED (Valid BCRYPT)" : "FAILED") . "\n";

    echo "\n========================================================\n";
    echo " MIGRATION & SEED COMPLETED SUCCESSFULLY!\n";
    echo "========================================================\n";

} catch (Exception $e) {
    echo "\n[ERROR] Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
