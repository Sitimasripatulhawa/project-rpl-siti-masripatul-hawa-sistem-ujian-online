<?php
/**
 * Test Suite: Tahap 7 Online Exam Engine
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
$cookieAni = sys_get_temp_dir() . '/test_student_ani_' . uniqid() . '.txt';
$cookieBimo = sys_get_temp_dir() . '/test_student_bimo_' . uniqid() . '.txt';

$passed = 0;
$failed = 0;

function assertTest($description, $condition) {
    global $passed, $failed;
    if ($condition) {
        echo " [PASS] " . $description . "\n";
        $passed++;
    } else {
        echo " [FAIL] " . $description . "\n";
        $failed++;
    }
}

function httpReq($url, $method = 'GET', $postFields = [], $cookieFile = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
    }
    
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
echo "RUNNING TEST SUITE: ONLINE EXAM ENGINE (TAHAP 7)\n";
echo "=======================================================\n\n";

$db = Database::getInstance();

// -------------------------------------------------------------------
// 0. Setup Test Exam Data
// -------------------------------------------------------------------
echo "--- 0. Preparing Test Exam in Database ---\n";
// Ensure questions exist
$qCount = $db->query("SELECT COUNT(*) AS total FROM questions")->single()['total'];
if ($qCount < 5) {
    die("Error: Questions seed not found. Please run database seed.\n");
}

// Clean previous test exams
$db->query("DELETE FROM exams WHERE title LIKE '%Unit Test Exam%'")->execute();

// Create test exam
$examModel = new ExamModel();
$examData = [
    'title' => 'Unit Test Exam Engine Tahap 7',
    'description' => 'Petunjuk pengerjaan ujian matematika unit test.',
    'subject_id' => 1,
    'class_id' => 1,
    'duration_minutes' => 45,
    'start_time' => date('Y-m-d H:i:s', strtotime('-1 hour')),
    'end_time' => date('Y-m-d H:i:s', strtotime('+3 hours')),
    'token' => 'TEST99',
    'is_random_question' => 1,
    'is_random_option' => 1,
    'enable_proctoring' => 0,
    'status' => 'published',
    'total_score' => 100.00,
    'passing_score' => 60.00
];
$questionIds = [1, 2, 3, 4, 5];
$testExamId = $examModel->createExamWithQuestions(1, $examData, $questionIds, 1);
assertTest("Test exam created with ID {$testExamId} and assigned to class 1", $testExamId > 0);

// Get participant ID for student 1 (Ani)
$participantAni = $db->query("SELECT id, exam_id, student_id, status FROM exam_participants WHERE exam_id = :eid AND student_id = 1")
    ->bind(':eid', $testExamId)
    ->single();
assertTest("Participant record created for student Ani (ID: {$participantAni['id']})", !empty($participantAni));

// -------------------------------------------------------------------
// 1. Unauthorized Access Tests
// -------------------------------------------------------------------
echo "\n--- 1. Testing Unauthorized Access ---\n";
// Request without login
$unauthRes = httpReq($baseUrl . '/student/examRoom/' . $testExamId);
assertTest("Unauthenticated request redirected to login", 
    $unauthRes['code'] === 302 && strpos($unauthRes['headers'], 'auth/login') !== false);

// Login as student Ani
$loginAni = httpReq($baseUrl . '/auth/authenticate', 'POST', [
    'username' => 'siswa_ani',
    'password' => 'password123'
], $cookieAni);
assertTest("Student Ani login successful", 
    $loginAni['code'] === 302 && strpos($loginAni['headers'], 'student/dashboard') !== false);

// Try accessing non-existent exam
$invalidExamRes = httpReq($baseUrl . '/student/examDetail/999999', 'GET', [], $cookieAni);
assertTest("Non-assigned/invalid exam access redirected to catalog", 
    $invalidExamRes['code'] === 302 && strpos($invalidExamRes['headers'], 'student/exams') !== false);

// -------------------------------------------------------------------
// 2. Token Verification Test
// -------------------------------------------------------------------
echo "\n--- 2. Testing Token Verification ---\n";
// Wrong token
$wrongTokenRes = httpReq($baseUrl . '/student/startExam/' . $testExamId, 'POST', [
    'token' => 'WRONG1'
], $cookieAni);
assertTest("Wrong token rejected and redirected to detail", 
    $wrongTokenRes['code'] === 302 && strpos($wrongTokenRes['headers'], 'student/examDetail/' . $testExamId) !== false);

// Correct token
$correctTokenRes = httpReq($baseUrl . '/student/startExam/' . $testExamId, 'POST', [
    'token' => 'TEST99'
], $cookieAni);
assertTest("Correct token accepted and redirected to systemCheck", 
    $correctTokenRes['code'] === 302 && strpos($correctTokenRes['headers'], 'student/systemCheck/' . $testExamId) !== false);

// -------------------------------------------------------------------
// 3. Start Exam & Enter Room Test
// -------------------------------------------------------------------
echo "\n--- 3. Testing Start Exam & Status Transitions ---\n";
// Check status before start
$partBefore = $db->query("SELECT status, start_time FROM exam_participants WHERE id = :id")->bind(':id', $participantAni['id'])->single();
assertTest("Initial participant status is 'not_started'", $partBefore['status'] === 'not_started');

// Call enterExam
$enterRes = httpReq($baseUrl . '/student/enterExam/' . $testExamId, 'POST', [], $cookieAni);
assertTest("Enter exam redirected to examRoom", 
    $enterRes['code'] === 302 && strpos($enterRes['headers'], 'student/examRoom/' . $testExamId) !== false);

$partAfter = $db->query("SELECT status, start_time FROM exam_participants WHERE id = :id")->bind(':id', $participantAni['id'])->single();
assertTest("Participant status transitioned to 'in_progress'", $partAfter['status'] === 'in_progress');
assertTest("Participant start_time is recorded", !empty($partAfter['start_time']));

// -------------------------------------------------------------------
// 4. Exam Room & Security (No answer leakage)
// -------------------------------------------------------------------
echo "\n--- 4. Testing Exam Room UI & Security ---\n";
$roomRes = httpReq($baseUrl . '/student/examRoom/' . $testExamId, 'GET', [], $cookieAni);
assertTest("Exam room rendered with HTTP 200", $roomRes['code'] === 200);
assertTest("Header contains exam title", strpos($roomRes['body'], 'Unit Test Exam Engine Tahap 7') !== false);
assertTest("Contains countdown timer element", strpos($roomRes['body'], 'id="examTimer"') !== false);
assertTest("Contains question navigator element", strpos($roomRes['body'], 'id="desktopNavGrid"') !== false);

// CRITICAL SECURITY: Ensure is_correct and explanations are NOT leaked!
assertTest("SECURITY: is_correct is NOT leaked to browser", strpos($roomRes['body'], '"is_correct"') === false);
assertTest("SECURITY: explanation is NOT leaked to browser", strpos($roomRes['body'], 'Titik puncak xp') === false);

// -------------------------------------------------------------------
// 5. Answer Auto-Save & Refresh Resilience
// -------------------------------------------------------------------
echo "\n--- 5. Testing Answer Auto-Save & Persistence ---\n";
// Q1 correct option is (3, -1) which has option ID 1 in seeds
$saveAnsRes = httpReq($baseUrl . '/student/saveAnswer', 'POST', [
    'exam_id' => $testExamId,
    'question_id' => 1,
    'option_id' => 1,
    'is_doubt' => 0
], $cookieAni);

$saveJson = json_decode($saveAnsRes['body'], true);
assertTest("Auto-save answer returned HTTP 200 and success=true", 
    $saveAnsRes['code'] === 200 && ($saveJson['success'] ?? false) === true);

// Save Q2 with doubt flag (Correct answer is option ID 8)
$saveDoubtRes = httpReq($baseUrl . '/student/saveAnswer', 'POST', [
    'exam_id' => $testExamId,
    'question_id' => 2,
    'option_id' => 8, // correct answer for Q2 is 4 (option ID 8)
    'is_doubt' => 1
], $cookieAni);
assertTest("Auto-save answer with doubt flag success", ($saveJson['success'] ?? false) === true);

// Verify in database
$savedAnsRow = $db->query("SELECT * FROM exam_answers WHERE exam_participant_id = :pid AND question_id = 1")
    ->bind(':pid', $participantAni['id'])
    ->single();
assertTest("Database verified: Q1 selected_option_id is 1", (int)$savedAnsRow['selected_option_id'] === 1);

$savedDoubtRow = $db->query("SELECT * FROM exam_answers WHERE exam_participant_id = :pid AND question_id = 2")
    ->bind(':pid', $participantAni['id'])
    ->single();
assertTest("Database verified: Q2 is_flagged_doubt is 1", (int)$savedDoubtRow['is_flagged_doubt'] === 1);

// Test Browser Refresh: Reload room page and check that saved answer is in the JS payload
$refreshRes = httpReq($baseUrl . '/student/examRoom/' . $testExamId, 'GET', [], $cookieAni);
assertTest("Refresh browser: Saved answer retained in room HTML/JS payload", 
    strpos($refreshRes['body'], '"selected_option_id":"1"') !== false || strpos($refreshRes['body'], '"selected_option_id":1') !== false);
assertTest("Refresh browser: Doubt flag retained in room payload", 
    strpos($refreshRes['body'], '"is_flagged_doubt":"1"') !== false || strpos($refreshRes['body'], '"is_flagged_doubt":1') !== false);

// -------------------------------------------------------------------
// 6. Timer Server Truth & Countdown API
// -------------------------------------------------------------------
echo "\n--- 6. Testing Server Timer Endpoint ---\n";
$timeRes = httpReq($baseUrl . '/student/examTime/' . $testExamId, 'GET', [], $cookieAni);
$timeJson = json_decode($timeRes['body'], true);
assertTest("examTime API returned remaining_seconds > 0", 
    ($timeJson['remaining_seconds'] ?? 0) > 0 && ($timeJson['is_expired'] ?? true) === false);

// -------------------------------------------------------------------
// 7. Duplicate Submit & Auto-Scoring Test
// -------------------------------------------------------------------
echo "\n--- 7. Testing Submit & Auto-Scoring ---\n";
// Save remaining answers: Q3 wrong (option ID 11: '1 < x < 2'), Q4 correct (option ID 17: '1/3'), Q5 unanswered
httpReq($baseUrl . '/student/saveAnswer', 'POST', [
    'exam_id' => $testExamId,
    'question_id' => 3,
    'option_id' => 11, // Incorrect (ID 12 is correct)
    'is_doubt' => 0
], $cookieAni);

httpReq($baseUrl . '/student/saveAnswer', 'POST', [
    'exam_id' => $testExamId,
    'question_id' => 4,
    'option_id' => 17, // Correct
    'is_doubt' => 0
], $cookieAni);

// Submit Exam
$submitRes = httpReq($baseUrl . '/student/submitExam/' . $testExamId, 'POST', [], $cookieAni);
assertTest("Submit exam redirected to examResult", 
    $submitRes['code'] === 302 && strpos($submitRes['headers'], 'student/examResult/' . $testExamId) !== false);

// Verify Participant Status in DB
$partSubmitted = $db->query("SELECT status, submit_time FROM exam_participants WHERE id = :id")->bind(':id', $participantAni['id'])->single();
assertTest("Participant status in DB is 'submitted'", $partSubmitted['status'] === 'submitted');
assertTest("Participant submit_time is recorded", !empty($partSubmitted['submit_time']));

// Verify Result in exam_results table: 5 questions total (20 pts each).
// Q1: correct (20), Q2: correct (20), Q3: incorrect (0), Q4: correct (20), Q5: unanswered (0).
// Expected final_score = 60.00, total_correct = 3, total_incorrect = 1, total_unanswered = 1.
// Passing score is 60.00 -> is_passed = 1.
$examResult = $db->query("SELECT * FROM exam_results WHERE exam_participant_id = :pid")->bind(':pid', $participantAni['id'])->single();
assertTest("Auto-scoring: total_correct is 3", (int)$examResult['total_correct'] === 3);
assertTest("Auto-scoring: total_incorrect is 1", (int)$examResult['total_incorrect'] === 1);
assertTest("Auto-scoring: total_unanswered is 1", (int)$examResult['total_unanswered'] === 1);
assertTest("Auto-scoring: final_score is 60.00", (float)$examResult['final_score'] == 60.00);
assertTest("Auto-scoring: is_passed is 1 (Lulus KKM)", (int)$examResult['is_passed'] === 1);

// -------------------------------------------------------------------
// 8. Duplicate Submission Prevention
// -------------------------------------------------------------------
echo "\n--- 8. Testing Duplicate Submit Prevention ---\n";
$dupSubmitRes = httpReq($baseUrl . '/student/submitExam/' . $testExamId, 'POST', [], $cookieAni);
assertTest("Duplicate submit prevented and redirected to result", 
    $dupSubmitRes['code'] === 302 && strpos($dupSubmitRes['headers'], 'student/examResult/' . $testExamId) !== false);

// -------------------------------------------------------------------
// 9. Re-access Prevention after Submit
// -------------------------------------------------------------------
echo "\n--- 9. Testing Re-access Prevention after Submit ---\n";
$reAccessRes = httpReq($baseUrl . '/student/examRoom/' . $testExamId, 'GET', [], $cookieAni);
assertTest("Access to examRoom after submit is blocked and redirected to examResult", 
    $reAccessRes['code'] === 302 && strpos($reAccessRes['headers'], 'student/examResult/' . $testExamId) !== false);

// Verify Result Page rendering
$resultPageRes = httpReq($baseUrl . '/student/examResult/' . $testExamId, 'GET', [], $cookieAni);
assertTest("Result page rendered with HTTP 200", $resultPageRes['code'] === 200);
assertTest("Result page displays final score '60.0'", strpos($resultPageRes['body'], '60.0') !== false);
assertTest("Result page displays 'SELAMAT, ANDA LULUS'", strpos($resultPageRes['body'], 'SELAMAT, ANDA LULUS') !== false);

// -------------------------------------------------------------------
// 10. Timeout Auto-Submit Test
// -------------------------------------------------------------------
echo "\n--- 10. Testing Timeout Auto-Submit ---\n";
// Create another participant for Bimo (student 2)
$loginBimo = httpReq($baseUrl . '/auth/authenticate', 'POST', [
    'username' => 'siswa_bimo',
    'password' => 'password123'
], $cookieBimo);
assertTest("Student Bimo login successful", $loginBimo['code'] === 302);

// Start exam for Bimo
httpReq($baseUrl . '/student/startExam/' . $testExamId, 'POST', ['token' => 'TEST99'], $cookieBimo);
httpReq($baseUrl . '/student/enterExam/' . $testExamId, 'POST', [], $cookieBimo);

$participantBimo = $db->query("SELECT id FROM exam_participants WHERE exam_id = :eid AND student_id = 2")
    ->bind(':eid', $testExamId)
    ->single();

// Simulate expired exam by backdating start_time to 50 minutes ago (exam duration is 45 min)
$db->query("UPDATE exam_participants SET start_time = DATE_SUB(NOW(), INTERVAL 50 MINUTE) WHERE id = :id")
    ->bind(':id', $participantBimo['id'])
    ->execute();

// Check remaining time via API
$timeExpiredRes = httpReq($baseUrl . '/student/examTime/' . $testExamId, 'GET', [], $cookieBimo);
$timeExpiredJson = json_decode($timeExpiredRes['body'], true);
assertTest("Expired exam returns remaining_seconds = 0 and is_expired = true", 
    ($timeExpiredJson['remaining_seconds'] ?? -1) === 0 && ($timeExpiredJson['is_expired'] ?? false) === true);

// Access examRoom when expired -> should auto submit and redirect to examResult!
$expiredRoomRes = httpReq($baseUrl . '/student/examRoom/' . $testExamId, 'GET', [], $cookieBimo);
assertTest("Accessing examRoom when expired triggers auto-submit and redirects to examResult", 
    $expiredRoomRes['code'] === 302 && strpos($expiredRoomRes['headers'], 'student/examResult/' . $testExamId) !== false);

$bimoAfter = $db->query("SELECT status FROM exam_participants WHERE id = :id")->bind(':id', $participantBimo['id'])->single();
assertTest("Participant Bimo is now automatically 'submitted'", $bimoAfter['status'] === 'submitted');

// Clean cookies
@unlink($cookieAni);
@unlink($cookieBimo);

echo "\n=======================================================\n";
echo "TEST RESULTS: {$passed} PASSED, {$failed} FAILED\n";
echo "=======================================================\n";

exit($failed > 0 ? 1 : 0);
