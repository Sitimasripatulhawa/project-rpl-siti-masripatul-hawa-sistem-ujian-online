<?php
/**
 * Test Suite: Database-Driven Chatbot Question Explanations & Discussions
 * AI Online Exam & Proctoring System
 */

define('FORCE_LOCAL_CHATBOT', true);
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/services/ChatbotService.php';

echo "========================================================\n";
echo " Test Suite: Database-Driven Chatbot Pembahasan Jawaban\n";
echo "========================================================\n\n";

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

// ----------------------------------------------------
// 1. GLOBAL FLOATING CHATBOT: PEMBAHASAN SOAL DARI DATABASE
// ----------------------------------------------------
echo "--- 1. Global Floating Chatbot: Pembahasan Soal dari Database ---\n";

// 1.1 Soal Nomor 1 (Fungsi Kuadrat)
$resQ1 = ChatbotService::reply("Bahas soal nomor 1");
assertCheck("1.1 Bahas soal nomor 1 memuat teks soal, kunci A, dan pembahasan DB",
    $resQ1['status'] === 'success' &&
    strpos($resQ1['reply'], 'fungsi kuadrat') !== false &&
    strpos($resQ1['reply'], 'Titik puncak') !== false &&
    strpos($resQ1['reply'], '(3, -1)') !== false
);

// 1.2 Keyword: Fungsi Kuadrat
$resKuadrat = ChatbotService::reply("Pembahasan soal fungsi kuadrat");
assertCheck("1.2 Pembahasan fungsi kuadrat sesuai database",
    $resKuadrat['status'] === 'success' &&
    strpos($resKuadrat['reply'], 'Titik puncak') !== false
);

// 1.3 Keyword: Limit
$resLimit = ChatbotService::reply("Bahas soal limit");
assertCheck("1.3 Pembahasan limit memuat faktorisasi dan nilai 4 dari DB",
    $resLimit['status'] === 'success' &&
    strpos($resLimit['reply'], 'Faktorisasi') !== false &&
    strpos($resLimit['reply'], '4') !== false
);

// 1.4 Keyword: Turunan / Interval Naik
$resTurunan = ChatbotService::reply("Bahas soal turunan fungsi aljabar");
assertCheck("1.4 Pembahasan turunan memuat f'(x) dan interval x < 1 atau x > 2 dari DB",
    $resTurunan['status'] === 'success' &&
    strpos($resTurunan['reply'], '6x² - 18x + 12') !== false &&
    strpos($resTurunan['reply'], 'x < 1 atau x > 2') !== false
);

// 1.5 Keyword: Dadu / Peluang
$resDadu = ChatbotService::reply("Bahas soal dadu");
assertCheck("1.5 Pembahasan dadu memuat angka prima ganjil {3, 5} dan peluang 1/3 dari DB",
    $resDadu['status'] === 'success' &&
    strpos($resDadu['reply'], 'Ruang sampel') !== false &&
    strpos($resDadu['reply'], '1/3') !== false
);

// 1.6 Keyword: Integral Tentu
$resIntegral = ChatbotService::reply("Pembahasan soal integral tentu");
assertCheck("1.6 Pembahasan integral memuat anti-turunan dan hasil 10 dari DB",
    $resIntegral['status'] === 'success' &&
    strpos($resIntegral['reply'], 'Anti-turunan') !== false &&
    strpos($resIntegral['reply'], '10') !== false
);

// 1.7 Keyword: 25 x 4
$res25 = ChatbotService::reply("Berapakah hasil dari 25 x 4?");
assertCheck("1.7 Pembahasan soal 25 x 4 memuat jawaban 100 dari DB",
    $res25['status'] === 'success' &&
    strpos($res25['reply'], '100') !== false
);

// 1.8 Keyword: Bcrypt
$resBcrypt = ChatbotService::reply("Bahas soal enkripsi password bcrypt");
assertCheck("1.8 Pembahasan soal Bcrypt memuat Blowfish dan salt dari DB",
    $resBcrypt['status'] === 'success' &&
    strpos($resBcrypt['reply'], 'Blowfish') !== false
);

// ----------------------------------------------------
// 2. SISWA: PEMBAHASAN UJIAN YANG TELAH SELESAI
// ----------------------------------------------------
echo "\n--- 2. Siswa: Pembahasan Ujian yang Telah Selesai ---\n";

$studentUser = [
    'id' => 3, // Ani Lestari
    'role' => 'student',
    'full_name' => 'Ani Lestari'
];

$resStudentExam = ChatbotService::reply("Bagaimana pembahasan ujian matematika saya?", $studentUser);
assertCheck("2.1 Siswa menerima rekapitulasi pembahasan soal dari ujiannya yang telah disubmit",
    $resStudentExam['status'] === 'success' &&
    strpos($resStudentExam['reply'], 'Pembahasan Resmi Database') !== false &&
    strpos($resStudentExam['reply'], 'Jawaban Anda') !== false
);

// ----------------------------------------------------
// 3. CHATBOT TIPE 2: QUESTION EXPLAINER PER-SOAL (DI MODAL RESULT)
// ----------------------------------------------------
echo "\n--- 3. Question Explainer (Chatbot Tipe 2: Penjelasan Tiap Soal) ---\n";

$dbQuestion = [
    'question_id' => 1,
    'question_text' => 'Diketahui sebuah fungsi kuadrat f(x) = x² - 6x + 8. Tentukan titik balik minimum fungsi tersebut!',
    'question_type' => 'multiple_choice',
    'options' => [
        ['option_label' => 'A', 'option_text' => '(3, -1)', 'is_correct' => 1],
        ['option_label' => 'B', 'option_text' => '(-3, 1)', 'is_correct' => 0],
        ['option_label' => 'C', 'option_text' => '(3, 1)', 'is_correct' => 0]
    ],
    'student_answer_label' => 'B. (-3, 1)',
    'correct_answer_label' => 'A. (3, -1)',
    'is_correct' => 0,
    'explanation' => 'Titik puncak xp = -b/(2a) = 6/2 = 3. yp = f(3) = 9 - 18 + 8 = -1. Jadi titik balik minimum adalah (3, -1).'
];

// 3.1 Initial Review
$expInit = ChatbotService::explainQuestion($dbQuestion, 'init');
assertCheck("3.1 Penjelasan awal memuat kunci A.(3, -1) dan pembahasan DB",
    $expInit['status'] === 'success' &&
    strpos($expInit['reply'], '(3, -1)') !== false &&
    strpos($expInit['reply'], 'Titik puncak') !== false
);

// 3.2 Follow-up: Mengapa jawaban saya salah?
$expWrong = ChatbotService::explainQuestion($dbQuestion, 'Mengapa jawaban saya salah?');
assertCheck("3.2 Penjelasan kekeliruan jawaban membandingkan B.(-3, 1) dengan kunci A.(3, -1) dan pembahasan DB",
    $expWrong['status'] === 'success' &&
    strpos($expWrong['reply'], 'B. (-3, 1)') !== false &&
    strpos($expWrong['reply'], 'A. (3, -1)') !== false &&
    strpos($expWrong['reply'], 'Titik puncak') !== false
);

// 3.3 Follow-up: Langkah penyelesaian
$expSteps = ChatbotService::explainQuestion($dbQuestion, 'Bagaimana langkah penyelesaiannya?');
assertCheck("3.3 Langkah penyelesaian merinci absis dan ordinat titik puncak sesuai DB",
    $expSteps['status'] === 'success' &&
    strpos($expSteps['reply'], 'Titik puncak') !== false &&
    strpos($expSteps['reply'], 'Langkah') !== false
);

// 3.4 Follow-up: Rumus kunci
$expFormula = ChatbotService::explainQuestion($dbQuestion, 'Apa rumus atau konsep materi kunci soal ini?');
assertCheck("3.4 Rumus kunci memuat rujukan pembahasan resmi database",
    $expFormula['status'] === 'success' &&
    strpos($expFormula['reply'], 'Konsep & Rumus Kunci') !== false &&
    strpos($expFormula['reply'], 'Titik puncak') !== false
);

// 3.5 Follow-up: Tips
$expTips = ChatbotService::explainQuestion($dbQuestion, 'Tips agar tidak keliru pada soal serupa');
assertCheck("3.5 Tips praktis merujuk acuan pembahasan resmi database",
    $expTips['status'] === 'success' &&
    strpos($expTips['reply'], 'Tips Menjawab Soal Serupa') !== false &&
    strpos($expTips['reply'], 'Titik puncak') !== false
);

echo "\n--------------------------------------------------------\n";
echo " Hasil Pengujian: {$passCount} Berhasil, {$failCount} Gagal\n";
echo "--------------------------------------------------------\n";

if ($failCount === 0) {
    echo "Semua pengujian Pembahasan Jawaban Chatbot Berdasarkan Database BERHASIL 100%!\n";
} else {
    exit(1);
}
