<?php
/**
 * Test Suite: Tahap 9 Dual Camera Proctoring Foundation
 * AI Online Exam & Proctoring System
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/models/ExamModel.php';
require_once __DIR__ . '/../app/models/ExamEngineModel.php';

$baseUrl = 'http://localhost/Project2';
$cookieFile = sys_get_temp_dir() . '/test_cam_proc_' . uniqid() . '.txt';

$passed = 0;
$failed = 0;

function assertCamTest($description, $condition) {
    global $passed, $failed;
    if ($condition) {
        echo " [PASS] " . $description . "\n";
        $passed++;
    } else {
        echo " [FAIL] " . $description . "\n";
        $failed++;
    }
}

function httpGet($url, $cookieFile = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'headers' => $headers,
        'body' => $body
    ];
}

function httpPost($url, $postFields = [], $cookieFile = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'headers' => $headers,
        'body' => $body
    ];
}

echo "=======================================================\n";
echo "RUNNING TEST SUITE: DUAL CAMERA PROCTORING (TAHAP 9)\n";
echo "=======================================================\n\n";

$db = Database::getInstance();

// -------------------------------------------------------------------
// 0. Setup Test Exams (Proctoring Enabled vs Optional)
// -------------------------------------------------------------------
echo "--- 0. Preparing Proctoring Test Exams ---\n";
// Clean previous Tahap 9 test exams
$db->query("DELETE FROM exams WHERE title LIKE '%Tahap 9 Proctoring%'")->execute();

$examModel = new ExamModel();

// Exam 1: enable_proctoring = 1
$examData1 = [
    'title' => 'Tahap 9 Proctoring Enabled Exam',
    'description' => 'Ujian dengan sistem kamera pengawasan aktif.',
    'subject_id' => 1,
    'class_id' => 1,
    'duration_minutes' => 45,
    'start_time' => date('Y-m-d H:i:s', strtotime('-1 hour')),
    'end_time' => date('Y-m-d H:i:s', strtotime('+4 hours')),
    'token' => 'CAM001',
    'is_random_question' => 0,
    'is_random_option' => 0,
    'enable_proctoring' => 1,
    'status' => 'published',
    'total_score' => 100.00,
    'passing_score' => 60.00
];
$examIdProctored = $examModel->createExamWithQuestions(1, $examData1, [1, 2], 1);
assertCamTest("Proctored exam created (ID: {$examIdProctored}) with enable_proctoring = 1", $examIdProctored > 0);

// Exam 2: enable_proctoring = 0
$examData2 = [
    'title' => 'Tahap 9 Proctoring Optional Exam',
    'description' => 'Ujian tanpa pengawasan kamera wajib.',
    'subject_id' => 1,
    'class_id' => 1,
    'duration_minutes' => 45,
    'start_time' => date('Y-m-d H:i:s', strtotime('-1 hour')),
    'end_time' => date('Y-m-d H:i:s', strtotime('+4 hours')),
    'token' => 'CAM002',
    'is_random_question' => 0,
    'is_random_option' => 0,
    'enable_proctoring' => 0,
    'status' => 'published',
    'total_score' => 100.00,
    'passing_score' => 60.00
];
$examIdOptional = $examModel->createExamWithQuestions(1, $examData2, [1, 2], 1);
assertCamTest("Optional proctoring exam created (ID: {$examIdOptional}) with enable_proctoring = 0", $examIdOptional > 0);

// Student Ani login
$loginRes = httpPost($baseUrl . '/auth/authenticate', [
    'username' => 'siswa_ani',
    'password' => 'password123'
], $cookieFile);
assertCamTest("Student Ani logged in successfully", $loginRes['code'] === 302);

// -------------------------------------------------------------------
// 1. System Check Dual Camera Preview Elements Test
// -------------------------------------------------------------------
echo "\n--- 1. Testing System Check Dual Camera UI Elements ---\n";
// Pass token verification for exam 1
httpPost($baseUrl . '/student/startExam/' . $examIdProctored, ['token' => 'CAM001'], $cookieFile);

$sysCheckRes = httpGet($baseUrl . '/student/systemCheck/' . $examIdProctored, $cookieFile);
assertCamTest("System check page accessible (HTTP 200)", $sysCheckRes['code'] === 200);

// Check Camera 1 elements
assertCamTest("System check contains Camera 1 video element (#camera1Video)", 
    strpos($sysCheckRes['body'], 'id="camera1Video"') !== false);
assertCamTest("System check contains Camera 1 status badge (#camera1Status)", 
    strpos($sysCheckRes['body'], 'id="camera1Status"') !== false);
assertCamTest("System check contains Camera 1 device selector (#camera1Select)", 
    strpos($sysCheckRes['body'], 'id="camera1Select"') !== false);

// Check Camera 2 elements
assertCamTest("System check contains Camera 2 video element (#camera2Video)", 
    strpos($sysCheckRes['body'], 'id="camera2Video"') !== false);
assertCamTest("System check contains Camera 2 status badge (#camera2Status)", 
    strpos($sysCheckRes['body'], 'id="camera2Status"') !== false);
assertCamTest("System check contains Camera 2 device selector (#camera2Select)", 
    strpos($sysCheckRes['body'], 'id="camera2Select"') !== false);

// Check Camera 2 fallback card
assertCamTest("System check contains Camera 2 fallback card (#camera2Fallback)", 
    strpos($sysCheckRes['body'], 'id="camera2Fallback"') !== false);
assertCamTest("Fallback card text explains 'Mode Kamera Tunggal'", 
    strpos($sysCheckRes['body'], 'Mode Kamera Tunggal') !== false);

// Check Microphone & System Status
assertCamTest("System check contains microphone status element (#micStatus)", 
    strpos($sysCheckRes['body'], 'id="micStatus"') !== false);
assertCamTest("System check contains refresh camera button (#btnRefreshCameras)", 
    strpos($sysCheckRes['body'], 'id="btnRefreshCameras"') !== false);
assertCamTest("System check contains permission error box (#cameraErrorBox)", 
    strpos($sysCheckRes['body'], 'id="cameraErrorBox"') !== false);

// Check Proctoring Camera Script inclusion
assertCamTest("System check includes proctoring-camera.js script", 
    strpos($sysCheckRes['body'], 'proctoring-camera.js') !== false);

// -------------------------------------------------------------------
// 2. Proctoring Enforcement & Button Guard Test
// -------------------------------------------------------------------
echo "\n--- 2. Testing Proctoring Enforcement & Continue Guard ---\n";
// Button must be disabled by default for proctored exam
assertCamTest("Continue button (#btnEnterExam) is disabled initially for proctored exam", 
    strpos($sysCheckRes['body'], 'id="btnEnterExam" disabled') !== false);

assertCamTest("System check displays 'Pengawasan Kamera Diaktifkan' badge", 
    strpos($sysCheckRes['body'], 'Pengawasan Kamera Diaktifkan') !== false);

// Check optional proctoring exam behavior
httpPost($baseUrl . '/student/startExam/' . $examIdOptional, ['token' => 'CAM002'], $cookieFile);
$sysCheckOptRes = httpGet($baseUrl . '/student/systemCheck/' . $examIdOptional, $cookieFile);
assertCamTest("Optional proctoring exam displays 'Pengawasan Kamera Opsional' badge", 
    strpos($sysCheckOptRes['body'], 'Pengawasan Kamera Opsional') !== false);

// -------------------------------------------------------------------
// 3. Client WebRTC Engine Verification (proctoring-camera.js)
// -------------------------------------------------------------------
echo "\n--- 3. Testing Client WebRTC Engine Logic (proctoring-camera.js) ---\n";
$jsFile = __DIR__ . '/../public/js/proctoring-camera.js';
assertCamTest("proctoring-camera.js file exists", file_exists($jsFile));

$jsContent = file_get_contents($jsFile);

// Check getUserMedia usage
assertCamTest("JS implements navigator.mediaDevices.getUserMedia", 
    strpos($jsContent, 'navigator.mediaDevices.getUserMedia') !== false);

// Check enumerateDevices
assertCamTest("JS implements navigator.mediaDevices.enumerateDevices", 
    strpos($jsContent, 'enumerateDevices') !== false);

// Check dual camera capability detection
assertCamTest("JS handles dual camera condition (videoDevices.length >= 2)", 
    strpos($jsContent, 'this.videoDevices.length >= 2') !== false);

// Check single camera fallback handling
assertCamTest("JS implements single camera fallback (showCamera2Container(false))", 
    strpos($jsContent, 'this.showCamera2Container(false)') !== false);

// Check permission error handling (NotAllowedError / PermissionDeniedError)
assertCamTest("JS handles NotAllowedError / PermissionDeniedError", 
    strpos($jsContent, 'NotAllowedError') !== false && strpos($jsContent, 'PermissionDeniedError') !== false);

// Check disconnect listener (track.onended & devicechange)
assertCamTest("JS listens to track.onended for disconnect detection", 
    strpos($jsContent, 'track.onended') !== false);
assertCamTest("JS listens to devicechange event", 
    strpos($jsContent, 'devicechange') !== false);

// Check stop streams cleanup
assertCamTest("JS implements stopAllStreams() for memory and battery cleanup", 
    strpos($jsContent, 'stopAllStreams') !== false);

// -------------------------------------------------------------------
// 4. Exam Room Proctoring Monitor Widget Test
// -------------------------------------------------------------------
echo "\n--- 4. Testing Exam Room Proctoring Monitor Widget ---\n";
// Enter room for proctored exam
httpPost($baseUrl . '/student/enterExam/' . $examIdProctored, [], $cookieFile);
$roomRes = httpGet($baseUrl . '/student/examRoom/' . $examIdProctored, $cookieFile);
assertCamTest("Exam room rendered with HTTP 200", $roomRes['code'] === 200);

// Check live PIP proctoring widget
assertCamTest("Exam room contains floating proctoring widget (#proctoringPipWidget)", 
    strpos($roomRes['body'], 'id="proctoringPipWidget"') !== false);
assertCamTest("Exam room contains proctoring live video monitor (#roomCameraVideo)", 
    strpos($roomRes['body'], 'id="roomCameraVideo"') !== false);
assertCamTest("Exam room displays 'Proctoring Aktif' live status text", 
    strpos($roomRes['body'], 'Proctoring Aktif') !== false);
assertCamTest("Exam room contains camera disconnect warning banner (#cameraDisconnectBanner)", 
    strpos($roomRes['body'], 'id="cameraDisconnectBanner"') !== false);
assertCamTest("Exam room script initializes roomCameraStream and disconnect listener", 
    strpos($roomRes['body'], 'ENABLE_PROCTORING') !== false && strpos($roomRes['body'], 'roomCameraStream') !== false);

// -------------------------------------------------------------------
// 5. Browser Refresh & Responsive CSS Styles Test
// -------------------------------------------------------------------
echo "\n--- 5. Testing Browser Refresh & CSS Styling ---\n";
// Re-requesting room (browser refresh simulation)
$refreshRoom = httpGet($baseUrl . '/student/examRoom/' . $examIdProctored, $cookieFile);
assertCamTest("Browser refresh on room retains proctoring widget cleanly", 
    strpos($refreshRoom['body'], 'id="proctoringPipWidget"') !== false);

$cssFile = __DIR__ . '/../public/css/style.css';
$cssContent = file_get_contents($cssFile);

assertCamTest("CSS includes .camera-box style", strpos($cssContent, '.camera-box') !== false);
assertCamTest("CSS includes .camera-video style", strpos($cssContent, '.camera-video') !== false);
assertCamTest("CSS includes .camera-fallback-card style", strpos($cssContent, '.camera-fallback-card') !== false);
assertCamTest("CSS includes .proctoring-pip-widget style", strpos($cssContent, '.proctoring-pip-widget') !== false);
assertCamTest("CSS includes .dot-online live pulse animation", strpos($cssContent, '.dot-online') !== false);

// Clean temporary cookies
@unlink($cookieFile);

echo "\n=======================================================\n";
echo "TEST RESULTS: {$passed} PASSED, {$failed} FAILED\n";
echo "=======================================================\n";

exit($failed > 0 ? 1 : 0);
