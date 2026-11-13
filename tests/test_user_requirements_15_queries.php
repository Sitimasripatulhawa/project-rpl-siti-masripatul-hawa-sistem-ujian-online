<?php
/**
 * Test Suite: 15 Target Queries & Guardrails Requested by User
 */

define('FORCE_LOCAL_CHATBOT', true);
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/services/ChatbotService.php';

echo "====================================================================\n";
echo " Test Suite: 15 Target User Queries & Guardrails Verification\n";
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

// 1. Siapa anda
$r1 = ChatbotService::reply("siapa anda");
assertCheck("1. 'siapa anda' dijawab dengan identitas ExamBot secara ringkas",
    $r1['status'] === 'success' &&
    strpos($r1['reply'], 'ExamBot') !== false &&
    strpos($r1['reply'], 'asisten AI resmi') !== false,
    $r1
);

// 2. Buatkan coding untuk menuliskan nama anda (Harus ditolak!)
$r2 = ChatbotService::reply("buatkan coding untuk menuliskan nama anda");
assertCheck("2. 'buatkan coding untuk menuliskan nama anda' DITOLAK (Guardrail)",
    $r2['status'] === 'success' &&
    $r2['source'] === 'guardrail' &&
    (strpos($r2['reply'], 'Di Luar Ruang Lingkup') !== false || strpos($r2['reply'], 'tidak melayani') !== false),
    $r2
);

// 2b. Buatkan coding python untuk menuliskan nama anda (Harus ditolak!)
$r2b = ChatbotService::reply("buatkan coding python untuk menuliskan nama anda");
assertCheck("2b. 'buatkan coding python untuk menuliskan nama anda' DITOLAK (Guardrail)",
    $r2b['status'] === 'success' &&
    $r2b['source'] === 'guardrail' &&
    (strpos($r2b['reply'], 'Di Luar Ruang Lingkup') !== false || strpos($r2b['reply'], 'tidak melayani') !== false),
    $r2b
);

// 3. Siapa saya (Guest mode)
$r3_guest = ChatbotService::reply("siapa saya", null);
assertCheck("3a. 'siapa saya' (Guest) menginfokan belum login / Tamu",
    $r3_guest['status'] === 'success' &&
    (strpos($r3_guest['reply'], 'Tamu') !== false || strpos($r3_guest['reply'], 'Guest') !== false),
    $r3_guest
);

// 3b. Siapa saya (Siswa Ani Lestari)
$userAni = ['id' => 3, 'full_name' => 'Ani Lestari', 'role' => 'student'];
$r3_student = ChatbotService::reply("siapa saya", $userAni);
assertCheck("3b. 'siapa saya' (Siswa) menampilkan nama Ani Lestari, NIS, dan Kelas",
    $r3_student['status'] === 'success' &&
    strpos($r3_student['reply'], 'Ani Lestari') !== false &&
    strpos($r3_student['reply'], '20261001') !== false &&
    strpos($r3_student['reply'], 'XII MIPA 1') !== false,
    $r3_student
);

// 3c. Siapa saya (Guru Budi Santoso)
$userBudi = ['id' => 2, 'full_name' => 'Budi Santoso, M.Pd', 'role' => 'teacher'];
$r3_teacher = ChatbotService::reply("siapa saya", $userBudi);
assertCheck("3c. 'siapa saya' (Guru) menampilkan Budi Santoso, NIP, dan Bidang",
    $r3_teacher['status'] === 'success' &&
    strpos($r3_teacher['reply'], 'Budi Santoso') !== false &&
    strpos($r3_teacher['reply'], 'Matematika') !== false,
    $r3_teacher
);

// 4. Ada berapa kelas
$r4 = ChatbotService::reply("ada berapa kelas");
assertCheck("4. 'ada berapa kelas' dijawab dengan 2 kelas dan nama kelas",
    $r4['status'] === 'success' &&
    $r4['source'] === 'database_info' &&
    strpos($r4['reply'], '2 kelas') !== false &&
    strpos($r4['reply'], 'XII MIPA 1') !== false &&
    strpos($r4['reply'], 'XII MIPA 2') !== false,
    $r4
);

// 5. Ada berapa guru
$r5 = ChatbotService::reply("ada berapa guru");
assertCheck("5. 'ada berapa guru' dijawab dengan 4 guru dan daftar guru",
    $r5['status'] === 'success' &&
    $r5['source'] === 'database_info' &&
    strpos($r5['reply'], '4 guru') !== false &&
    strpos($r5['reply'], 'Budi Santoso') !== false &&
    strpos($r5['reply'], 'Tika') !== false,
    $r5
);

// 6. Ada berapa siswa
$r6 = ChatbotService::reply("ada berapa siswa");
assertCheck("6. 'ada berapa siswa' dijawab dengan 5 siswa dan daftar siswa",
    $r6['status'] === 'success' &&
    $r6['source'] === 'database_info' &&
    strpos($r6['reply'], '5 siswa') !== false &&
    strpos($r6['reply'], 'Ani Lestari') !== false &&
    strpos($r6['reply'], 'Bimo Nugroho') !== false,
    $r6
);

// 7. Ada berapa ujian
$r7 = ChatbotService::reply("ada berapa ujian");
assertCheck("7. 'ada berapa ujian' dijawab dengan jumlah sesi ujian di database",
    $r7['status'] === 'success' &&
    $r7['source'] === 'database_info' &&
    strpos($r7['reply'], 'sesi ujian') !== false,
    $r7
);

// 8. Ada berapa mata pelajaran
$r8 = ChatbotService::reply("ada berapa mata pelajaran");
assertCheck("8. 'ada berapa mata pelajaran' dijawab dengan 4 mata pelajaran",
    $r8['status'] === 'success' &&
    $r8['source'] === 'database_info' &&
    strpos($r8['reply'], '4 mata pelajaran') !== false &&
    strpos($r8['reply'], 'Matematika Wajib') !== false,
    $r8
);

// 9. Ada berapa user
$r9 = ChatbotService::reply("ada berapa user");
assertCheck("9. 'ada berapa user' dijawab dengan total 10 user dan breakdown role",
    $r9['status'] === 'success' &&
    $r9['source'] === 'database_info' &&
    strpos($r9['reply'], '10 pengguna') !== false &&
    strpos($r9['reply'], 'Siswa') !== false &&
    strpos($r9['reply'], 'Guru') !== false &&
    strpos($r9['reply'], 'Admin') !== false,
    $r9
);

// 10. Siapa guru matematika
$r10 = ChatbotService::reply("siapa guru matematika");
assertCheck("10. 'siapa guru matematika' dijawab Budi Santoso",
    $r10['status'] === 'success' &&
    $r10['source'] === 'database_info' &&
    strpos($r10['reply'], 'Budi Santoso') !== false &&
    strpos($r10['reply'], 'Matematika') !== false,
    $r10
);

// 11. Siapa siswa yang telah mengikuti ujian
$r11 = ChatbotService::reply("siapa siswa yang telah mengikuti ujian");
assertCheck("11. 'siapa siswa yang telah mengikuti ujian' dijawab dengan peserta ujian",
    $r11['status'] === 'success' &&
    $r11['source'] === 'database_info' &&
    strpos($r11['reply'], 'Ani Lestari') !== false &&
    strpos($r11['reply'], 'Bimo Nugroho') !== false,
    $r11
);

// 12. Ada berapa siswa yang telah submit ujian
$r12 = ChatbotService::reply("ada berapa siswa yang telah submit ujian");
assertCheck("12. 'ada berapa siswa yang telah submit ujian' dijawab dengan 3 siswa submitted",
    $r12['status'] === 'success' &&
    $r12['source'] === 'database_info' &&
    strpos($r12['reply'], '3 siswa') !== false &&
    strpos($r12['reply'], 'Ani Lestari') !== false,
    $r12
);

// 13. Berapa soal di setiap mata pelajaran
$r13 = ChatbotService::reply("berapa soal di setiap mata pelajaran");
assertCheck("13. 'berapa soal di setiap mata pelajaran' dijawab dengan jumlah soal per mapel",
    $r13['status'] === 'success' &&
    $r13['source'] === 'database_info' &&
    strpos($r13['reply'], 'Matematika Wajib') !== false &&
    strpos($r13['reply'], '7') !== false &&
    strpos($r13['reply'], 'Biologi') !== false,
    $r13
);

// 14. NIS setiap siswa
$r14 = ChatbotService::reply("nis setiap siswa");
assertCheck("14. 'nis setiap siswa' dijawab dengan daftar NIS semua siswa",
    $r14['status'] === 'success' &&
    $r14['source'] === 'database_info' &&
    strpos($r14['reply'], '20261001') !== false &&
    strpos($r14['reply'], '20261002') !== false &&
    strpos($r14['reply'], '0898342') !== false &&
    strpos($r14['reply'], '2387335') !== false,
    $r14
);

// 15. Jumlah siswa di setiap kelas nya
$r15 = ChatbotService::reply("jumlah siswa di setiap kelas nya");
assertCheck("15. 'jumlah siswa di setiap kelas nya' dijawab dengan breakdown per kelas",
    $r15['status'] === 'success' &&
    $r15['source'] === 'database_info' &&
    strpos($r15['reply'], 'XII MIPA 1') !== false &&
    strpos($r15['reply'], '3 siswa') !== false &&
    strpos($r15['reply'], 'XII MIPA 2') !== false &&
    strpos($r15['reply'], '2 siswa') !== false,
    $r15
);

echo "\n--------------------------------------------------------------------\n";
echo " Hasil Pengujian: {$passCount} Berhasil, {$failCount} Gagal\n";
echo "====================================================================\n";

if ($failCount === 0) {
    echo "Semua 15 pertanyaan pengguna & guardrail BERHASIL 100%!\n";
    exit(0);
} else {
    echo "Ada {$failCount} pengujian yang gagal.\n";
    exit(1);
}
