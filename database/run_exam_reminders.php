<?php
/**
 * Exam Reminder Scheduler CLI Script
 * AI Online Exam & Proctoring System
 *
 * Can be run every 5-15 minutes via Windows Task Scheduler or crontab:
 * Contoh Windows CLI:
 *   C:\xampp\php\php.exe c:\xampp\htdocs\Project2\database\run_exam_reminders.php
 *
 * Contoh Crontab Linux:
 *   * /10 * * * * php /var/www/html/Project2/database/run_exam_reminders.php >> /var/log/exam_reminders.log 2>&1
 */

if (php_sapi_name() !== 'cli') {
    die("Script ini hanya dapat dijalankan melalui antarmuka Command Line (CLI).\n");
}

require_once __DIR__ . '/../app/init.php';
require_once __DIR__ . '/../app/services/EmailService.php';
require_once __DIR__ . '/../app/services/NotificationService.php';

echo "========================================================\n";
echo " AI Online Exam - Exam Reminder Scheduler Runner\n";
echo " Waktu Server: " . date('Y-m-d H:i:s T') . "\n";
echo "========================================================\n";

$db = Database::getInstance();
$notifService = new NotificationService();

// Cari ujian yang berstatus 'published' dan akan dimulai dalam kurun waktu 0 sampai 35 menit ke depan
$now = date('Y-m-d H:i:s');
$windowEnd = date('Y-m-d H:i:s', strtotime('+35 minutes'));

$sql = "SELECT id, title, start_time, class_id 
        FROM exams 
        WHERE status = 'published' 
          AND start_time BETWEEN :now AND :windowEnd";

$upcomingExams = $db->query($sql)
    ->bind(':now', $now)
    ->bind(':windowEnd', $windowEnd)
    ->resultSet();

if (empty($upcomingExams)) {
    echo "[INFO] Tidak ada sesi ujian yang akan dimulai dalam 35 menit ke depan.\n";
    echo "Selesai.\n";
    exit(0);
}

echo "[INFO] Ditemukan " . count($upcomingExams) . " sesi ujian yang akan segera berlangsung.\n";

foreach ($upcomingExams as $exam) {
    $diffMinutes = round((strtotime($exam['start_time']) - time()) / 60);
    echo "  -> Memproses pengingat ujian #{$exam['id']} '{$exam['title']}' (Mulai dalam {$diffMinutes} menit)... ";

    $res = $notifService->notifyExamReminder((int)$exam['id'], (int)$diffMinutes);
    if ($res['success']) {
        echo "SUKSES (" . ($res['count'] ?? 0) . " peserta diingatkan)\n";
    } else {
        echo "GAGAL: " . ($res['message'] ?? 'Error') . "\n";
    }
}

echo "========================================================\n";
echo " Pengingat ujian selesai diproses secara aman.\n";
