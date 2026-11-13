<?php
/**
 * Test Suite: AI Chatbot Service & Controller Integration
 * AI Online Exam & Proctoring System
 */

require_once __DIR__ . '/../app/config/config.php';
define('FORCE_LOCAL_CHATBOT', true);
require_once __DIR__ . '/../app/services/ChatbotService.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/controllers/HomeController.php';

echo "========================================================\n";
echo " AI Online Exam - AI Chatbot Test Suite\n";
echo "========================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest($description, $condition) {
    global $passCount, $failCount;
    echo "Testing: {$description}... ";
    if ($condition) {
        echo "[PASSED]\n";
        $passCount++;
    } else {
        echo "[FAILED]\n";
        $failCount++;
    }
}

// 1. Test Greeting
$greetingRes = ChatbotService::reply('Halo');
assertTest('1.1 Sapaan Halo dijawab dengan ramah', 
    $greetingRes['status'] === 'success' && 
    strpos($greetingRes['reply'], 'ExamBot') !== false
);

// 2. Test Identity
$idRes = ChatbotService::reply('Siapa kamu?');
assertTest('2.1 Identitas ExamBot dijelaskan secara akurat', 
    $idRes['status'] === 'success' && 
    strpos($idRes['reply'], 'ExamBot') !== false &&
    count($idRes['suggestions']) > 0
);

// 3. Test AI Proctoring query
$proctorRes = ChatbotService::reply('Bagaimana cara kerja AI proctoring dan deteksi kecurangan?');
assertTest('3.1 Pertanyaan AI Proctoring memuat penjelasan deteksi wajah & tab', 
    $proctorRes['status'] === 'success' && 
    strpos($proctorRes['reply'], 'Face Tracking') !== false &&
    strpos($proctorRes['reply'], 'Tab & Window Monitor') !== false
);

// 4. Test Student Workflow
$studentRes = ChatbotService::reply('Bagaimana alur siswa saat mengerjakan ujian?');
assertTest('4.1 Pertanyaan pengerjaan siswa memuat autosave & system check', 
    $studentRes['status'] === 'success' && 
    strpos($studentRes['reply'], 'System Check') !== false &&
    strpos($studentRes['reply'], 'Auto-save') !== false
);

// 5. Test Teacher Workflow
$teacherRes = ChatbotService::reply('Bagaimana panduan guru membuat bank soal dan live monitoring?');
assertTest('5.1 Pertanyaan guru memuat bank soal & live monitoring', 
    $teacherRes['status'] === 'success' && 
    strpos($teacherRes['reply'], 'Bank Soal') !== false &&
    strpos($teacherRes['reply'], 'Live Monitoring') !== false
);

// 6. Test Demo Accounts
$demoRes = ChatbotService::reply('Minta akun demo untuk login');
assertTest('6.1 Permintaan akun demo memuat admin, guru, siswa, dan password123', 
    $demoRes['status'] === 'success' && 
    strpos($demoRes['reply'], 'admin@sekolah.sch.id') !== false &&
    strpos($demoRes['reply'], 'guru_budi') !== false &&
    strpos($demoRes['reply'], 'siswa_ani') !== false &&
    strpos($demoRes['reply'], 'password123') !== false &&
    isset($demoRes['action']['url'])
);

// 7. Test Troubleshooting (Camera error)
$camRes = ChatbotService::reply('Kamera tidak muncul dan izin ditolak');
assertTest('7.1 Troubleshooting kamera memuat panduan izin peramban', 
    $camRes['status'] === 'success' && 
    strpos($camRes['reply'], 'Izinkan') !== false
);

// 8. Test Fallback with suggestions
$unknownRes = ChatbotService::reply('xyz123 random prompt yang tidak terduga');
assertTest('8.1 Fallback memberikan saran topik yang relevan', 
    $unknownRes['status'] === 'success' && 
    count($unknownRes['suggestions']) >= 3
);

// 9. Test Empty input
$emptyRes = ChatbotService::reply('');
assertTest('9.1 Input kosong memberikan balasan pembuka yang sopan', 
    $emptyRes['status'] === 'success' && 
    !empty($emptyRes['reply'])
);

// 10. Test HomeController and Route availability
$homeController = new HomeController();
assertTest('10.1 HomeController instance berhasil dibuat', 
    is_object($homeController) && ($homeController instanceof Controller)
);
assertTest('10.2 Method index dan chat tersedia di HomeController', 
    method_exists($homeController, 'index') && method_exists($homeController, 'chat')
);

echo "\n--------------------------------------------------------\n";
echo " Hasil Pengujian: {$passCount} Berhasil, {$failCount} Gagal\n";
echo "--------------------------------------------------------\n";

if ($failCount === 0) {
    echo "Semua pengujian Chatbot Service & Controller BERHASIL 100%!\n";
} else {
    exit(1);
}
