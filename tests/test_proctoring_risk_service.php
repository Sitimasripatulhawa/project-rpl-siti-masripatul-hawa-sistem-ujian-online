<?php
/**
 * Test Suite: Tahap 12 Proctoring Risk Score & Service
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
require_once __DIR__ . '/../app/services/ProctoringRiskService.php';

$baseUrl = 'http://localhost/Project2';
$teacherCookie = sys_get_temp_dir() . '/test_risk_teacher_' . uniqid() . '.txt';

$passed = 0;
$failed = 0;

function assertRiskTest(string $desc, bool $condition, string $details = '') {
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
echo "RUNNING TEST SUITE: PROCTORING RISK SCORE (TAHAP 12)\n";
echo "=======================================================\n\n";

$db = Database::getInstance();

// -------------------------------------------------------------
// 1. Service Instantiation & Configuration Tests
// -------------------------------------------------------------
echo "--- 1. Testing ProctoringRiskService Configuration & Weights ---\n";

$service = new ProctoringRiskService();
assertRiskTest("ProctoringRiskService instantiated successfully", $service instanceof ProctoringRiskService);

$weights = $service->getWeights();
assertRiskTest("Default weight FACE_NOT_DETECTED is 10", ($weights['FACE_NOT_DETECTED'] ?? 0) === 10);
assertRiskTest("Default weight MULTIPLE_FACE is 40", ($weights['MULTIPLE_FACE'] ?? 0) === 40);
assertRiskTest("Default weight CAMERA_DISCONNECTED is 20", ($weights['CAMERA_DISCONNECTED'] ?? 0) === 20);
assertRiskTest("Default weight FACE_CHANGED is 50", ($weights['FACE_CHANGED'] ?? 0) === 50);
assertRiskTest("Default weight SUSPICIOUS_GAZE is 15", ($weights['SUSPICIOUS_GAZE'] ?? 0) === 15);

$thresholds = $service->getThresholds();
assertRiskTest("Normal max threshold is 29", ($thresholds['normal_max'] ?? 0) === 29);
assertRiskTest("Warning max threshold is 59", ($thresholds['warning_max'] ?? 0) === 59);
assertRiskTest("High risk min threshold is 60", ($thresholds['high_risk_min'] ?? 0) === 60);

// Test custom configurability
$customService = new ProctoringRiskService(['FACE_NOT_DETECTED' => 25], ['normal_max' => 20]);
assertRiskTest("Custom weight applied (FACE_NOT_DETECTED = 25)", $customService->getWeights()['FACE_NOT_DETECTED'] === 25);
assertRiskTest("Custom threshold applied (normal_max = 20)", $customService->getThresholds()['normal_max'] === 20);

// -------------------------------------------------------------
// 2. Score Boundary Tests (0-29 NORMAL, 30-59 WARNING, 60-100 HIGH RISK)
// -------------------------------------------------------------
echo "\n--- 2. Testing Score Boundaries & Level Classification ---\n";

assertRiskTest("Score 0 is NORMAL", $service->determineRiskLevel(0) === 'NORMAL');
assertRiskTest("Score 29 is NORMAL (Boundary)", $service->determineRiskLevel(29) === 'NORMAL');
assertRiskTest("Score 30 is WARNING (Boundary)", $service->determineRiskLevel(30) === 'WARNING');
assertRiskTest("Score 45 is WARNING", $service->determineRiskLevel(45) === 'WARNING');
assertRiskTest("Score 59 is WARNING (Boundary)", $service->determineRiskLevel(59) === 'WARNING');
assertRiskTest("Score 60 is HIGH RISK (Boundary)", $service->determineRiskLevel(60) === 'HIGH RISK');
assertRiskTest("Score 75 is HIGH RISK", $service->determineRiskLevel(75) === 'HIGH RISK');
assertRiskTest("Score 100 is HIGH RISK (Maximum)", $service->determineRiskLevel(100) === 'HIGH RISK');

// -------------------------------------------------------------
// 3. Setup Test Exam & Participants in Database
// -------------------------------------------------------------
echo "\n--- 3. Setting Up Test Exam & Multi-Scenario Participants ---\n";

$db->query("DELETE FROM exams WHERE title LIKE '%Tahap 12 Risk Test%'")->execute();

$examModel = new ExamModel();
$examData = [
    'title' => 'Tahap 12 Risk Test Exam',
    'description' => 'Ujian pengujian kalkulasi skor risiko proctoring.',
    'subject_id' => 1,
    'class_id' => 1,
    'duration_minutes' => 60,
    'start_time' => date('Y-m-d H:i:s', strtotime('-10 minutes')),
    'end_time' => date('Y-m-d H:i:s', strtotime('+2 hours')),
    'token' => 'RISK12',
    'is_random_question' => 0,
    'is_random_option' => 0,
    'enable_proctoring' => 1,
    'status' => 'published',
    'total_score' => 100.00,
    'passing_score' => 60.00
];
$examId = $examModel->createExamWithQuestions(1, $examData, [1, 2], 1);
assertRiskTest("Test exam created (ID: $examId)", $examId > 0);

// Setup 4 Participants:
// P1 (Ani, ID 1): 0 events -> NORMAL (Score 0)
// P2 (Bimo, ID 2): 3x FACE_NOT_DETECTED (3*10=30) -> WARNING (Score 30)
// P3 (Citra, ID 3): 1x MULTIPLE_FACE (40) + 3x FACE_NOT_DETECTED (30) = 70 -> HIGH RISK (Score 70)
// P4 (Dedi, ID 4/dummy): Excessive events (150 pts) -> Capped at 100

// Clean prior events
$db->query("DELETE FROM proctoring_events WHERE exam_id = :eid")->bind(':eid', $examId)->execute();

// P1: Ani (Student ID 1)
$db->query("
    INSERT INTO exam_participants (exam_id, student_id, status, start_time, risk_score)
    VALUES (:eid, 1, 'in_progress', NOW() - INTERVAL 10 MINUTE, 0.00)
    ON DUPLICATE KEY UPDATE status = 'in_progress', start_time = NOW() - INTERVAL 10 MINUTE, risk_score = 0.00
")->bind(':eid', $examId)->execute();
$pidAni = (int)$db->query("SELECT id FROM exam_participants WHERE exam_id = :eid AND student_id = 1 LIMIT 1")
    ->bind(':eid', $examId)->single()['id'];

// P2: Bimo (Student ID 2)
$db->query("
    INSERT INTO exam_participants (exam_id, student_id, status, start_time, risk_score)
    VALUES (:eid, 2, 'in_progress', NOW() - INTERVAL 10 MINUTE, 0.00)
    ON DUPLICATE KEY UPDATE status = 'in_progress', start_time = NOW() - INTERVAL 10 MINUTE, risk_score = 0.00
")->bind(':eid', $examId)->execute();
$pidBimo = (int)$db->query("SELECT id FROM exam_participants WHERE exam_id = :eid AND student_id = 2 LIMIT 1")
    ->bind(':eid', $examId)->single()['id'];

// P3: Citra (Student ID 3)
$db->query("
    INSERT INTO exam_participants (exam_id, student_id, status, start_time, risk_score)
    VALUES (:eid, 3, 'in_progress', NOW() - INTERVAL 10 MINUTE, 0.00)
    ON DUPLICATE KEY UPDATE status = 'in_progress', start_time = NOW() - INTERVAL 10 MINUTE, risk_score = 0.00
")->bind(':eid', $examId)->execute();
$pidCitra = (int)$db->query("SELECT id FROM exam_participants WHERE exam_id = :eid AND student_id = 3 LIMIT 1")
    ->bind(':eid', $examId)->single()['id'];

assertRiskTest("3 test participants registered", $pidAni > 0 && $pidBimo > 0 && $pidCitra > 0);

// -------------------------------------------------------------
// 4. Test Scenario: No Events (NORMAL, Score 0)
// -------------------------------------------------------------
echo "\n--- 4. Testing Scenario 1: No Events (NORMAL) ---\n";

$resAni = $service->calculateRisk($examId, 1);
assertRiskTest("Ani risk_score is 0", $resAni['risk_score'] === 0);
assertRiskTest("Ani risk_level is NORMAL", $resAni['risk_level'] === 'NORMAL');
assertRiskTest("Ani needs_review is false", $resAni['needs_review'] === false);
assertRiskTest("Ani event_summary Multiple Face count is 0", $resAni['event_summary']['Multiple Face'] === 0);
assertRiskTest("Ani event_summary Face Missing count is 0", $resAni['event_summary']['Face Missing'] === 0);

// -------------------------------------------------------------
// 5. Test Scenario: Warning Events (Score 30, WARNING)
// -------------------------------------------------------------
echo "\n--- 5. Testing Scenario 2: Warning Events (Score 30-59) ---\n";

// Insert 3x FACE_NOT_DETECTED (3 * 10 = 30)
for ($i = 0; $i < 3; $i++) {
    $db->query("
        INSERT INTO proctoring_events (exam_id, student_id, exam_participant_id, event_type, severity, event_time)
        VALUES (:eid, 2, :pid, 'FACE_NOT_DETECTED', 'WARNING', NOW() - INTERVAL " . (3 - $i) . " MINUTE)
    ")->bind(':eid', $examId)->bind(':pid', $pidBimo)->execute();
}

$resBimo = $service->calculateRisk($examId, 2);
assertRiskTest("Bimo risk_score is exactly 30 (3 x 10)", $resBimo['risk_score'] === 30);
assertRiskTest("Bimo risk_level is WARNING", $resBimo['risk_level'] === 'WARNING');
assertRiskTest("Bimo needs_review is false", $resBimo['needs_review'] === false);
assertRiskTest("Bimo event_summary Face Missing is 3", $resBimo['event_summary']['Face Missing'] === 3);
assertRiskTest("Bimo event_summary Camera Disconnect is 0", $resBimo['event_summary']['Camera Disconnect'] === 0);

// Verify DB synchronization
$bimoDbScore = (float)$db->query("SELECT risk_score FROM exam_participants WHERE id = :pid")
    ->bind(':pid', $pidBimo)->single()['risk_score'];
assertRiskTest("Bimo risk_score is synced to exam_participants table (30.00)", $bimoDbScore == 30.00);

// -------------------------------------------------------------
// 6. Test Scenario: High-Risk Events (Score 70, HIGH RISK)
// -------------------------------------------------------------
echo "\n--- 6. Testing Scenario 3: High-Risk Events (Score >= 60) ---\n";

// Insert 1x MULTIPLE_FACE (40) + 3x FACE_NOT_DETECTED (30) = 70
$db->query("
    INSERT INTO proctoring_events (exam_id, student_id, exam_participant_id, event_type, severity, event_time)
    VALUES (:eid, 3, :pid, 'MULTIPLE_FACE', 'CRITICAL', NOW() - INTERVAL 4 MINUTE)
")->bind(':eid', $examId)->bind(':pid', $pidCitra)->execute();

for ($i = 0; $i < 3; $i++) {
    $db->query("
        INSERT INTO proctoring_events (exam_id, student_id, exam_participant_id, event_type, severity, event_time)
        VALUES (:eid, 3, :pid, 'FACE_NOT_DETECTED', 'WARNING', NOW() - INTERVAL " . (3 - $i) . " MINUTE)
    ")->bind(':eid', $examId)->bind(':pid', $pidCitra)->execute();
}

$resCitra = $service->calculateRisk($examId, 3);
assertRiskTest("Citra risk_score is exactly 70 (40 + 30)", $resCitra['risk_score'] === 70);
assertRiskTest("Citra risk_level is HIGH RISK", $resCitra['risk_level'] === 'HIGH RISK');
assertRiskTest("Citra risk_label is 'High Risk (Needs Review)'", $resCitra['risk_label'] === 'High Risk (Needs Review)');
assertRiskTest("Citra needs_review is true", $resCitra['needs_review'] === true);
assertRiskTest("Citra event_summary Multiple Face is 1", $resCitra['event_summary']['Multiple Face'] === 1);
assertRiskTest("Citra event_summary Face Missing is 3", $resCitra['event_summary']['Face Missing'] === 3);

// -------------------------------------------------------------
// 7. Test Score Boundary & Capping at 100
// -------------------------------------------------------------
echo "\n--- 7. Testing Score Capping at 100 ---\n";

// Add multiple heavy events to Citra: 2x FACE_CHANGED (100) -> total raw score = 170
for ($i = 0; $i < 2; $i++) {
    $db->query("
        INSERT INTO proctoring_events (exam_id, student_id, exam_participant_id, event_type, severity, event_time)
        VALUES (:eid, 3, :pid, 'FACE_CHANGED', 'CRITICAL', NOW() - INTERVAL 1 MINUTE)
    ")->bind(':eid', $examId)->bind(':pid', $pidCitra)->execute();
}

$resCapped = $service->calculateRisk($examId, 3);
assertRiskTest("Citra raw score 170 is capped at 100", $resCapped['risk_score'] === 100);
assertRiskTest("Citra risk_level is HIGH RISK", $resCapped['risk_level'] === 'HIGH RISK');

// -------------------------------------------------------------
// 8. Integration with Teacher Monitoring API
// -------------------------------------------------------------
echo "\n--- 8. Testing Integration with Teacher Live Monitoring API ---\n";

httpPost("$baseUrl/auth/authenticate", ['username' => 'guru_budi', 'password' => 'password123'], $teacherCookie);

$dataRes = httpGet("$baseUrl/teacher/monitoringData/$examId", $teacherCookie);
assertRiskTest("Teacher monitoringData returned HTTP 200", $dataRes['code'] === 200);

$monJson = json_decode($dataRes['body'], true);
assertRiskTest("monitoringData returns participants array", !empty($monJson['participants']));

$participants = $monJson['participants'];
$pCitraMon = null;
$pBimoMon = null;
$pAniMon = null;

foreach ($participants as $p) {
    if ($p['participant_id'] == $pidCitra) $pCitraMon = $p;
    if ($p['participant_id'] == $pidBimo) $pBimoMon = $p;
    if ($p['participant_id'] == $pidAni) $pAniMon = $p;
}

assertRiskTest("Monitoring includes Citra risk_score = 100", (int)($pCitraMon['risk_score'] ?? 0) === 100);
assertRiskTest("Monitoring includes Citra risk_level = HIGH RISK", ($pCitraMon['risk_level'] ?? '') === 'HIGH RISK');
assertRiskTest("Monitoring includes Citra event_summary", isset($pCitraMon['event_summary']['Multiple Face']));

assertRiskTest("Monitoring includes Bimo risk_score = 30", (int)($pBimoMon['risk_score'] ?? 0) === 30);
assertRiskTest("Monitoring includes Bimo risk_level = WARNING", ($pBimoMon['risk_level'] ?? '') === 'WARNING');

assertRiskTest("Monitoring includes Ani risk_score = 0", (int)($pAniMon['risk_score'] ?? -1) === 0);
assertRiskTest("Monitoring includes Ani risk_level = NORMAL", ($pAniMon['risk_level'] ?? '') === 'NORMAL');

// Clean up cookies
@unlink($teacherCookie);

// -------------------------------------------------------------
// Summary
// -------------------------------------------------------------
echo "\n=======================================================\n";
echo "TEST RESULTS: $passed PASSED, $failed FAILED\n";
echo "=======================================================\n";

if ($failed > 0) {
    exit(1);
}
