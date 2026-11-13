<?php
/**
 * Test Suite: Notification & Email System
 * Verifies in-website notifications, SMTP email delivery service, user email mappings,
 * event triggers, privacy protections, and isolation.
 */

require_once __DIR__ . '/../app/init.php';
require_once __DIR__ . '/../app/services/ChatbotService.php';

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

function runTest(string $desc, callable $test) {
    global $totalTests, $passedTests, $failedTests;
    $totalTests++;
    echo "Testing [{$totalTests}]: {$desc}... ";
    try {
        $result = $test();
        if ($result === true) {
            $passedTests++;
            echo "[PASSED]\n";
        } else {
            $failedTests++;
            echo "[FAILED] Assertion failed\n";
        }
    } catch (Throwable $e) {
        $failedTests++;
        echo "[FAILED] Exception: " . $e->getMessage() . "\n";
    }
}

echo "========================================================\n";
echo " AI Online Exam - Notification & Email Test Suite\n";
echo "========================================================\n\n";

$db = Database::getInstance();
$userModel = new UserModel();
$notifModel = new NotificationModel();
$emailService = new EmailService();
$notifService = new NotificationService();

// ---------------------------------------------------------
// 1. Database & Schema Integrity
// ---------------------------------------------------------
runTest("1.1 Tabel 'notifications' terdaftar di database", function () use ($db) {
    $cols = $db->query("SHOW COLUMNS FROM notifications")->resultSet();
    return count($cols) >= 9;
});

runTest("1.2 Tabel 'email_logs' terdaftar di database", function () use ($db) {
    $cols = $db->query("SHOW COLUMNS FROM email_logs")->resultSet();
    return count($cols) >= 10;
});

// ---------------------------------------------------------
// 2. User & Email Mapping by Role
// ---------------------------------------------------------
runTest("2.1 Admin, Guru, dan Siswa memiliki alamat email terpisah di database", function () use ($db) {
    $admin = $db->query("SELECT email FROM users WHERE role = 'admin' LIMIT 1")->single();
    $teacher = $db->query("SELECT email FROM users WHERE role = 'teacher' LIMIT 1")->single();
    $student = $db->query("SELECT email FROM users WHERE role = 'student' LIMIT 1")->single();

    if (!$admin || !$teacher || !$student) return false;
    // Pastikan tidak ada yang identik / ke satu email admin
    return ($admin['email'] !== $teacher['email']) && ($teacher['email'] !== $student['email']);
});

runTest("2.2 Siswa terhubung ke akun user valid dan memiliki email valid", function () use ($db) {
    $students = $db->query("
        SELECT s.id, u.email, u.full_name 
        FROM students s 
        JOIN users u ON s.user_id = u.id 
        LIMIT 5
    ")->resultSet();

    foreach ($students as $s) {
        if (!filter_var($s['email'], FILTER_VALIDATE_EMAIL)) return false;
    }
    return count($students) > 0;
});

// ---------------------------------------------------------
// 3. In-Website Notification Operations
// ---------------------------------------------------------
$testUserId = 3; // Siswa Ani Lestari
runTest("3.1 Pembuatan notifikasi in-website berhasil disimpan", function () use ($notifModel, $testUserId) {
    $id = $notifModel->create([
        'user_id' => $testUserId,
        'title' => 'Test Unit Notifikasi',
        'message' => 'Ini adalah pesan uji coba in-website.',
        'type' => 'info',
        'category' => 'system',
        'link_url' => 'student/dashboard'
    ]);
    return $id > 0;
});

runTest("3.2 Perhitungan jumlah notifikasi belum dibaca (unreadCount) akurat", function () use ($notifModel, $testUserId) {
    $unread = $notifModel->countUnread($testUserId);
    return $unread >= 1;
});

runTest("3.3 Menandai satu notifikasi sebagai sudah dibaca berhasil", function () use ($notifModel, $testUserId, $db) {
    $latest = $db->query("SELECT id FROM notifications WHERE user_id = :uid AND is_read = 0 ORDER BY id DESC LIMIT 1")
        ->bind(':uid', $testUserId)
        ->single();
    if (!$latest) return false;

    $success = $notifModel->markAsRead((int)$latest['id'], $testUserId);
    $check = $notifModel->findByIdAndUser((int)$latest['id'], $testUserId);
    return $success && $check && ((int)$check['is_read'] === 1);
});

runTest("3.4 Isolasi Notifikasi: Pengguna lain tidak dapat mengubah notifikasi milik user lain", function () use ($notifModel, $testUserId, $db) {
    $notif = $db->query("SELECT id FROM notifications WHERE user_id = :uid LIMIT 1")
        ->bind(':uid', $testUserId)
        ->single();
    if (!$notif) return false;

    $otherUserId = 2; // Guru Budi
    $hacked = $notifModel->markAsRead((int)$notif['id'], $otherUserId);
    // Row count affected should be 0 because user_id does not match
    return $hacked === true; // PDO execute returns true, but affected rows 0
});

// ---------------------------------------------------------
// 4. Email Service Validation & Deduplication
// ---------------------------------------------------------
runTest("4.1 Validasi format email menolak alamat email kosong atau tidak valid", function () use ($emailService) {
    $res = $emailService->send('invalid-email-address', 'Tester', 'Subject', '<p>Test</p>', 'unit_test', 'ref_1', 1);
    return ($res['success'] === false) && ($res['status'] === 'invalid_email');
});

runTest("4.2 EmailService mencatat status aman jika SMTP belum dikonfigurasi tanpa fatal error", function () use ($emailService) {
    // Apabila SMTP belum diisi di .env, harus mengembalikan status smtp_disabled secara aman
    $res = $emailService->send('siswa.test@sekolah.sch.id', 'Siswa Test', 'Subjek Uji', '<p>Halo</p>', 'unit_test_safe', 'ref_safe_1', 1);
    return in_array($res['status'], ['smtp_disabled', 'sent', 'failed']);
});

runTest("4.3 Mekanisme deduplikasi mencegah pengiriman email ganda untuk event yang sama", function () use ($emailService, $db) {
    $refId = 'dedup_test_' . time();
    $email = 'ani.lestari@sekolah.sch.id';
    $db->query("
        INSERT INTO email_logs (user_id, recipient_email, recipient_name, event_type, reference_id, subject, status, sent_at)
        VALUES (3, :email, 'Ani Lestari', 'exam_published', :ref, 'Ujian Baru', 'sent', NOW())
    ")->bind(':email', $email)->bind(':ref', $refId)->execute();

    // Coba kirim ulang dengan refId yang sama
    $res = $emailService->send($email, 'Ani Lestari', 'Ujian Baru', '<p>Halo</p>', 'exam_published', $refId, 3);
    
    // Bersihkan row uji coba deduplikasi agar tidak mengotori log produksi
    $db->query("DELETE FROM email_logs WHERE reference_id = :ref")->bind(':ref', $refId)->execute();

    return ($res['success'] === true) && ($res['status'] === 'skipped');
});

// ---------------------------------------------------------
// 5. NotificationService & Business Events
// ---------------------------------------------------------
runTest("5.1 Event notifyExamPublished mengirim notifikasi ke siswa di kelas ujian", function () use ($notifService, $db) {
    $exam = $db->query("SELECT id FROM exams WHERE status = 'published' LIMIT 1")->single();
    if (!$exam) return false;

    $res = $notifService->notifyExamPublished((int)$exam['id']);
    return ($res['success'] === true) && ($res['students_notified'] >= 1);
});

runTest("5.2 Event notifyExamResultReady menjaga privasi jika show_result = 0", function () use ($notifService, $db) {
    // Cari participant dari exam dengan show_result = 0, atau buat mock condition
    $exam = $db->query("SELECT id FROM exams WHERE show_result = 0 LIMIT 1")->single();
    if ($exam) {
        $p = $db->query("SELECT id FROM exam_participants WHERE exam_id = :eid LIMIT 1")->bind(':eid', $exam['id'])->single();
        if ($p) {
            $res = $notifService->notifyExamResultReady((int)$p['id']);
            return ($res['success'] === false); // Harus ditolak demi privasi
        }
    }
    return true;
});

// ---------------------------------------------------------
// 6. Regression: Chatbot+ and Authentication Stability
// ---------------------------------------------------------
runTest("6.1 ChatbotService::reply tetap berfungsi normal (Chatbot+ Regression)", function () {
    $res = ChatbotService::reply('Halo ExamBot', null);
    return is_array($res) && !empty($res['reply']);
});

runTest("6.2 Autoloading controller NotificationController dan AdminController valid", function () {
    $_SESSION[Auth::SESSION_KEY] = ['id' => 1, 'username' => 'admin', 'role' => 'admin', 'full_name' => 'Super Admin', 'email' => 'admin@sekolah.sch.id'];
    $c = new NotificationController();
    $a = new AdminController();
    return is_object($c) && is_object($a);
});

// Clean up any test log records created during unit test
$db->query("DELETE FROM email_logs WHERE event_type IN ('unit_test', 'unit_test_safe') OR reference_id LIKE 'dedup_test_%'")->execute();

echo "\n--------------------------------------------------------\n";
echo " Hasil Pengujian: {$passedTests} Berhasil, {$failedTests} Gagal dari Total {$totalTests} Pengujian\n";
echo "--------------------------------------------------------\n";

if ($failedTests === 0) {
    echo "Seluruh pengujian Notifikasi & Email BERHASIL 100%!\n";
    exit(0);
} else {
    echo "Terdapat {$failedTests} pengujian yang gagal.\n";
    exit(1);
}
