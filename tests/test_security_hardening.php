<?php
/**
 * Test Suite: Tahap 13 Security Hardening
 * AI Online Exam & Proctoring System
 */

require_once __DIR__ . '/../app/init.php';
require_once __DIR__ . '/../app/models/ExamModel.php';
require_once __DIR__ . '/../app/models/ExamEngineModel.php';
require_once __DIR__ . '/../app/models/ProctoringModel.php';
require_once __DIR__ . '/../app/models/UserModel.php';
require_once __DIR__ . '/../app/services/ProctoringRiskService.php';

$baseUrl = 'http://localhost/Project2';
$cookieFile = sys_get_temp_dir() . '/sec_test_' . uniqid() . '.txt';

$passed = 0;
$failed = 0;

function assertSecTest(string $desc, bool $condition, string $details = '') {
    global $passed, $failed;
    if ($condition) {
        echo " [PASS] $desc\n";
        $passed++;
    } else {
        echo " [FAIL] $desc" . ($details ? " ($details)" : '') . "\n";
        $failed++;
    }
}

function curlReq($url, $method = 'GET', $data = [], $cookie = null, $customHeaders = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

    if ($cookie) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
    }

    $headers = $customHeaders;

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if (is_array($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        } else {
            // Raw string (e.g. JSON)
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            $headers[] = 'Content-Type: application/json';
        }
    }

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $respHeaders = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    curl_close($ch);

    return ['code' => $httpCode, 'headers' => $respHeaders, 'body' => $body];
}

echo "=======================================================\n";
echo "RUNNING TEST SUITE: SECURITY HARDENING (TAHAP 13)\n";
echo "=======================================================\n\n";

$db = Database::getInstance();

// -------------------------------------------------------------
// 1. Authentication & Password Security
// -------------------------------------------------------------
echo "--- 1. Testing Authentication & Session Security ---\n";

// Password Hashing Verification
$userRow = $db->query("SELECT password FROM users WHERE username = 'admin' LIMIT 1")->single();
assertSecTest("Password hash starts with \$2y\$ (Bcrypt)", strpos($userRow['password'], '$2y$') === 0);
assertSecTest("Password hash passes password_verify", password_verify('password123', $userRow['password']));
assertSecTest("Wrong password fails password_verify", !password_verify('wrongpassword', $userRow['password']));

// Session Security: no sensitive data in session
$userModel = new UserModel();
$userPayload = $userModel->findByUsernameOrEmail('admin');
Auth::login($userPayload);
$sessionUser = Auth::user();
assertSecTest("Session does NOT contain password", !isset($sessionUser['password']));
assertSecTest("Session contains sanitized ID", isset($sessionUser['id']) && is_int($sessionUser['id']));
assertSecTest("Session contains correct role", ($sessionUser['role'] ?? '') === 'admin');

// Brute force throttling
$dummyId = 'brute_force_victim_' . uniqid();
for ($i = 0; $i < 4; $i++) {
    Auth::recordFailedLogin($dummyId);
}
assertSecTest("Under threshold (4 attempts) is not throttled", !Auth::isLoginThrottled($dummyId));
Auth::recordFailedLogin($dummyId); // 5th attempt
assertSecTest("At threshold (5 attempts) is throttled", Auth::isLoginThrottled($dummyId));
assertSecTest("Remaining lockout time is > 0 seconds", Auth::getRemainingLockoutTime($dummyId) > 0);
Auth::clearFailedLogins($dummyId);
assertSecTest("Cleared failed logins unlocks identifier", !Auth::isLoginThrottled($dummyId));

// Role Authorization checks via HTTP
$studentCookie = sys_get_temp_dir() . '/sec_student_' . uniqid() . '.txt';
curlReq("$baseUrl/auth/authenticate", 'POST', ['username' => 'siswa_ani', 'password' => 'password123'], $studentCookie);

// Student trying to access /teacher/results
$unauthTeacher = curlReq("$baseUrl/teacher/results", 'GET', [], $studentCookie);
assertSecTest("Student accessing /teacher/results is blocked and redirected (302)", $unauthTeacher['code'] === 302);

// Student trying to access /admin/dashboard
$unauthAdmin = curlReq("$baseUrl/admin/dashboard", 'GET', [], $studentCookie);
assertSecTest("Student accessing /admin/dashboard is blocked and redirected (302)", $unauthAdmin['code'] === 302);

// -------------------------------------------------------------
// 2. Database & SQL Injection Resistance
// -------------------------------------------------------------
echo "\n--- 2. Testing Database & SQL Injection Resistance ---\n";

// Test SQL injection in UserModel search
$sqliSearch = "' OR '1'='1' -- ";
$sqliResults = $userModel->getAll($sqliSearch);
assertSecTest("SQL injection payload in user search returns 0 rows (treated as literal)", count($sqliResults) === 0);

// Test SQL injection in countAll
$countSqli = $userModel->countAll($sqliSearch);
assertSecTest("SQL injection in countAll handled safely (0 rows)", $countSqli === 0);

// Test findByUsernameOrEmail with SQL injection
$sqliUser = $userModel->findByUsernameOrEmail("' OR '1'='1' -- ");
assertSecTest("SQL injection in findByUsernameOrEmail returns null", $sqliUser === null);

// -------------------------------------------------------------
// 3. Exam Engine Security & Tampering Prevention
// -------------------------------------------------------------
echo "\n--- 3. Testing Exam Engine Security & Anti-Tampering ---\n";

// Create a test exam for tampering tests
$examModel = new ExamModel();
$secExamData = [
    'title' => 'Security Audit Test Exam',
    'description' => 'Exam for testing tampering and unauthorized access',
    'subject_id' => 1,
    'class_id' => 1,
    'duration_minutes' => 60,
    'start_time' => date('Y-m-d H:i:s', strtotime('-10 minutes')),
    'end_time' => date('Y-m-d H:i:s', strtotime('+2 hours')),
    'token' => 'SECTK1',
    'is_random_question' => 0,
    'is_random_option' => 0,
    'enable_proctoring' => 1,
    'status' => 'published',
    'total_score' => 100.00,
    'passing_score' => 70.00
];
$secExamId = $examModel->createExamWithQuestions(1, $secExamData, [1, 2], 1);

$examEngine = new ExamEngineModel();
$participantAni = $examEngine->getExamParticipant($secExamId, 1);
$pidAni = (int)$participantAni['participant_id'];

// Start exam for Ani
$examEngine->startExam($pidAni);

// Check that is_correct and explanation are NOT returned in student exam room data
$qData = $examEngine->getExamQuestionsAndSavedAnswers($secExamId, $pidAni, false, false);
$hasLeak = false;
foreach ($qData as $q) {
    if (isset($q['is_correct']) || isset($q['explanation'])) $hasLeak = true;
    if (!empty($q['options'])) {
        foreach ($q['options'] as $opt) {
            if (isset($opt['is_correct'])) $hasLeak = true;
        }
    }
}
assertSecTest("SECURITY: is_correct and explanation NOT leaked in student payload", !$hasLeak);

// Test Question Tampering in saveAnswer:
// Question ID 999 is NOT part of this exam
$tamperQRes = $examEngine->saveAnswer($pidAni, 999, 1, null, 0);
assertSecTest("Submitting foreign question_id 999 is rejected", $tamperQRes['success'] === false);
assertSecTest("Rejection message indicates invalid question", strpos($tamperQRes['message'], 'tidak valid') !== false);

// Test Option Tampering in saveAnswer:
// Question ID 1 belongs to exam, but Option ID 99999 does not belong to Question 1
$tamperOptRes = $examEngine->saveAnswer($pidAni, 1, 99999, null, 0);
assertSecTest("Submitting foreign option_id 99999 is rejected", $tamperOptRes['success'] === false);
assertSecTest("Rejection message indicates invalid option", strpos($tamperOptRes['message'], 'tidak valid') !== false);

// Test legitimate answer saves successfully
$legitAnsRes = $examEngine->saveAnswer($pidAni, 1, 1, null, 0);
assertSecTest("Submitting legitimate question & option saves successfully", $legitAnsRes['success'] === true);

// -------------------------------------------------------------
// 4. Proctoring Security & Append-Only Audit Trail
// -------------------------------------------------------------
echo "\n--- 4. Testing Proctoring Hardening & Whitelist ---\n";

// Test unsupported event rejection via HTTP
$badEventReq = curlReq("$baseUrl/student/logProctoringEvent", 'POST', json_encode([
    'exam_id' => $secExamId,
    'event_type' => 'FAKE_UNAUTHORIZED_EVENT',
    'severity' => 'CRITICAL'
]), $studentCookie);
$badEventJson = json_decode($badEventReq['body'], true);
assertSecTest("Submitting non-whitelisted event_type returns HTTP 400", $badEventReq['code'] === 400);
assertSecTest("Error message indicates event_type tidak valid", strpos($badEventJson['message'] ?? '', 'tidak valid') !== false);

// Test client severity manipulation (client sends INFO for MULTIPLE_FACE)
$sevTamperReq = curlReq("$baseUrl/student/logProctoringEvent", 'POST', json_encode([
    'exam_id' => $secExamId,
    'event_type' => 'MULTIPLE_FACE',
    'severity' => 'INFO' // Attempted downgrade
]), $studentCookie);
$sevTamperJson = json_decode($sevTamperReq['body'], true);
assertSecTest("Server enforces authoritative severity (CRITICAL, not downgraded to INFO)", ($sevTamperJson['severity'] ?? '') === 'CRITICAL');

// Test client cannot modify risk score (risk score is not accepted from client)
$riskTamperReq = curlReq("$baseUrl/student/logProctoringEvent", 'POST', json_encode([
    'exam_id' => $secExamId,
    'event_type' => 'FACE_NOT_DETECTED',
    'risk_score' => 0 // Attempted bypass
]), $studentCookie);
$participantAfter = $examEngine->getExamParticipant($secExamId, 1);
assertSecTest("Client cannot overwrite risk_score (calculated via ProctoringRiskService)", (float)$participantAfter['risk_score'] > 0);

// Test upload .htaccess blocks script execution in proctoring uploads
$uploadHtaccess = file_get_contents(__DIR__ . '/../public/uploads/proctoring/.htaccess');
assertSecTest(".htaccess exists in proctoring uploads", !empty($uploadHtaccess));
assertSecTest(".htaccess denies PHP file execution", strpos($uploadHtaccess, 'Deny from all') !== false);

// -------------------------------------------------------------
// 5. CSRF Protection & HTTP Security Headers
// -------------------------------------------------------------
echo "\n--- 5. Testing CSRF Protection & Security Headers ---\n";

// Test HTTP Security Headers
$homeReq = curlReq("$baseUrl/auth/login", 'GET');
assertSecTest("HTTP Header X-Content-Type-Options: nosniff present", strpos($homeReq['headers'], 'X-Content-Type-Options: nosniff') !== false);
assertSecTest("HTTP Header X-Frame-Options: SAMEORIGIN present", strpos($homeReq['headers'], 'X-Frame-Options: SAMEORIGIN') !== false);
assertSecTest("HTTP Header Referrer-Policy present", strpos($homeReq['headers'], 'Referrer-Policy:') !== false);

// Test CSRF Token Generation & Verification
$csrf1 = Csrf::getToken();
assertSecTest("Csrf::getToken returns 64-char hex string (32 bytes)", strlen($csrf1) === 64);
assertSecTest("Csrf::verify succeeds with matching token", Csrf::verify($csrf1));
assertSecTest("Csrf::verify rejects forged token", !Csrf::verify('forged_fake_token_12345'));
assertSecTest("Csrf::verify rejects empty token", !Csrf::verify(''));
assertSecTest("Csrf::tokenField renders valid HTML input", strpos(Csrf::tokenField(), 'name="csrf_token"') !== false);
assertSecTest("Csrf::tokenMeta renders valid meta tag", strpos(Csrf::tokenMeta(), '<meta name="csrf-token"') !== false);

// Test Cross-Site Origin CSRF Blocking
$crossOriginReq = curlReq("$baseUrl/student/saveAnswer", 'POST', [
    'exam_id' => $secExamId,
    'question_id' => 1,
    'option_id' => 1
], $studentCookie, [
    'Origin: http://evil-attacker-site.com',
    'Sec-Fetch-Site: cross-site'
]);
assertSecTest("Cross-site request (evil Origin) is blocked with HTTP 403", $crossOriginReq['code'] === 403);
$crossJson = json_decode($crossOriginReq['body'], true);
assertSecTest("CSRF rejection message indicates cross-site detection", ($crossJson['csrf_error'] ?? false) === true);

// Test Forged CSRF Token Blocking (AJAX returns 403 JSON)
$forgedTokenReq = curlReq("$baseUrl/student/saveAnswer", 'POST', [
    'exam_id' => $secExamId,
    'question_id' => 1,
    'option_id' => 1,
    'csrf_token' => 'invalid_tampered_token_xyz'
], $studentCookie, [
    'X-Requested-With: XMLHttpRequest'
]);
assertSecTest("Forged csrf_token on AJAX is blocked with HTTP 403", $forgedTokenReq['code'] === 403);
$forgedJson = json_decode($forgedTokenReq['body'], true);
assertSecTest("Forged CSRF JSON response has csrf_error flag", ($forgedJson['csrf_error'] ?? false) === true);

// Test Forged CSRF Token on standard Form POST (Redirects with HTTP 302 flash warning)
$forgedFormReq = curlReq("$baseUrl/teacher/saveEssayGrade", 'POST', [
    'participant_id' => $pidAni,
    'question_id' => 1,
    'score' => 10,
    'csrf_token' => 'invalid_tampered_token_xyz'
], $studentCookie);
assertSecTest("Forged csrf_token on Form POST triggers 302 redirect with security warning", $forgedFormReq['code'] === 302);

// Test Forged X-CSRF-Token Header Blocking
$forgedHeaderReq = curlReq("$baseUrl/student/saveAnswer", 'POST', [
    'exam_id' => $secExamId,
    'question_id' => 1,
    'option_id' => 1
], $studentCookie, [
    'X-CSRF-Token: malicious_header_token'
]);
assertSecTest("Forged X-CSRF-Token header is blocked with HTTP 403", $forgedHeaderReq['code'] === 403);

// Test Global XSS Escaping Helper e()
assertSecTest("e() escapes <script> tag", e('<script>alert("xss")</script>') === '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;');
assertSecTest("e() escapes single quotes", strpos(e("O'Connor"), '&#039;') !== false);
assertSecTest("e() handles null gracefully", e(null) === '');

// Cleanup
@unlink($cookieFile);
@unlink($studentCookie);

echo "\n=======================================================\n";
echo "TEST RESULTS: $passed PASSED, $failed FAILED\n";
echo "=======================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
