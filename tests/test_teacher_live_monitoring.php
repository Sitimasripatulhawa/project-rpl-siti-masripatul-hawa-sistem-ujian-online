<?php
/**
 * Test Suite: Tahap 11 Teacher Live Monitoring
 * AI Online Exam & Proctoring System
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/models/ExamModel.php';
require_once __DIR__ . '/../app/models/ExamEngineModel.php';
require_once __DIR__ . '/../app/models/ProctoringModel.php';

$baseUrl = 'http://localhost/Project2';
$teacherCookie = sys_get_temp_dir() . '/test_mon_teacher_' . uniqid() . '.txt';
$studentCookie = sys_get_temp_dir() . '/test_mon_student_' . uniqid() . '.txt';

$passed = 0;
$failed = 0;

function assertMonTest(string $desc, bool $condition, string $details = '') {
    global $passed, $failed;
    if ($condition) {
        echo " [PASS] $desc\n";
        $passed++;
    } else {
        echo " [FAIL] $desc" . ($details ? " ($details)" : '') . "\n";
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
    
    return ['code' => $httpCode, 'headers' => $headers, 'body' => $body];
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
    
    return ['code' => $httpCode, 'headers' => $headers, 'body' => $body];
}

echo "=======================================================\n";
echo "RUNNING TEST SUITE: TEACHER LIVE MONITORING (TAHAP 11)\n";
echo "=======================================================\n\n";

$db = Database::getInstance();

// -------------------------------------------------------------
// 0. Setup Multi-User Test Exam
// -------------------------------------------------------------
echo "--- 0. Preparing Multi-User Monitored Exam ---\n";

$db->query("DELETE FROM exams WHERE title LIKE '%Tahap 11 Live Mon%'")->execute();

$examModel = new ExamModel();
$examData = [
    'title' => 'Tahap 11 Live Mon Test Exam',
    'description' => 'Ujian simulasi pemantauan langsung guru.',
    'subject_id' => 1,
    'class_id' => 1,
    'duration_minutes' => 60,
    'start_time' => date('Y-m-d H:i:s', strtotime('-15 minutes')),
    'end_time' => date('Y-m-d H:i:s', strtotime('+3 hours')),
    'token' => 'LIVE11',
    'is_random_question' => 0,
    'is_random_option' => 0,
    'enable_proctoring' => 1,
    'status' => 'published',
    'total_score' => 100.00,
    'passing_score' => 60.00
];
$examId = $examModel->createExamWithQuestions(1, $examData, [1, 2], 1);
assertMonTest("Monitored exam created (ID: $examId)", $examId > 0);

// Setup 3 Students with varied scenarios:
// Student 1 (Ani, ID 1): Active Online, Normal, 1 Answer
// Student 2 (Bimo, ID 2): Active Online, Multiple High-Risk Anomaly Events
// Student 3 (Citra, ID 3): Completed (Submitted)

// Participant 1: Ani
$db->query("
    INSERT INTO exam_participants (exam_id, student_id, status, start_time, risk_score)
    VALUES (:eid, 1, 'in_progress', NOW() - INTERVAL 10 MINUTE, 0.00)
    ON DUPLICATE KEY UPDATE status = 'in_progress', risk_score = 0.00
")->bind(':eid', $examId)->execute();

$pAni = $db->query("SELECT id FROM exam_participants WHERE exam_id = :eid AND student_id = 1 LIMIT 1")
    ->bind(':eid', $examId)->single();
$pidAni = (int)$pAni['id'];

// Add 1 answer for Ani
$db->query("
    INSERT INTO exam_answers (exam_participant_id, question_id, selected_option_id, answered_at)
    VALUES (:pid, 1, 1, NOW() - INTERVAL 20 SECOND)
    ON DUPLICATE KEY UPDATE selected_option_id = 1, answered_at = NOW() - INTERVAL 20 SECOND
")->bind(':pid', $pidAni)->execute();

// Participant 2: Bimo
$db->query("
    INSERT INTO exam_participants (exam_id, student_id, status, start_time, risk_score)
    VALUES (:eid, 2, 'in_progress', NOW() - INTERVAL 12 MINUTE, 15.00)
    ON DUPLICATE KEY UPDATE status = 'in_progress', start_time = NOW() - INTERVAL 12 MINUTE, risk_score = 15.00
")->bind(':eid', $examId)->execute();

$pBimo = $db->query("SELECT id FROM exam_participants WHERE exam_id = :eid AND student_id = 2 LIMIT 1")
    ->bind(':eid', $examId)->single();
$pidBimo = (int)$pBimo['id'];

// Log proctoring events for Bimo (FACE_NOT_DETECTED, CAMERA_DISCONNECTED, MULTIPLE_FACE)
$proctoringModel = new ProctoringModel();
$proctoringModel->logEvent($pidBimo, $examId, 2, 'FACE_NOT_DETECTED', 'WARNING', ['detail' => 'Face not detected'], null, 1.0, 'primary_camera', 0);
$proctoringModel->logEvent($pidBimo, $examId, 2, 'CAMERA_DISCONNECTED', 'WARNING', ['detail' => 'Camera unplugged'], null, 1.0, 'primary_camera', 0);
$proctoringModel->logEvent($pidBimo, $examId, 2, 'MULTIPLE_FACE', 'CRITICAL', ['face_count' => 2], null, 1.0, 'primary_camera', 0);

// Participant 3: Citra
$db->query("
    INSERT INTO exam_participants (exam_id, student_id, status, start_time, submit_time, risk_score)
    VALUES (:eid, 3, 'submitted', NOW() - INTERVAL 30 MINUTE, NOW() - INTERVAL 5 MINUTE, 0.00)
    ON DUPLICATE KEY UPDATE status = 'submitted', risk_score = 0.00
")->bind(':eid', $examId)->execute();

$pCitra = $db->query("SELECT id FROM exam_participants WHERE exam_id = :eid AND student_id = 3 LIMIT 1")
    ->bind(':eid', $examId)->single();
$pidCitra = (int)$pCitra['id'];

assertMonTest("Created 3 participant records with varied test scenarios", 
    $pidAni > 0 && $pidBimo > 0 && $pidCitra > 0);

// -------------------------------------------------------------
// 1. Authentication & Authorization Security Test
// -------------------------------------------------------------
echo "\n--- 1. Testing Authentication & Authorization Security ---\n";

// Unauthenticated access
$unauthRes = httpGet("$baseUrl/teacher/monitoring/$examId", null);
assertMonTest("Unauthenticated access to /teacher/monitoring redirects to login", 
    in_array($unauthRes['code'], [302, 401]));

// Student accessing teacher monitoring
httpPost("$baseUrl/auth/authenticate", ['username' => 'siswa_ani', 'password' => 'password123'], $studentCookie);
$studentRes = httpGet("$baseUrl/teacher/monitoring/$examId", $studentCookie);
assertMonTest("Student role accessing /teacher/monitoring is blocked and redirected", 
    in_array($studentRes['code'], [302, 403]));

$studentDataRes = httpGet("$baseUrl/teacher/monitoringData/$examId", $studentCookie);
assertMonTest("Student accessing /teacher/monitoringData is blocked and redirected", 
    in_array($studentDataRes['code'], [302, 403]));

// Teacher login
$loginTeacher = httpPost("$baseUrl/auth/authenticate", ['username' => 'guru_budi', 'password' => 'password123'], $teacherCookie);
assertMonTest("Teacher Budi login successful", $loginTeacher['code'] === 302);

// -------------------------------------------------------------
// 2. Teacher Live Monitoring Page UI Test
// -------------------------------------------------------------
echo "\n--- 2. Testing Teacher Live Monitoring Page UI Elements ---\n";

$pageRes = httpGet("$baseUrl/teacher/monitoring/$examId", $teacherCookie);
assertMonTest("Live monitoring page loads with HTTP 200", $pageRes['code'] === 200);
assertMonTest("Page contains title 'Live Monitoring Ujian'", strpos($pageRes['body'], 'Live Monitoring Ujian') !== false);
assertMonTest("Page contains active exam selector (#selectExam)", strpos($pageRes['body'], 'id="selectExam"') !== false);
assertMonTest("Page contains auto-refresh toggle (#btnToggleRefresh)", strpos($pageRes['body'], 'id="btnToggleRefresh"') !== false);
assertMonTest("Page contains stat cards (#statTotal, #statOnline, #statCompleted)", 
    strpos($pageRes['body'], 'id="statTotal"') !== false && strpos($pageRes['body'], 'id="statOnline"') !== false);
assertMonTest("Page contains warning & high risk stat counters (#statWarning, #statHighRisk)", 
    strpos($pageRes['body'], 'id="statWarning"') !== false && strpos($pageRes['body'], 'id="statHighRisk"') !== false);
assertMonTest("Page contains filter tabs (#filterTabs)", strpos($pageRes['body'], 'id="filterTabs"') !== false);
assertMonTest("Page contains participants grid container (#participantsGrid)", strpos($pageRes['body'], 'id="participantsGrid"') !== false);
assertMonTest("Page contains detail & timeline modal (#participantModal)", strpos($pageRes['body'], 'id="participantModal"') !== false);

// -------------------------------------------------------------
// 3. Live Monitoring JSON Polling API Test
// -------------------------------------------------------------
echo "\n--- 3. Testing Live Monitoring JSON Polling API (/teacher/monitoringData) ---\n";

$dataRes = httpGet("$baseUrl/teacher/monitoringData/$examId", $teacherCookie);
assertMonTest("monitoringData returned HTTP 200", $dataRes['code'] === 200);

$json = json_decode($dataRes['body'], true);
assertMonTest("monitoringData returned valid JSON with success=true", ($json['success'] ?? false) === true);
assertMonTest("API response contains exam details", !empty($json['exam']['id']));
assertMonTest("API response contains summary stats", isset($json['summary']['total']));
assertMonTest("API response lists participants", is_array($json['participants']) && count($json['participants']) >= 3);

// -------------------------------------------------------------
// 4. Multi-User Simulation & Participant Card Data Verification
// -------------------------------------------------------------
echo "\n--- 4. Testing Multi-User Simulation & Status Representation ---\n";

$participants = $json['participants'];
$aniData = null;
$bimoData = null;
$citraData = null;

foreach ($participants as $p) {
    if ($p['participant_id'] == $pidAni) $aniData = $p;
    if ($p['participant_id'] == $pidBimo) $bimoData = $p;
    if ($p['participant_id'] == $pidCitra) $citraData = $p;
}

assertMonTest("Found participant Ani in monitoring data", $aniData !== null);
assertMonTest("Found participant Bimo in monitoring data", $bimoData !== null);
assertMonTest("Found participant Citra in monitoring data", $citraData !== null);

// Scenario 1: Ani (Normal, Active, Online)
assertMonTest("Ani status is 'in_progress'", $aniData['participant_status'] === 'in_progress');
assertMonTest("Ani is marked online (is_online=true)", $aniData['is_online'] === true);
assertMonTest("Ani progress is 50% (1 of 2 questions answered)", (int)$aniData['progress_percent'] === 50);
assertMonTest("Ani risk level is 'NORMAL'", $aniData['risk_level'] === 'NORMAL');
assertMonTest("Ani Camera 1 status is 'Aktif'", $aniData['camera1_status'] === 'Aktif');

// Scenario 2: Bimo (Anomalies, High Risk / Needs Review)
assertMonTest("Bimo status is 'in_progress'", $bimoData['participant_status'] === 'in_progress');
assertMonTest("Bimo risk level is 'HIGH RISK' (Needs Review)", $bimoData['risk_level'] === 'HIGH RISK');
assertMonTest("Bimo risk label contains 'Needs Review'", strpos($bimoData['risk_label'], 'Needs Review') !== false);
assertMonTest("Bimo critical_count >= 1", (int)$bimoData['critical_count'] >= 1);
assertMonTest("Bimo warning_count >= 1", (int)$bimoData['warning_count'] >= 1);
assertMonTest("Bimo Camera 1 status is 'Terputus' (CAMERA_DISCONNECTED)", $bimoData['camera1_status'] === 'Terputus');

// Scenario 3: Citra (Submitted)
assertMonTest("Citra status is 'submitted'", $citraData['participant_status'] === 'submitted');
assertMonTest("Citra online_status_text is 'Selesai'", $citraData['online_status_text'] === 'Selesai');

// -------------------------------------------------------------
// 5. Participant Timeline API Test (/teacher/participantTimelineData)
// -------------------------------------------------------------
echo "\n--- 5. Testing Participant Timeline Data (/teacher/participantTimelineData) ---\n";

$timelineRes = httpGet("$baseUrl/teacher/participantTimelineData/$pidBimo", $teacherCookie);
assertMonTest("participantTimelineData returned HTTP 200", $timelineRes['code'] === 200);

$tJson = json_decode($timelineRes['body'], true);
assertMonTest("Timeline JSON returned success=true", ($tJson['success'] ?? false) === true);
assertMonTest("Timeline array contains events", !empty($tJson['timeline']));

$timelineEvents = $tJson['timeline'];
$eventTypes = array_column($timelineEvents, 'event_type');

assertMonTest("Timeline includes 'NORMAL' (Memulai Ujian)", in_array('NORMAL', $eventTypes));
assertMonTest("Timeline includes 'FACE_NOT_DETECTED'", in_array('FACE_NOT_DETECTED', $eventTypes));
assertMonTest("Timeline includes 'CAMERA_DISCONNECTED'", in_array('CAMERA_DISCONNECTED', $eventTypes));
assertMonTest("Timeline includes 'MULTIPLE_FACE'", in_array('MULTIPLE_FACE', $eventTypes));

// Verify chronological ordering
$firstTime = $timelineEvents[0]['timestamp'];
$lastTime = end($timelineEvents)['timestamp'];
assertMonTest("Timeline is chronologically sorted (first <= last)", $firstTime <= $lastTime);

// -------------------------------------------------------------
// 6. Navigation Sidebar Integration Test
// -------------------------------------------------------------
echo "\n--- 6. Testing Sidebar Navigation Integration ---\n";

$dashboardRes = httpGet("$baseUrl/teacher/dashboard", $teacherCookie);
assertMonTest("Teacher sidebar contains 'Live Monitoring' link", 
    strpos($dashboardRes['body'], 'teacher/monitoring') !== false);
assertMonTest("Sidebar uses broadcast icon (<i class=\"bi bi-broadcast\"></i>)", 
    strpos($dashboardRes['body'], 'bi-broadcast') !== false);

// Clean up cookies
@unlink($teacherCookie);
@unlink($studentCookie);

// -------------------------------------------------------------
// Summary
// -------------------------------------------------------------
echo "\n=======================================================\n";
echo "TEST RESULTS: $passed PASSED, $failed FAILED\n";
echo "=======================================================\n";

if ($failed > 0) {
    exit(1);
}
