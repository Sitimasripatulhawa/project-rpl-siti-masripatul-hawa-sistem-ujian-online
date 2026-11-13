<?php
/**
 * Test Suite: Database Real-time Auto-Update & Role Scoped Access
 */

define('FORCE_LOCAL_CHATBOT', true);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/services/ChatbotService.php';

$passCount = 0;
$failCount = 0;

function assertCheck(string $desc, bool $condition): void
{
    global $passCount, $failCount;
    echo "Testing: {$desc}... ";
    if ($condition) {
        echo "[PASSED]\n";
        $passCount++;
    } else {
        echo "[FAILED]\n";
        $failCount++;
    }
}

echo "====================================================================\n";
echo " Test Suite: Database Auto-Update & Role Scoped Access\n";
echo "====================================================================\n\n";

$db = Database::getInstance();

// ----------------------------------------------------
// 1. DYNAMIC DATABASE UPDATE: MODIFIKASI SOAL DI DATABASE
// ----------------------------------------------------
echo "--- 1. Dynamic Database Auto-Update Tests ---\n";

// Buat soal baru untuk pengujian dynamic edit
$teacherRow = $db->query("SELECT id FROM teachers LIMIT 1")->single();
$subjectRow = $db->query("SELECT id FROM subjects LIMIT 1")->single();
$teacherId = $teacherRow['id'] ?? 1;
$subjectId = $subjectRow['id'] ?? 1;

$db->query("
    INSERT INTO questions (subject_id, teacher_id, question_text, question_type, difficulty, default_score, explanation)
    VALUES (:sid, :tid, 'Soal Uji Dinamis Versi Awal: Berapakah 10 + 15?', 'multiple_choice', 'easy', 1.00, 'Penjumlahan 10 + 15 menghasilkan 25 secara tepat.')
")->bind(':sid', $subjectId)->bind(':tid', $teacherId)->execute();

$testQId = (int)$db->lastInsertId();

// Tambah opsi
$db->query("INSERT INTO question_options (question_id, option_label, option_text, is_correct) VALUES (:qid, 'A', '25', 1)")->bind(':qid', $testQId)->execute();
$db->query("INSERT INTO question_options (question_id, option_label, option_text, is_correct) VALUES (:qid, 'B', '30', 0)")->bind(':qid', $testQId)->execute();

// Query Chatbot untuk versi awal
$resInitial = ChatbotService::reply("Bahas soal nomor {$testQId}");
assertCheck("1.1 Chatbot merespon soal baru dengan teks dan penjelasan versi awal",
    $resInitial['status'] === 'success' &&
    strpos($resInitial['reply'], 'Soal Uji Dinamis Versi Awal') !== false &&
    strpos($resInitial['reply'], 'Penjumlahan 10 + 15 menghasilkan 25') !== false &&
    strpos($resInitial['reply'], '25') !== false
);

// Sekarang simulasikan guru/admin MENGEDIT soal di database:
$db->query("
    UPDATE questions 
    SET question_text = 'Soal Uji Dinamis VERSI TERBARU: Berapakah 50 + 75?',
        explanation = 'Penjumlahan terbaru 50 + 75 menghasilkan 125 berdasarkan pembaruan guru.'
    WHERE id = :qid
")->bind(':qid', $testQId)->execute();

// Update juga opsi jawaban di database
$db->query("UPDATE question_options SET option_text = '125' WHERE question_id = :qid AND option_label = 'A'")->bind(':qid', $testQId)->execute();

// Query Chatbot kembali untuk soal yang sama
$resUpdated = ChatbotService::reply("Bahas soal nomor {$testQId}");
assertCheck("1.2 Chatbot OTOMATIS MENGIKUTI data terbaru setelah soal diedit di database",
    $resUpdated['status'] === 'success' &&
    strpos($resUpdated['reply'], 'Soal Uji Dinamis VERSI TERBARU') !== false &&
    strpos($resUpdated['reply'], '50 + 75') !== false &&
    strpos($resUpdated['reply'], '125') !== false &&
    strpos($resUpdated['reply'], 'Penjumlahan terbaru 50 + 75 menghasilkan 125') !== false
);

// Hapus fixture soal uji
$db->query("DELETE FROM question_options WHERE question_id = :qid")->bind(':qid', $testQId)->execute();
$db->query("DELETE FROM questions WHERE id = :qid")->bind(':qid', $testQId)->execute();


// ----------------------------------------------------
// 2. RUANG LINGKUP USER (ROLE SCOPED ACCESS)
// ----------------------------------------------------
echo "\n--- 2. Role Scoped Access Tests ---\n";

// 2.1 Guest Scope: Mengarahkan login bila tanya nilai/ujian pribadi
$guestUser = null;
$guestRes = ChatbotService::reply("Berapa nilai ujian saya yang lalu?", $guestUser);
assertCheck("2.1 Guest dibatasi dan diarahkan login ketika menanyakan nilai/jadwal pribadi",
    $guestRes['status'] === 'success' &&
    strpos($guestRes['reply'], 'Akses Terbatas') !== false &&
    isset($guestRes['action']['url']) &&
    strpos($guestRes['action']['url'], '/auth/login') !== false
);

// 2.2 Student Scope: Siswa menerima jadwal/hasil ujian sesuai kelas & dirinya
$studentUserRow = $db->query("
    SELECT u.id, u.username, u.full_name, u.role 
    FROM users u 
    WHERE u.role = 'student' 
    LIMIT 1
")->single();

$studentRes = ChatbotService::reply("Jadwal ujian saya yang tersedia apa saja?", $studentUserRow);
assertCheck("2.2 Siswa menerima data jadwal/hasil ujian miliknya secara scoped",
    $studentRes['status'] === 'success' &&
    (strpos($studentRes['reply'], 'Ringkasan Jadwal') !== false || strpos($studentRes['reply'], 'Informasi Ujian') !== false)
);

// 2.3 Teacher Scope: Guru menerima rekapitulasi ujian yang diampu
$teacherUserRow = $db->query("
    SELECT u.id, u.username, u.full_name, u.role 
    FROM users u 
    WHERE u.role = 'teacher' 
    LIMIT 1
")->single();

$teacherExamRes = ChatbotService::reply("Rekapitulasi ujian saya bagaimana?", $teacherUserRow);
assertCheck("2.3 Guru menerima statistik ujian yang diampunya saja",
    $teacherExamRes['status'] === 'success' &&
    (strpos($teacherExamRes['reply'], 'Rekapitulasi Ujian yang Anda Ampu') !== false || strpos($teacherExamRes['reply'], 'Dashboard Guru') !== false)
);

// 2.4 Teacher Scope: Guru menanyakan bank soal miliknya
$teacherBankRes = ChatbotService::reply("Bank soal saya ada apa saja?", $teacherUserRow);
assertCheck("2.4 Guru menerima daftar bank soal yang dikelolanya dari database",
    $teacherBankRes['status'] === 'success' &&
    (strpos($teacherBankRes['reply'], 'Bank Soal yang Anda Kelola') !== false || strpos($teacherBankRes['reply'], 'Bank Soal Anda') !== false)
);

// 2.5 Admin Scope: Administrator menerima rekapitulasi data master
$adminUserRow = $db->query("
    SELECT u.id, u.username, u.full_name, u.role 
    FROM users u 
    WHERE u.role = 'admin' 
    LIMIT 1
")->single();

$adminRes = ChatbotService::reply("Ringkasan data pengguna & kelas", $adminUserRow);
assertCheck("2.5 Admin menerima ringkasan data master sistem (siswa, guru, kelas, ujian)",
    $adminRes['status'] === 'success' &&
    strpos($adminRes['reply'], 'Ringkasan Master Data') !== false &&
    strpos($adminRes['reply'], 'Pengguna Terdaftar') !== false &&
    strpos($adminRes['reply'], 'Rombongan Belajar') !== false
);

// ----------------------------------------------------
// 3. QUESTION EXPLAINER (TUTOR SOAL): RESPON DINAMIS SESUAI DATABASE
// ----------------------------------------------------
echo "\n--- 3. Question Explainer (Tutor Soal) Database Grounding ---\n";

$customExplainerData = [
    'question_id' => 999,
    'question_text' => 'Berapakah 12 x 12?',
    'question_type' => 'multiple_choice',
    'options' => [
        ['option_label' => 'A', 'option_text' => '144', 'is_correct' => 1],
        ['option_label' => 'B', 'option_text' => '124', 'is_correct' => 0]
    ],
    'student_answer_label' => 'B. 124',
    'correct_answer_label' => 'A. 144',
    'is_correct' => 0,
    'explanation' => 'Perkalian 12 x 12 = 144 berdasarkan tabel perkalian matematika dasar.'
];

$explainerWrong = ChatbotService::explainQuestion($customExplainerData, "Mengapa jawaban saya salah?");
assertCheck("3.1 Question Explainer mengevaluasi jawaban salah dengan kunci dan penjelasan DB",
    $explainerWrong['status'] === 'success' &&
    strpos($explainerWrong['reply'], 'Evaluasi Jawaban Siswa') !== false &&
    strpos($explainerWrong['reply'], '144') !== false &&
    strpos($explainerWrong['reply'], 'Perkalian 12 x 12 = 144') !== false
);

$explainerStep = ChatbotService::explainQuestion($customExplainerData, "Bagaimana langkah penyelesaiannya?");
assertCheck("3.2 Question Explainer merinci langkah penyelesaian sesuai penjelasan DB",
    $explainerStep['status'] === 'success' &&
    strpos($explainerStep['reply'], 'Langkah Sistematis Penyelesaian Soal') !== false &&
    strpos($explainerStep['reply'], 'Perkalian 12 x 12 = 144') !== false
);

echo "\n--------------------------------------------------------------------\n";
echo " Hasil Pengujian: {$passCount} Berhasil, {$failCount} Gagal\n";
echo "====================================================================\n";

if ($failCount === 0) {
    echo "Semua pengujian Database Auto-Update & Role Scoped Access BERHASIL 100%!\n";
    exit(0);
} else {
    exit(1);
}
