<?php
/**
 * Test Suite: Strict Scope Guardrails & Database-Driven Explanations
 * AI Online Exam & Proctoring System
 */

define('FORCE_LOCAL_CHATBOT', true);
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/services/ChatbotService.php';

echo "====================================================================\n";
echo " Test Suite: Strict Guardrails & Database-Grounded Explanations\n";
echo "====================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertCheck(string $desc, bool $condition, ?array $res = null): void
{
    global $passCount, $failCount;
    echo "Testing: {$desc}... ";
    if ($condition) {
        echo "[PASSED]\n";
        $passCount++;
    } else {
        echo "[FAILED]\n";
        if ($res) {
            echo "   -> Source: " . ($res['source'] ?? 'none') . "\n";
            echo "   -> Reply: " . substr($res['reply'] ?? '', 0, 100) . "...\n";
        }
        $failCount++;
    }
}

// ---------------------------------------------------------------------
// 1. PENGUJIAN PENOLAKAN PERMINTAAN KODING & HAL DI LUAR SISTEM (GUARDRAIL)
// ---------------------------------------------------------------------
echo "--- 1. Penolakan Permintaan Koding / Programming di Luar Sistem ---\n";

// 1.1 Permintaan spesifik dari user: "buatkan coding python untuk menuliskan nama anda"
$resPython1 = ChatbotService::reply("buatkan coding python untuk menuliskan nama anda");
assertCheck("1.1 'buatkan coding python untuk menuliskan nama anda' DITOLAK secara tegas",
    $resPython1['status'] === 'success' &&
    $resPython1['source'] === 'guardrail' &&
    stripos($resPython1['reply'], 'Luar Ruang Lingkup') !== false &&
    stripos($resPython1['reply'], 'coding Python') !== false &&
    strpos($resPython1['reply'], 'print(') === false,
    $resPython1
);

// 1.2 Variasi: "tuliskan kodingan python untuk penjumlahan"
$resPython2 = ChatbotService::reply("tuliskan kodingan python untuk penjumlahan");
assertCheck("1.2 'tuliskan kodingan python untuk penjumlahan' DITOLAK",
    $resPython2['status'] === 'success' &&
    $resPython2['source'] === 'guardrail' &&
    stripos($resPython2['reply'], 'Luar Ruang Lingkup') !== false,
    $resPython2
);

// 1.3 Variasi: "buatkan script PHP untuk download file"
$resPhp = ChatbotService::reply("buatkan script PHP untuk download file");
assertCheck("1.3 'buatkan script PHP untuk download file' DITOLAK",
    $resPhp['status'] === 'success' &&
    $resPhp['source'] === 'guardrail' &&
    stripos($resPhp['reply'], 'Luar Ruang Lingkup') !== false,
    $resPhp
);

// 1.4 Variasi: "bikin program c++"
$resCpp = ChatbotService::reply("bikin program c++ untuk kalkulator");
assertCheck("1.4 'bikin program c++' DITOLAK",
    $resCpp['status'] === 'success' &&
    $resCpp['source'] === 'guardrail' &&
    stripos($resCpp['reply'], 'Luar Ruang Lingkup') !== false,
    $resCpp
);

// 1.5 Permintaan puisi / konten non-ujian
$resPuisi = ChatbotService::reply("buatkan puisi cinta yang romantis");
assertCheck("1.5 'buatkan puisi' DITOLAK",
    $resPuisi['status'] === 'success' &&
    $resPuisi['source'] === 'guardrail' &&
    stripos($resPuisi['reply'], 'Luar Ruang Lingkup') !== false,
    $resPuisi
);

// 1.6 Permintaan resep / topik umum
$resResep = ChatbotService::reply("bagaimana resep masakan nasi goreng spesial");
assertCheck("1.6 'resep masakan' DITOLAK",
    $resResep['status'] === 'success' &&
    $resResep['source'] === 'guardrail' &&
    stripos($resResep['reply'], 'Luar Ruang Lingkup') !== false,
    $resResep
);

// 1.7 Topik random di luar ujian
$resRandom = ChatbotService::reply("berapa harga motor vario baru tahun ini");
assertCheck("1.7 Pertanyaan umum non-ujian DITOLAK (fallback guardrail)",
    $resRandom['status'] === 'success' &&
    $resRandom['source'] === 'guardrail' &&
    stripos($resRandom['reply'], 'Luar Ruang Lingkup') !== false,
    $resRandom
);

// ---------------------------------------------------------------------
// 2. QUESTION EXPLAINER: PENOLAKAN KODING & HAL DI LUAR SOAL
// ---------------------------------------------------------------------
echo "\n--- 2. Question Explainer (Modal Hasil): Penolakan Permintaan Koding ---\n";

$dummyQuestion = [
    'question_id' => 1,
    'question_text' => 'Diketahui fungsi kuadrat f(x) = x² - 6x + 8. Titik balik minimum?',
    'correct_answer_label' => 'A. (3, -1)',
    'student_answer_label' => 'B. (-3, 1)',
    'is_correct' => 0,
    'explanation' => 'Titik puncak xp = 3, yp = -1.'
];

$qExpPython = ChatbotService::explainQuestion($dummyQuestion, "buatkan coding python untuk menuliskan nama anda");
assertCheck("2.1 Question Explainer MENOLAK permintaan coding python",
    $qExpPython['status'] === 'success' &&
    $qExpPython['source'] === 'guardrail' &&
    stripos($qExpPython['reply'], 'Luar Ruang Lingkup') !== false,
    $qExpPython
);

// ---------------------------------------------------------------------
// 3. KEWAJIBAN PEMBAHASAN HARUS SESUAI DATABASE RESMI
// ---------------------------------------------------------------------
echo "\n--- 3. Pembahasan Jawaban Wajib Sesuai Database Proyek ---\n";

// 3.1 Pembahasan Soal Nomor 1 (Fungsi Kuadrat)
$resDbQ1 = ChatbotService::reply("Bahas soal nomor 1");
assertCheck("3.1 Pembahasan Soal #1 memuat rujukan 'Pembahasan Resmi Database Guru' dan formula resmi",
    $resDbQ1['status'] === 'success' &&
    strpos($resDbQ1['reply'], 'Pembahasan Resmi Database') !== false &&
    strpos($resDbQ1['reply'], 'Titik puncak xp = -b/(2a)') !== false &&
    strpos($resDbQ1['reply'], '(3, -1)') !== false
);

// 3.2 Pembahasan Soal Limit
$resDbLimit = ChatbotService::reply("Pembahasan soal limit fungsi");
assertCheck("3.2 Pembahasan Soal Limit memuat faktorisasi resmi dari database",
    $resDbLimit['status'] === 'success' &&
    strpos($resDbLimit['reply'], 'Faktorisasi') !== false &&
    strpos($resDbLimit['reply'], '4') !== false
);

// 3.3 Pembahasan Soal Enkripsi Bcrypt (Soal #12 di database)
$resDbBcrypt = ChatbotService::reply("Bahas soal enkripsi password bcrypt");
assertCheck("3.3 Pembahasan Soal Bcrypt memuat penjelasan kurikulum Blowfish & salt dari database",
    $resDbBcrypt['status'] === 'success' &&
    strpos($resDbBcrypt['reply'], 'Blowfish') !== false &&
    strpos($resDbBcrypt['reply'], 'salt') !== false
);

// 3.4 Soal yang TIDAK ADA di Database: Harus menegaskan batasan database
$resDbUnknown = ChatbotService::reply("Bahas soal fotosintesis biologi sel klorofil");
assertCheck("3.4 Soal di luar database menjelaskan bahwa seluruh pembahasan harus bersumber dari database",
    $resDbUnknown['status'] === 'success' &&
    $resDbUnknown['source'] === 'database_explainer' &&
    strpos($resDbUnknown['reply'], 'bersumber 100% dari database soal resmi') !== false &&
    strpos($resDbUnknown['reply'], 'Daftar Butir Soal yang Tersedia di Database') !== false
);

// ---------------------------------------------------------------------
// 4. PENGUJIAN JAWABAN PERTANYAAN IDENTITAS: "SIAPA ANDA"
// ---------------------------------------------------------------------
echo "\n--- 4. Pertanyaan Identitas: 'Siapa Anda' & Variasinya ---\n";

// 4.1 "siapa anda"
$resSiapaAnda = ChatbotService::reply("siapa anda");
assertCheck("4.1 Chatbot menjawab pertanyaan 'siapa anda' dengan identitas ExamBot",
    $resSiapaAnda['status'] === 'success' &&
    $resSiapaAnda['source'] === 'local' &&
    strpos($resSiapaAnda['reply'], 'ExamBot') !== false &&
    strpos($resSiapaAnda['reply'], 'AI Online Exam & Proctoring System') !== false,
    $resSiapaAnda
);

// 4.2 "siapakah anda"
$resSiapakahAnda = ChatbotService::reply("siapakah anda");
assertCheck("4.2 Chatbot menjawab pertanyaan 'siapakah anda'",
    $resSiapakahAnda['status'] === 'success' &&
    strpos($resSiapakahAnda['reply'], 'ExamBot') !== false,
    $resSiapakahAnda
);

// 4.3 "anda siapa"
$resAndaSiapa = ChatbotService::reply("anda siapa");
assertCheck("4.3 Chatbot menjawab pertanyaan 'anda siapa'",
    $resAndaSiapa['status'] === 'success' &&
    strpos($resAndaSiapa['reply'], 'ExamBot') !== false,
    $resAndaSiapa
);

// ---------------------------------------------------------------------
// 5. PENGUJIAN PERTANYAAN ENTITAS SESUAI DATABASE SISTEM
// ---------------------------------------------------------------------
echo "\n--- 5. Pertanyaan Entitas Sesuai Database Sistem ---\n";

// 5.1 Mata Pelajaran dari Database
$resMapel = ChatbotService::reply("mata pelajaran apa saja yang ada?");
assertCheck("5.1 Pertanyaan mapel dijawab sesuai tabel subjects di database",
    $resMapel['status'] === 'success' &&
    $resMapel['source'] === 'database_info' &&
    strpos($resMapel['reply'], 'Matematika Wajib') !== false &&
    strpos($resMapel['reply'], 'MTK-XII-01') !== false,
    $resMapel
);

// 5.2 Guru dari Database
$resGuru = ChatbotService::reply("daftar guru di sekolah ini");
assertCheck("5.2 Pertanyaan guru dijawab sesuai tabel teachers di database",
    $resGuru['status'] === 'success' &&
    $resGuru['source'] === 'database_info' &&
    strpos($resGuru['reply'], 'Budi Santoso') !== false &&
    strpos($resGuru['reply'], 'Matematika Terapan & Statistika') !== false,
    $resGuru
);

// 5.3 Kelas dari Database
$resKelas = ChatbotService::reply("daftar kelas yang ada");
assertCheck("5.3 Pertanyaan kelas dijawab sesuai tabel classes di database",
    $resKelas['status'] === 'success' &&
    $resKelas['source'] === 'database_info' &&
    strpos($resKelas['reply'], 'XII MIPA 1') !== false,
    $resKelas
);

// 5.4 Sesi Ujian & KKM dari Database
$resUjian = ChatbotService::reply("ada ujian apa saja di sistem ini?");
assertCheck("5.4 Pertanyaan ujian dijawab sesuai tabel exams di database",
    $resUjian['status'] === 'success' &&
    $resUjian['source'] === 'database_info' &&
    strpos($resUjian['reply'], 'KKM') !== false,
    $resUjian
);

echo "\n--------------------------------------------------------------------\n";
echo " Hasil Pengujian: {$passCount} Berhasil, {$failCount} Gagal\n";
echo "====================================================================\n";

if ($failCount === 0) {
    echo "Semua pengujian Guardrail, Identitas, & Jawaban Database BERHASIL 100%!\n";
} else {
    exit(1);
}
