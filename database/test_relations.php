<?php
/**
 * Relationship Integrity Verification Script
 */
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';

$db = Database::getInstance();

echo "=== 1. Test Guru, Mapel, dan Bank Soal ===\n";
$questions = $db->query("
    SELECT 
        q.id,
        q.question_text,
        q.difficulty,
        q.default_score,
        s.name AS subject_name,
        u.full_name AS teacher_name,
        COUNT(o.id) AS total_options
    FROM questions q
    JOIN subjects s ON q.subject_id = s.id
    JOIN teachers t ON q.teacher_id = t.id
    JOIN users u ON t.user_id = u.id
    LEFT JOIN question_options o ON q.id = o.question_id
    GROUP BY q.id
")->resultSet();

foreach ($questions as $q) {
    echo "Soal #{$q['id']}: [{$q['subject_name']}] {$q['question_text']}\n";
    echo "  Guru: {$q['teacher_name']} | Pilihan: {$q['total_options']} Opsi | Bobot: {$q['default_score']}\n";
}

echo "\n=== 2. Test Siswa dan Kelas ===\n";
$students = $db->query("
    SELECT 
        s.id,
        s.nis,
        s.nisn,
        s.gender,
        u.full_name,
        u.email,
        c.name AS class_name,
        c.academic_year
    FROM students s
    JOIN users u ON s.user_id = u.id
    JOIN classes c ON s.class_id = c.id
")->resultSet();

foreach ($students as $s) {
    echo "Siswa: {$s['full_name']} (NIS: {$s['nis']}) | Kelas: {$s['class_name']} ({$s['academic_year']}) | Email: {$s['email']}\n";
}

echo "\n=== 3. Test Foreign Key Cascades & Constraints ===\n";
$fks = $db->query("
    SELECT 
        TABLE_NAME, 
        COLUMN_NAME, 
        CONSTRAINT_NAME, 
        REFERENCED_TABLE_NAME, 
        REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND REFERENCED_TABLE_NAME IS NOT NULL
    ORDER BY TABLE_NAME, COLUMN_NAME
")->resultSet();

echo "Total Foreign Keys Terdaftar: " . count($fks) . "\n";
foreach ($fks as $fk) {
    echo "  {$fk['TABLE_NAME']}.{$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']} ({$fk['CONSTRAINT_NAME']})\n";
}
