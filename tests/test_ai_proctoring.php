<?php
/**
 * Test Suite: Tahap 10 AI Proctoring MVP
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
$studentCookie = sys_get_temp_dir() . '/test_ai_proc_student_' . uniqid() . '.txt';
$teacherCookie = sys_get_temp_dir() . '/test_ai_proc_teacher_' . uniqid() . '.txt';

$passed = 0;
$failed = 0;

function assertAiTest(string $desc, bool $condition, string $details = '') {
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

function httpJsonPost($url, array $jsonPayload = [], $cookieFile = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($jsonPayload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Requested-With: XMLHttpRequest'
    ]);
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
echo "RUNNING TEST SUITE: AI PROCTORING MVP (TAHAP 10)\n";
echo "=======================================================\n\n";

$db = Database::getInstance();

// -------------------------------------------------------------
// 0. Database Schema & Migration Verification
// -------------------------------------------------------------
echo "--- 0. Testing Database Schema for Proctoring Events ---\n";

$cols = $db->query("SHOW COLUMNS FROM proctoring_events")->resultSet();
$colNames = array_column($cols, 'Field');

assertAiTest("proctoring_events has 'exam_id' column", in_array('exam_id', $colNames));
assertAiTest("proctoring_events has 'student_id' column", in_array('student_id', $colNames));
assertAiTest("proctoring_events has 'severity' column", in_array('severity', $colNames));
assertAiTest("proctoring_events has 'event_type' column", in_array('event_type', $colNames));
assertAiTest("proctoring_events has 'metadata_json' column", in_array('metadata_json', $colNames));
assertAiTest("proctoring_events has 'snapshot_path' column", in_array('snapshot_path', $colNames));

// -------------------------------------------------------------
// 1. Setup Test Exam with Proctoring
// -------------------------------------------------------------
echo "\n--- 1. Setting up Proctored Test Exam & Student Session ---\n";

$db->query("DELETE FROM exams WHERE title LIKE '%Tahap 10 AI Exam%'")->execute();

$examModel = new ExamModel();
$examData = [
    'title' => 'Tahap 10 AI Exam Test',
    'description' => 'Ujian Pengujian AI Proctoring',
    'subject_id' => 1,
    'class_id' => 1,
    'duration_minutes' => 60,
    'start_time' => date('Y-m-d H:i:s', strtotime('-10 minutes')),
    'end_time' => date('Y-m-d H:i:s', strtotime('+2 hours')),
    'token' => 'AI10TK',
    'is_random_question' => 0,
    'is_random_option' => 0,
    'enable_proctoring' => 1,
    'status' => 'published',
    'total_score' => 100.00,
    'passing_score' => 70.00
];
$examId = $examModel->createExamWithQuestions(1, $examData, [1, 2], 1);
assertAiTest("Proctored exam created with enable_proctoring = 1 (ID: $examId)", $examId > 0);

// Student login
$loginRes = httpPost("$baseUrl/auth/authenticate", [
    'username' => 'siswa_ani',
    'password' => 'password123'
], $studentCookie);
assertAiTest("Student Ani logged in successfully", $loginRes['code'] === 302);

// Token verification for exam
httpPost("$baseUrl/student/startExam/$examId", ['token' => 'AI10TK'], $studentCookie);

// Enter exam to set participant status to in_progress
httpPost("$baseUrl/student/enterExam/$examId", [], $studentCookie);

$student = $db->query("SELECT id FROM students WHERE user_id = 3 LIMIT 1")->single();
$studentId = (int)$student['id'];

$participant = $db->query("SELECT id, status FROM exam_participants WHERE exam_id = :eid AND student_id = :sid LIMIT 1")
    ->bind(':eid', $examId)
    ->bind(':sid', $studentId)
    ->single();
$participantId = (int)$participant['id'];
assertAiTest("Student Ani participant record active 'in_progress' (ID: $participantId)", 
    $participantId > 0 && $participant['status'] === 'in_progress');

// -------------------------------------------------------------
// 2. Privacy & Consent Notice Verification
// -------------------------------------------------------------
echo "\n--- 2. Testing Privacy & Consent Notice (Privacy by Design) ---\n";

$sysCheckRes = httpGet("$baseUrl/student/systemCheck/$examId", $studentCookie);
assertAiTest("System check page loads with HTTP 200", $sysCheckRes['code'] === 200);
assertAiTest("System check contains Privacy & Consent box (#aiPrivacyConsentBox)", 
    strpos($sysCheckRes['body'], 'id="aiPrivacyConsentBox"') !== false);
assertAiTest("Consent notice mentions 'Privacy by Design'", 
    strpos($sysCheckRes['body'], 'Privacy by Design') !== false);
assertAiTest("Consent notice mentions 'Tanpa Rekaman Video Penuh'", 
    strpos($sysCheckRes['body'], 'Tanpa Rekaman Video Penuh') !== false);
assertAiTest("Consent notice mentions 'Bukan Vonis Otomatis'", 
    strpos($sysCheckRes['body'], 'Bukan Vonis Otomatis') !== false);
assertAiTest("Integrity pledge includes privacy consent statement", 
    strpos($sysCheckRes['body'], 'Persetujuan Privasi & Pakta Integritas') !== false);

// -------------------------------------------------------------
// 3. Client AI Proctoring Engine Script Verification
// -------------------------------------------------------------
echo "\n--- 3. Testing Client AI Engine (ai-proctoring.js) ---\n";

$aiJsFile = __DIR__ . '/../public/js/ai-proctoring.js';
assertAiTest("ai-proctoring.js file exists", file_exists($aiJsFile));

$aiJsContent = file_get_contents($aiJsFile);
assertAiTest("JS defines AiProctoringEngine class", strpos($aiJsContent, 'class AiProctoringEngine') !== false);
assertAiTest("JS includes configurable checkIntervalMs", strpos($aiJsContent, 'checkIntervalMs') !== false);
assertAiTest("JS includes configurable faceMissingThresholdMs", strpos($aiJsContent, 'faceMissingThresholdMs') !== false);
assertAiTest("JS includes configurable multipleFaceThresholdMs", strpos($aiJsContent, 'multipleFaceThresholdMs') !== false);
assertAiTest("JS includes configurable cameraBlockedThresholdMs", strpos($aiJsContent, 'cameraBlockedThresholdMs') !== false);
assertAiTest("JS includes configurable eventCooldownMs", strpos($aiJsContent, 'eventCooldownMs') !== false);
assertAiTest("JS implements downscaled canvas processing (320x240)", 
    strpos($aiJsContent, 'canvasWidth: 320') !== false && strpos($aiJsContent, 'canvasHeight: 240') !== false);
assertAiTest("JS implements checkLuminanceAndBlocked method", strpos($aiJsContent, 'checkLuminanceAndBlocked') !== false);
assertAiTest("JS implements detectFacesHeuristic method", strpos($aiJsContent, 'detectFacesHeuristic') !== false);
assertAiTest("JS implements checkGazeDeviation method", strpos($aiJsContent, 'checkGazeDeviation') !== false);
assertAiTest("JS implements anomaly duration debouncing before triggering", strpos($aiJsContent, 'this.anomalyDurations') !== false);

// -------------------------------------------------------------
// 4. Exam Room AI Proctoring Integration
// -------------------------------------------------------------
echo "\n--- 4. Testing Exam Room AI Proctoring Integration ---\n";

$roomRes = httpGet("$baseUrl/student/examRoom/$examId", $studentCookie);
assertAiTest("Exam room loads with HTTP 200", $roomRes['code'] === 200);
assertAiTest("Exam room includes ai-proctoring.js script", strpos($roomRes['body'], 'ai-proctoring.js') !== false);
assertAiTest("Exam room PIP widget includes AI face badge (#aiFaceBadge)", strpos($roomRes['body'], 'id="aiFaceBadge"') !== false);
assertAiTest("Exam room initializes AiProctoringEngine in script", strpos($roomRes['body'], 'new AiProctoringEngine') !== false);
assertAiTest("Exam room script stops AI engine on submit/unload", strpos($roomRes['body'], 'aiEngine.stop()') !== false);

// -------------------------------------------------------------
// 5. Event Logger API & Minimal Events Verification
// -------------------------------------------------------------
echo "\n--- 5. Testing Event Logger API & Required Events ---\n";

// Clear previous events for this participant
$db->query("DELETE FROM proctoring_events WHERE exam_participant_id = :pid")->bind(':pid', $participantId)->execute();

// Event 1: FACE_NOT_DETECTED (WARNING)
$ev1 = httpJsonPost("$baseUrl/student/logProctoringEvent", [
    'exam_id' => $examId,
    'event_type' => 'FACE_NOT_DETECTED',
    'severity' => 'WARNING',
    'metadata' => ['detail' => 'Face missing for 3 seconds']
], $studentCookie);

$res1 = json_decode($ev1['body'], true);
assertAiTest("Logging FACE_NOT_DETECTED returned success=true", ($res1['success'] ?? false) === true);
assertAiTest("FACE_NOT_DETECTED was logged to database", ($res1['logged'] ?? false) === true);
assertAiTest("FACE_NOT_DETECTED severity is WARNING", ($res1['severity'] ?? '') === 'WARNING');

// Event 2: MULTIPLE_FACE (CRITICAL)
$ev2 = httpJsonPost("$baseUrl/student/logProctoringEvent", [
    'exam_id' => $examId,
    'event_type' => 'MULTIPLE_FACE',
    'severity' => 'CRITICAL',
    'metadata' => ['face_count' => 2, 'detail' => '2 faces detected']
], $studentCookie);

$res2 = json_decode($ev2['body'], true);
assertAiTest("Logging MULTIPLE_FACE returned success=true", ($res2['success'] ?? false) === true);
assertAiTest("MULTIPLE_FACE was logged with severity CRITICAL", ($res2['severity'] ?? '') === 'CRITICAL');

// Event 3: CAMERA_DISCONNECTED (WARNING)
$ev3 = httpJsonPost("$baseUrl/student/logProctoringEvent", [
    'exam_id' => $examId,
    'event_type' => 'CAMERA_DISCONNECTED',
    'severity' => 'WARNING',
    'metadata' => ['detail' => 'Camera track ended unexpectedly']
], $studentCookie);

$res3 = json_decode($ev3['body'], true);
assertAiTest("Logging CAMERA_DISCONNECTED returned success=true", ($res3['success'] ?? false) === true);

// Event 4: CAMERA_BLOCKED (WARNING)
$ev4 = httpJsonPost("$baseUrl/student/logProctoringEvent", [
    'exam_id' => $examId,
    'event_type' => 'CAMERA_BLOCKED',
    'severity' => 'WARNING',
    'metadata' => ['avg_luminance' => 4.2]
], $studentCookie);

$res4 = json_decode($ev4['body'], true);
assertAiTest("Logging CAMERA_BLOCKED returned success=true", ($res4['success'] ?? false) === true);

// Event 5: SUSPICIOUS_GAZE (WARNING)
$ev5 = httpJsonPost("$baseUrl/student/logProctoringEvent", [
    'exam_id' => $examId,
    'event_type' => 'SUSPICIOUS_GAZE',
    'severity' => 'WARNING',
    'metadata' => ['horizontal_deviation' => 0.82]
], $studentCookie);

$res5 = json_decode($ev5['body'], true);
assertAiTest("Logging SUSPICIOUS_GAZE returned success=true", ($res5['success'] ?? false) === true);

// Verify database entries
$eventsDb = $db->query("
    SELECT event_type, severity, exam_id, student_id, metadata_json 
    FROM proctoring_events 
    WHERE exam_participant_id = :pid
    ORDER BY id ASC
")->bind(':pid', $participantId)->resultSet();

assertAiTest("Database contains exactly 5 logged proctoring events", count($eventsDb) === 5);
$typesInDb = array_column($eventsDb, 'event_type');
assertAiTest("DB has FACE_NOT_DETECTED", in_array('FACE_NOT_DETECTED', $typesInDb));
assertAiTest("DB has MULTIPLE_FACE", in_array('MULTIPLE_FACE', $typesInDb));
assertAiTest("DB has CAMERA_DISCONNECTED", in_array('CAMERA_DISCONNECTED', $typesInDb));
assertAiTest("DB has CAMERA_BLOCKED", in_array('CAMERA_BLOCKED', $typesInDb));
assertAiTest("DB has SUSPICIOUS_GAZE", in_array('SUSPICIOUS_GAZE', $typesInDb));
assertAiTest("Events correctly mapped to exam_id and student_id", 
    !empty($eventsDb) && $eventsDb[0]['exam_id'] == $examId && $eventsDb[0]['student_id'] == $studentId);

// -------------------------------------------------------------
// 6. Cooldown & Debounce Verification
// -------------------------------------------------------------
echo "\n--- 6. Testing Cooldown & Debounce Mechanism ---\n";

// Attempt to log FACE_NOT_DETECTED immediately again (cooldown is 15s)
$evDebounce = httpJsonPost("$baseUrl/student/logProctoringEvent", [
    'exam_id' => $examId,
    'event_type' => 'FACE_NOT_DETECTED',
    'severity' => 'WARNING',
    'metadata' => ['detail' => 'Immediate frame attempt']
], $studentCookie);

$resDebounce = json_decode($evDebounce['body'], true);
assertAiTest("Immediate duplicate event returned cooldown_active=true", ($resDebounce['cooldown_active'] ?? false) === true);
assertAiTest("Duplicate event was NOT logged (logged=false)", ($resDebounce['logged'] ?? true) === false);
assertAiTest("Reason is 'cooldown_active'", ($resDebounce['reason'] ?? '') === 'cooldown_active');

// Verify DB count did NOT increase
$countAfterSpam = $db->query("SELECT COUNT(*) AS total FROM proctoring_events WHERE exam_participant_id = :pid")
    ->bind(':pid', $participantId)
    ->single();
assertAiTest("DB event count remained unchanged (no spamming)", (int)$countAfterSpam['total'] === 5);

// -------------------------------------------------------------
// 7. Evidence Snapshot Handling
// -------------------------------------------------------------
echo "\n--- 7. Testing Configurable Evidence Snapshot ---\n";

// Valid minimal 1x1 base64 JPEG
$dummyJpegBase64 = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=';

// Log a different event: FACE_CHANGED with evidence snapshot
$evSnap = httpJsonPost("$baseUrl/student/logProctoringEvent", [
    'exam_id' => $examId,
    'event_type' => 'FACE_CHANGED',
    'severity' => 'CRITICAL',
    'metadata' => ['similarity_score' => 0.42],
    'evidence_base64' => $dummyJpegBase64
], $studentCookie);

$resSnap = json_decode($evSnap['body'], true);
assertAiTest("FACE_CHANGED event logged with snapshot payload", ($resSnap['logged'] ?? false) === true);

// Check if snapshot_path was saved in DB
$snapRecord = $db->query("
    SELECT snapshot_path 
    FROM proctoring_events 
    WHERE exam_participant_id = :pid AND event_type = 'FACE_CHANGED'
    LIMIT 1
")->bind(':pid', $participantId)->single();

assertAiTest("Snapshot path is recorded in DB", !empty($snapRecord['snapshot_path']));
$fullSnapPath = __DIR__ . '/../public/' . $snapRecord['snapshot_path'];
assertAiTest("Snapshot file physically exists on disk", file_exists($fullSnapPath));

// Clean up test snapshot
if (file_exists($fullSnapPath)) {
    @unlink($fullSnapPath);
}

// -------------------------------------------------------------
// 8. Security & Unauthorized Access
// -------------------------------------------------------------
echo "\n--- 8. Testing Security & Unauthorized Access ---\n";

// Unauthenticated attempt (empty cookie)
$unauthRes = httpJsonPost("$baseUrl/student/logProctoringEvent", [
    'exam_id' => $examId,
    'event_type' => 'FACE_NOT_DETECTED'
], null);

assertAiTest("Unauthenticated logging request is redirected/rejected", 
    in_array($unauthRes['code'], [302, 401, 403]));

// Logging for wrong/unstarted exam
$wrongExamRes = httpJsonPost("$baseUrl/student/logProctoringEvent", [
    'exam_id' => 999999,
    'event_type' => 'FACE_NOT_DETECTED'
], $studentCookie);

assertAiTest("Logging for non-assigned exam returns 404", $wrongExamRes['code'] === 404);

// -------------------------------------------------------------
// 9. Teacher Answer Review & Proctoring Log Section
// -------------------------------------------------------------
echo "\n--- 9. Testing Teacher Answer Review & Proctoring Log Section ---\n";

$teacherLoginRes = httpPost("$baseUrl/auth/authenticate", [
    'username' => 'guru_budi',
    'password' => 'password123'
], $teacherCookie);
assertAiTest("Teacher Budi logged in successfully", $teacherLoginRes['code'] === 302);

$reviewRes = httpGet("$baseUrl/teacher/reviewAnswers/$participantId", $teacherCookie);
assertAiTest("Teacher reviewAnswers page loads with HTTP 200", $reviewRes['code'] === 200);
assertAiTest("Review page contains AI Proctoring Log section (#proctoringLogSection)", 
    strpos($reviewRes['body'], 'id="proctoringLogSection"') !== false);
assertAiTest("Review page displays 'Log Pengawasan AI Proctoring'", 
    strpos($reviewRes['body'], 'Log Pengawasan AI Proctoring') !== false);
assertAiTest("Review page lists logged event 'FACE_NOT_DETECTED'", 
    strpos($reviewRes['body'], 'FACE_NOT_DETECTED') !== false);
assertAiTest("Review page lists logged event 'MULTIPLE_FACE'", 
    strpos($reviewRes['body'], 'MULTIPLE_FACE') !== false);
assertAiTest("Review page displays severity badges (CRITICAL/WARNING)", 
    strpos($reviewRes['body'], 'CRITICAL') !== false && strpos($reviewRes['body'], 'WARNING') !== false);

// Clean up cookies
@unlink($studentCookie);
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
