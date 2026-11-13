<?php
/**
 * Test Suite: Chatbot Database Integration & 12 Target Queries
 * Verifies that ExamBot can connect to the database and accurately, concisely answer:
 * 1. ada berapa kelas
 * 2. ada berapa guru
 * 3. ada berapa siswa
 * 4. ada berapa ujian
 * 5. ada berapa mata pelajaran
 * 6. ada berapa user
 * 7. siapa guru matematika
 * 8. siapa siswa yang telah mengikuti ujian
 * 9. ada berapa siswa yang telah submit ujian
 * 10. berapa soal di setiap mata pelajaran
 * 11. nis setiap siswa
 * 12. jumlah siswa di setiap kelas nya
 */

define('FORCE_LOCAL_CHATBOT', true);
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/services/ChatbotService.php';

echo "====================================================================\n";
echo " Test Suite: Chatbot Database Connectivity & 12 Target Queries\n";
echo "====================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertCheck(string $desc, bool $condition, ?array $details = null) {
    global $passCount, $failCount;
    echo "Testing: {$desc}... ";
    if ($condition) {
        echo "[PASSED]\n";
        $passCount++;
    } else {
        echo "[FAILED]\n";
        $failCount++;
        if ($details !== null) {
            echo "   Detail: " . json_encode($details, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
        }
    }
}

// 1. Ada berapa kelas
$q1 = ChatbotService::reply("ada berapa kelas");
assertCheck("1.1 'ada berapa kelas' dijawab dengan jumlah kelas dan rincian kelas",
    $q1['status'] === 'success' &&
    $q1['source'] === 'database_info' &&
    strpos($q1['reply'], '2 kelas') !== false &&
    strpos($q1['reply'], 'XII MIPA 1') !== false &&
    strpos($q1['reply'], 'XII MIPA 2') !== false,
    $q1
);

// 2. Ada berapa guru
$q2 = ChatbotService::reply("ada berapa guru");
assertCheck("2.1 'ada berapa guru' dijawab dengan jumlah guru dan daftar guru",
    $q2['status'] === 'success' &&
    $q2['source'] === 'database_info' &&
    strpos($q2['reply'], '4 guru') !== false &&
    strpos($q2['reply'], 'Budi Santoso') !== false &&
    strpos($q2['reply'], 'Tika') !== false &&
    strpos($q2['reply'], 'Andi Pratama') !== false &&
    strpos($q2['reply'], 'Ayu') !== false,
    $q2
);

// 3. Ada berapa siswa
$q3 = ChatbotService::reply("ada berapa siswa");
assertCheck("3.1 'ada berapa siswa' dijawab dengan jumlah siswa dan nama-nama siswa",
    $q3['status'] === 'success' &&
    $q3['source'] === 'database_info' &&
    strpos($q3['reply'], '5 siswa') !== false &&
    strpos($q3['reply'], 'Ani Lestari') !== false &&
    strpos($q3['reply'], 'Bimo Nugroho') !== false &&
    strpos($q3['reply'], 'Citra Dewi') !== false,
    $q3
);

// 4. Ada berapa ujian
$q4 = ChatbotService::reply("ada berapa ujian");
assertCheck("4.1 'ada berapa ujian' dijawab dengan jumlah sesi ujian di database",
    $q4['status'] === 'success' &&
    $q4['source'] === 'database_info' &&
    strpos($q4['reply'], 'sesi ujian') !== false &&
    strpos($q4['reply'], 'database') !== false,
    $q4
);

// 5. Ada berapa mata pelajaran
$q5 = ChatbotService::reply("ada berapa mata pelajaran");
assertCheck("5.1 'ada berapa mata pelajaran' dijawab dengan jumlah dan nama mapel",
    $q5['status'] === 'success' &&
    $q5['source'] === 'database_info' &&
    strpos($q5['reply'], '4 mata pelajaran') !== false &&
    strpos($q5['reply'], 'Matematika Wajib') !== false &&
    strpos($q5['reply'], 'B. Inggris') !== false &&
    strpos($q5['reply'], 'Biologi') !== false,
    $q5
);

// 6. Ada berapa user
$q6 = ChatbotService::reply("ada berapa user");
assertCheck("6.1 'ada berapa user' dijawab dengan total user dan breakdown role",
    $q6['status'] === 'success' &&
    $q6['source'] === 'database_info' &&
    strpos($q6['reply'], '10 pengguna') !== false &&
    strpos($q6['reply'], 'Siswa') !== false &&
    strpos($q6['reply'], 'Guru') !== false &&
    strpos($q6['reply'], 'Admin') !== false,
    $q6
);

// 7. Siapa guru matematika
$q7 = ChatbotService::reply("siapa guru matematika");
assertCheck("7.1 'siapa guru matematika' dijawab dengan Budi Santoso dan bidangnya",
    $q7['status'] === 'success' &&
    $q7['source'] === 'database_info' &&
    strpos($q7['reply'], 'Budi Santoso') !== false &&
    strpos($q7['reply'], 'Matematika') !== false,
    $q7
);

// 8. Siapa siswa yang telah mengikuti ujian
$q8 = ChatbotService::reply("siapa siswa yang telah mengikuti ujian");
assertCheck("8.1 'siapa siswa yang telah mengikuti ujian' dijawab dengan daftar peserta ujian aktif",
    $q8['status'] === 'success' &&
    $q8['source'] === 'database_info' &&
    strpos($q8['reply'], 'Ani Lestari') !== false &&
    strpos($q8['reply'], 'Bimo Nugroho') !== false &&
    strpos($q8['reply'], 'Citra Dewi') !== false,
    $q8
);

// 9. Ada berapa siswa yang telah submit ujian
$q9 = ChatbotService::reply("ada berapa siswa yang telah submit ujian");
assertCheck("9.1 'ada berapa siswa yang telah submit ujian' dijawab dengan 3 siswa submitted",
    $q9['status'] === 'success' &&
    $q9['source'] === 'database_info' &&
    strpos($q9['reply'], '3 siswa') !== false &&
    strpos($q9['reply'], 'Ani Lestari') !== false &&
    strpos($q9['reply'], 'submit') !== false,
    $q9
);

// 10. Berapa soal di setiap mata pelajaran
$q10 = ChatbotService::reply("berapa soal di setiap mata pelajaran");
assertCheck("10.1 'berapa soal di setiap mata pelajaran' dijawab dengan breakdown per mapel",
    $q10['status'] === 'success' &&
    $q10['source'] === 'database_info' &&
    strpos($q10['reply'], 'Matematika Wajib') !== false &&
    strpos($q10['reply'], '7') !== false &&
    strpos($q10['reply'], 'B. Inggris') !== false &&
    strpos($q10['reply'], 'Biologi') !== false,
    $q10
);

// 11. NIS setiap siswa
$q11 = ChatbotService::reply("nis setiap siswa");
assertCheck("11.1 'nis setiap siswa' dijawab dengan daftar NIS semua siswa",
    $q11['status'] === 'success' &&
    $q11['source'] === 'database_info' &&
    strpos($q11['reply'], '20261001') !== false &&
    strpos($q11['reply'], '20261002') !== false &&
    strpos($q11['reply'], '20261003') !== false &&
    strpos($q11['reply'], '0898342') !== false &&
    strpos($q11['reply'], '2387335') !== false,
    $q11
);

// 12. Jumlah siswa di setiap kelas nya
$q12 = ChatbotService::reply("jumlah siswa di setiap kelas nya");
assertCheck("12.1 'jumlah siswa di setiap kelas nya' dijawab dengan breakdown kelas dan siswa",
    $q12['status'] === 'success' &&
    $q12['source'] === 'database_info' &&
    strpos($q12['reply'], 'XII MIPA 1') !== false &&
    strpos($q12['reply'], '3 siswa') !== false &&
    strpos($q12['reply'], 'XII MIPA 2') !== false &&
    strpos($q12['reply'], '2 siswa') !== false,
    $q12
);

echo "\n--------------------------------------------------------------------\n";
echo " Hasil Pengujian: {$passCount} Berhasil, {$failCount} Gagal\n";
echo "====================================================================\n";

if ($failCount === 0) {
    echo "Semua 12 pertanyaan basis data Chatbot BERHASIL 100%!\n";
    exit(0);
} else {
    echo "Ada {$failCount} pengujian yang gagal.\n";
    exit(1);
}
