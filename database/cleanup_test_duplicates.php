<?php
/**
 * Safe Deduplication & Test Artifacts Cleanup Script
 * Cleans up redundant duplicate rows created during test runs while preserving
 * authentic notifications and unique event records.
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';

echo "========================================================\n";
echo " AI Online Exam - Safe Deduplication & Log Cleanup\n";
echo "========================================================\n\n";

$db = Database::getInstance();

// 1. Audit before cleanup
$beforeEmailLogs = (int)$db->query("SELECT COUNT(*) FROM email_logs")->fetchColumn();
$beforeNotifs = (int)$db->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
echo "Kondisi Awal:\n";
echo " - Total baris di email_logs: {$beforeEmailLogs}\n";
echo " - Total baris di notifications: {$beforeNotifs}\n\n";

// 2. Remove fake mock test records (unit_test, unit_test_safe, dedup_test_%)
$deletedMocks = $db->query("
    DELETE FROM email_logs 
    WHERE event_type IN ('unit_test', 'unit_test_safe') 
       OR reference_id LIKE 'dedup_test_%'
")->execute();
echo "[+] Membersihkan data uji coba langsung (unit_test & dedup_test mock)\n";

// 3. Deduplicate redundant identical logs for the same (event_type, reference_id, recipient_email)
// Keeps the record with the MAX(id) and removes older duplicates
$duplicateGroups = $db->query("
    SELECT event_type, reference_id, recipient_email, COUNT(*) as cnt, MAX(id) as keep_id 
    FROM email_logs 
    WHERE reference_id IS NOT NULL 
    GROUP BY event_type, reference_id, recipient_email 
    HAVING cnt > 1
")->resultSet();

$dedupEmailLogsCount = 0;
foreach ($duplicateGroups as $group) {
    $event = $group['event_type'];
    $ref = $group['reference_id'];
    $email = $group['recipient_email'];
    $keepId = (int)$group['keep_id'];

    $db->query("
        DELETE FROM email_logs 
        WHERE event_type = :event 
          AND reference_id = :ref 
          AND recipient_email = :email 
          AND id != :keep_id
    ")->bind(':event', $event)
      ->bind(':ref', $ref)
      ->bind(':email', $email)
      ->bind(':keep_id', $keepId)
      ->execute();

    $removed = ((int)$group['cnt']) - 1;
    $dedupEmailLogsCount += $removed;
    echo " [+] Deduplikasi event '{$event}' ref: '{$ref}' penerima: '{$email}' (dihapus {$removed} baris duplikat, disimpan ID #{$keepId})\n";
}

// 4. Deduplicate notifications table (keep MAX(id) for identical user_id, title, category)
$notifDuplicates = $db->query("
    SELECT user_id, title, category, COUNT(*) as cnt, MAX(id) as keep_id 
    FROM notifications 
    GROUP BY user_id, title, category 
    HAVING cnt > 1
")->resultSet();

$dedupNotifsCount = 0;
foreach ($notifDuplicates as $nGroup) {
    $uid = (int)$nGroup['user_id'];
    $title = $nGroup['title'];
    $cat = $nGroup['category'];
    $keepId = (int)$nGroup['keep_id'];

    $db->query("
        DELETE FROM notifications 
        WHERE user_id = :uid 
          AND title = :title 
          AND category = :cat 
          AND id != :keep_id
    ")->bind(':uid', $uid)
      ->bind(':title', $title)
      ->bind(':cat', $cat)
      ->bind(':keep_id', $keepId)
      ->execute();

    $removed = ((int)$nGroup['cnt']) - 1;
    $dedupNotifsCount += $removed;
    echo " [+] Deduplikasi notifikasi user #{$uid} '{$title}' (dihapus {$removed} duplikat, disimpan ID #{$keepId})\n";
}

// 5. Clean mock test in notifications if title is 'Test Unit Notifikasi'
$db->query("DELETE FROM notifications WHERE title = 'Test Unit Notifikasi'")->execute();

// 6. Audit after cleanup
$afterEmailLogs = (int)$db->query("SELECT COUNT(*) FROM email_logs")->fetchColumn();
$afterNotifs = (int)$db->query("SELECT COUNT(*) FROM notifications")->fetchColumn();

echo "\n========================================================\n";
echo " Ringkasan Pembersihan & Deduplikasi:\n";
echo " - email_logs: berkurang dari {$beforeEmailLogs} menjadi {$afterEmailLogs} baris\n";
echo " - notifications: berkurang dari {$beforeNotifs} menjadi {$afterNotifs} baris\n";
echo " - Seluruh riwayat unik dan valid tetap dipertahankan!\n";
echo "========================================================\n";
