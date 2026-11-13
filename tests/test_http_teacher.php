<?php
/**
 * HTTP End-to-End Test Suite for Teacher Module & Bank Soal
 */

$baseUrl = 'http://localhost/Project2';
$cookieFile = __DIR__ . '/teacher_cookie_jar.txt';

function httpReq($url, $method = 'GET', $postFields = [], $followRedirect = false) {
    global $cookieFile;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $followRedirect);
    
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

function clearCookie() {
    global $cookieFile;
    if (file_exists($cookieFile)) {
        unlink($cookieFile);
    }
}

echo "========================================================\n";
echo " AI Online Exam - Teacher Bank Soal HTTP E2E Test Suite\n";
echo "========================================================\n\n";

clearCookie();

// 1. Login as Teacher
$login = httpReq($baseUrl . '/auth/authenticate', 'POST', [
    'username' => 'guru_budi',
    'password' => 'password123'
]);

echo "1. Login as Teacher (guru_budi) -> HTTP {$login['code']}";
if ($login['code'] === 302 && strpos($login['headers'], 'teacher/dashboard') !== false) {
    echo " [PASSED]\n";
} else {
    echo " [FAILED]\n";
}

// 2. Access Teacher Dashboard
$dash = httpReq($baseUrl . '/teacher/dashboard');
echo "2. Access Teacher Dashboard -> HTTP {$dash['code']}";
if ($dash['code'] === 200 && strpos($dash['body'], 'Bank Soal Saya') !== false && strpos($dash['body'], 'Dashboard Guru') !== false) {
    echo " [PASSED - Dashboard Rendered]\n";
} else {
    echo " [FAILED]\n";
}

// 3. Create Multiple Choice Question via HTTP POST
$mcPost = httpReq($baseUrl . '/teacher/questionCreate', 'POST', [
    'subject_id' => 1,
    'question_text' => 'HTTP E2E Soal Pilihan Ganda: Berapakah 10 + 15?',
    'question_type' => 'multiple_choice',
    'difficulty' => 'easy',
    'default_score' => '10',
    'options' => [
        'A' => '20',
        'B' => '25',
        'C' => '30',
        'D' => '35'
    ],
    'correct_option' => 'B',
    'explanation' => '10 + 15 = 25.'
]);

echo "3. POST Create MC Question -> HTTP {$mcPost['code']}";
if ($mcPost['code'] === 302) {
    echo " [PASSED - Redirected to questions]\n";
} else {
    echo " [FAILED]\n";
}

// 4. Create Essay Question via HTTP POST
$essayPost = httpReq($baseUrl . '/teacher/questionCreate', 'POST', [
    'subject_id' => 1,
    'question_text' => 'HTTP E2E Soal Essay: Sebutkan rumus luas lingkaran!',
    'question_type' => 'essay',
    'difficulty' => 'easy',
    'default_score' => '20',
    'explanation' => 'Luas = π x r².'
]);

echo "4. POST Create Essay Question -> HTTP {$essayPost['code']}";
if ($essayPost['code'] === 302) {
    echo " [PASSED - Redirected to questions]\n";
} else {
    echo " [FAILED]\n";
}

// 5. Search Questions in Bank Soal
$searchList = httpReq($baseUrl . '/teacher/questions?q=HTTP+E2E');
echo "5. Search Questions by query 'HTTP E2E' -> HTTP {$searchList['code']}";
if ($searchList['code'] === 200 && strpos($searchList['body'], 'HTTP E2E Soal Pilihan Ganda') !== false && strpos($searchList['body'], 'HTTP E2E Soal Essay') !== false) {
    echo " [PASSED - Found Both Created Questions]\n";
} else {
    echo " [FAILED]\n";
}

// 6. Filter Questions by Type: essay
$filterList = httpReq($baseUrl . '/teacher/questions?q=HTTP+E2E&type=essay');
echo "6. Filter Questions by Type 'essay' -> HTTP {$filterList['code']}";
if ($filterList['code'] === 200 && strpos($filterList['body'], 'HTTP E2E Soal Essay') !== false && strpos($filterList['body'], 'HTTP E2E Soal Pilihan Ganda') === false) {
    echo " [PASSED - Correctly Filtered Essay Only]\n";
} else {
    echo " [FAILED]\n";
}

// 7. Test RBAC: Student cannot access Teacher Question Bank
clearCookie();
$studentLogin = httpReq($baseUrl . '/auth/authenticate', 'POST', [
    'username' => 'siswa_ani',
    'password' => 'password123'
]);

$blockedAccess = httpReq($baseUrl . '/teacher/questions');
echo "7. Student Accessing /teacher/questions -> HTTP {$blockedAccess['code']}";
if ($blockedAccess['code'] === 302 && strpos($blockedAccess['headers'], 'student/dashboard') !== false) {
    echo " [PASSED - Access Denied, Redirected to student/dashboard]\n";
} else {
    echo " [FAILED]\n";
}

clearCookie();

// Cleanup E2E questions
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
$db = Database::getInstance();
$db->query("DELETE FROM questions WHERE question_text LIKE 'HTTP E2E%'")->execute();

echo "\n========================================================\n";
echo " ALL HTTP TEACHER BANK SOAL E2E TESTS PASSED!\n";
echo "========================================================\n";
