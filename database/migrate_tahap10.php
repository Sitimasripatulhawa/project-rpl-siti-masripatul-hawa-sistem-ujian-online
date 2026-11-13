<?php
/**
 * Migration for Tahap 10: AI Proctoring MVP
 * Updates proctoring_events table with exam_id, student_id, severity, and flexible event_type.
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';

echo "=== Running Migration for Tahap 10: AI Proctoring ===\n";

try {
    $db = Database::getInstance();
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $cols = $pdo->query("SHOW COLUMNS FROM proctoring_events")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('exam_id', $cols)) {
        $pdo->exec("ALTER TABLE proctoring_events ADD COLUMN exam_id BIGINT UNSIGNED NULL AFTER id");
        echo " [+] Added column 'exam_id'\n";
    } else {
        echo " [.] Column 'exam_id' already exists\n";
    }

    if (!in_array('student_id', $cols)) {
        $pdo->exec("ALTER TABLE proctoring_events ADD COLUMN student_id BIGINT UNSIGNED NULL AFTER exam_id");
        echo " [+] Added column 'student_id'\n";
    } else {
        echo " [.] Column 'student_id' already exists\n";
    }

    if (!in_array('severity', $cols)) {
        $pdo->exec("ALTER TABLE proctoring_events ADD COLUMN severity ENUM('INFO', 'WARNING', 'CRITICAL') NOT NULL DEFAULT 'WARNING' AFTER event_type");
        echo " [+] Added column 'severity'\n";
    } else {
        echo " [.] Column 'severity' already exists\n";
    }

    // Modify event_type to VARCHAR(50)
    $pdo->exec("ALTER TABLE proctoring_events MODIFY COLUMN event_type VARCHAR(50) NOT NULL");
    echo " [+] Modified column 'event_type' to VARCHAR(50)\n";

    // Add indexes
    try {
        $pdo->exec("ALTER TABLE proctoring_events ADD INDEX idx_pe_exam_student (exam_id, student_id)");
        echo " [+] Added index 'idx_pe_exam_student'\n";
    } catch (Exception $e) {
        echo " [.] Index 'idx_pe_exam_student' already present\n";
    }

    try {
        $pdo->exec("ALTER TABLE proctoring_events ADD INDEX idx_pe_severity (severity)");
        echo " [+] Added index 'idx_pe_severity'\n";
    } catch (Exception $e) {
        echo " [.] Index 'idx_pe_severity' already present\n";
    }

    // Ensure proctoring uploads directory exists
    $uploadDir = __DIR__ . '/../public/uploads/proctoring';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        echo " [+] Created directory public/uploads/proctoring\n";
    }

    echo "=== Migration Finished Successfully! ===\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
