<?php
/**
 * Migration: Notifications & Email Logs System
 * Safe, idempotent migration that creates notifications and email_logs tables.
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';

echo "=== Running Migration: Notifications & Email Logs ===\n";

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // 1. Create notifications table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            type ENUM('info', 'success', 'warning', 'danger') NOT NULL DEFAULT 'info',
            category VARCHAR(50) NOT NULL DEFAULT 'general',
            link_url VARCHAR(255) NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            read_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_notifications_user_read (user_id, is_read),
            INDEX idx_notifications_created (created_at),
            CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo " [+] Verified/Created table 'notifications'\n";

    // 2. Create email_logs table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS email_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NULL,
            recipient_email VARCHAR(150) NOT NULL,
            recipient_name VARCHAR(100) NULL,
            event_type VARCHAR(50) NOT NULL,
            reference_id VARCHAR(50) NULL,
            subject VARCHAR(255) NOT NULL,
            status ENUM('pending', 'sent', 'failed', 'invalid_email', 'smtp_disabled') NOT NULL DEFAULT 'pending',
            attempt_count INT NOT NULL DEFAULT 1,
            error_message TEXT NULL,
            sent_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email_logs_user (user_id),
            INDEX idx_email_logs_dedup (event_type, reference_id, recipient_email),
            INDEX idx_email_logs_status (status),
            CONSTRAINT fk_email_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo " [+] Verified/Created table 'email_logs'\n";

    echo "=== Migration Notifications Finished Successfully! ===\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
