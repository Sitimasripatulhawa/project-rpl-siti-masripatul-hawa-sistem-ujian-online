<?php
/**
 * HTTP Integration & E2E Test Suite: Teacher Exam Management
 * AI Online Exam & Proctoring System
 */

$baseUrl = 'http://localhost/Project2';
$cookieFile = sys_get_temp_dir() . '/teacher_exam_e2e_' . uniqid() . '.txt';

$passed = 0;
$failed = 0;

function assertHttp($description, $condition) {
    global $passed, $failed;
    if ($condition) {
        echo " [PASS] " . $description . "\n";
        $passed++;
    } else {
        echo " [FAIL] " . $description . "\n";
        $failed++;
    }
}

function httpReq($url, $method = 'GET', $postFields = []) {
    global $cookieFile;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
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
echo "RUNNING HTTP E2E TEST: TEACHER EXAM MANAGEMENT\n";
echo "=======================================================\n\n";

// 1. Teacher Login
$loginRes = httpReq($baseUrl . '/auth/authenticate', 'POST', [
    'username' => 'guru_budi',
    'password' => 'password123'
]);

assertHttp("Teacher login success and redirected to dashboard (HTTP {$loginRes['code']})", 
    $loginRes['code'] === 302 && strpos($loginRes['headers'], 'teacher/dashboard') !== false);

// 2. Check Teacher Dashboard 4 status cards
$dashRes = httpReq($baseUrl . '/teacher/dashboard');
assertHttp("Dashboard displays 'Draft Exams' counter", strpos($dashRes['body'], 'Draft Exams') !== false);
assertHttp("Dashboard displays 'Published Exams' counter", strpos($dashRes['body'], 'Published Exams') !== false);
assertHttp("Dashboard displays 'Active / Running' counter", strpos($dashRes['body'], 'Active / Running') !== false);
assertHttp("Dashboard displays 'Finished Exams' counter", strpos($dashRes['body'], 'Finished Exams') !== false);

// 3. Access Exam Index Page
$indexRes = httpReq($baseUrl . '/teacher/exams');
assertHttp("Exam index page accessible (HTTP 200)", $indexRes['code'] === 200 && strpos($indexRes['body'], 'Manajemen Ujian') !== false);
assertHttp("Exam index contains 'Buat Ujian Baru' button", strpos($indexRes['body'], 'btnCreateExam') !== false);

// 4. Access Exam Create Form
$createPageRes = httpReq($baseUrl . '/teacher/examCreate');
assertHttp("Exam create form accessible (HTTP 200)", $createPageRes['code'] === 200 && strpos($createPageRes['body'], 'Pilih Soal dari Bank Soal') !== false);

$subjectId = 1;
$classId = 1;
$qId = 1;

// 5. Test Validation Failure: Duration <= 0
$failPost1 = httpReq($baseUrl . '/teacher/examCreate', 'POST', [
    'title' => 'Ujian Validasi Gagal',
    'subject_id' => $subjectId,
    'class_id' => $classId,
    'duration_minutes' => 0,
    'start_time' => date('Y-m-d H:i:s', strtotime('+1 day')),
    'end_time' => date('Y-m-d H:i:s', strtotime('+1 day +2 hours')),
    'questions' => [$qId]
]);
assertHttp("Validation 1 rejected with 302 redirect", $failPost1['code'] === 302);
$failGet1 = httpReq($baseUrl . '/teacher/examCreate');
assertHttp("Validation 1 displays error flash message", strpos($failGet1['body'], 'Durasi ujian harus lebih dari 0 menit') !== false);

// 6. Test Validation Failure: Start Time >= End Time
$failPost2 = httpReq($baseUrl . '/teacher/examCreate', 'POST', [
    'title' => 'Ujian Validasi Waktu',
    'subject_id' => $subjectId,
    'class_id' => $classId,
    'duration_minutes' => 60,
    'start_time' => date('Y-m-d H:i:s', strtotime('+2 days')),
    'end_time' => date('Y-m-d H:i:s', strtotime('+1 day')),
    'questions' => [$qId]
]);
assertHttp("Validation 2 rejected with 302 redirect", $failPost2['code'] === 302);
$failGet2 = httpReq($baseUrl . '/teacher/examCreate');
assertHttp("Validation 2 displays error flash message", strpos($failGet2['body'], 'Waktu mulai ujian harus lebih awal') !== false);

// 7. Test Validation Failure: No questions selected
$failPost3 = httpReq($baseUrl . '/teacher/examCreate', 'POST', [
    'title' => 'Ujian Tanpa Soal',
    'subject_id' => $subjectId,
    'class_id' => $classId,
    'duration_minutes' => 60,
    'start_time' => date('Y-m-d H:i:s', strtotime('+1 day')),
    'end_time' => date('Y-m-d H:i:s', strtotime('+1 day +2 hours')),
    'questions' => []
]);
assertHttp("Validation 3 rejected with 302 redirect", $failPost3['code'] === 302);
$failGet3 = httpReq($baseUrl . '/teacher/examCreate');
assertHttp("Validation 3 displays error flash message", strpos($failGet3['body'], 'Minimal pilih 1 butir soal') !== false);

// 8. Create Valid Exam via HTTP POST
$examTitle = 'Ujian Simulasi E2E HTTP #' . time();
$createRes = httpReq($baseUrl . '/teacher/examCreate', 'POST', [
    'title' => $examTitle,
    'description' => 'Deskripsi petunjuk ujian simulasi HTTP E2E.',
    'subject_id' => $subjectId,
    'class_id' => $classId,
    'duration_minutes' => 75,
    'start_time' => date('Y-m-d H:i:s', strtotime('+1 day')),
    'end_time' => date('Y-m-d H:i:s', strtotime('+1 day +2 hours')),
    'token' => 'SIM123',
    'is_random_question' => '1',
    'is_random_option' => '1',
    'enable_proctoring' => '1',
    'status' => 'draft',
    'passing_score' => 70,
    'questions' => [$qId]
]);

assertHttp("Valid exam creation returned 302 redirect to /teacher/exams", 
    $createRes['code'] === 302 && strpos($createRes['headers'], 'teacher/exams') !== false);

$createdExamList = httpReq($baseUrl . '/teacher/exams');
assertHttp("Exams index displays success flash message", strpos($createdExamList['body'], 'Sesi ujian berhasil dibuat') !== false);
assertHttp("Exams index lists the created exam title", strpos($createdExamList['body'], $examTitle) !== false);

// Extract exam ID from link in index
preg_match('/examShow\/([0-9]+)/', $createdExamList['body'], $m);
$createdId = !empty($m[1]) ? (int)$m[1] : 0;
assertHttp("Created exam found in UI with ID ({$createdId})", $createdId > 0);

// 9. Preview Exam via HTTP GET
$showRes = httpReq($baseUrl . '/teacher/examShow/' . $createdId);
assertHttp("Exam Preview page renders title and token", 
    $showRes['code'] === 200 && strpos($showRes['body'], $examTitle) !== false && strpos($showRes['body'], 'SIM123') !== false);
assertHttp("Exam Preview lists assigned question", strpos($showRes['body'], 'Daftar Soal') !== false);
assertHttp("Exam Preview lists class participants", strpos($showRes['body'], 'Peserta Kelas Sasaran') !== false);

// 10. Toggle Publish Status
$toggleRes = httpReq($baseUrl . '/teacher/examTogglePublish/' . $createdId);
assertHttp("Toggle publish returned 302 redirect", $toggleRes['code'] === 302);

$afterToggleList = httpReq($baseUrl . '/teacher/exams');
assertHttp("Exams index shows published status flash", strpos($afterToggleList['body'], 'dipublikasikan (Published)') !== false);

// 11. Edit Exam via HTTP POST
$editRes = httpReq($baseUrl . '/teacher/examEdit/' . $createdId, 'POST', [
    'title' => $examTitle . ' (Edited)',
    'description' => 'Deskripsi ujian yang telah diedit.',
    'subject_id' => $subjectId,
    'class_id' => $classId,
    'duration_minutes' => 120,
    'start_time' => date('Y-m-d H:i:s', strtotime('+2 days')),
    'end_time' => date('Y-m-d H:i:s', strtotime('+2 days +3 hours')),
    'status' => 'published',
    'passing_score' => 85,
    'questions' => [$qId]
]);

assertHttp("Exam edit returned 302 redirect", $editRes['code'] === 302);

$afterEditList = httpReq($baseUrl . '/teacher/exams');
assertHttp("Exams index lists updated exam title", strpos($afterEditList['body'], $examTitle . ' (Edited)') !== false);

// 12. Delete Exam via HTTP GET
$deleteRes = httpReq($baseUrl . '/teacher/examDelete/' . $createdId);
assertHttp("Exam deletion returned 302 redirect", $deleteRes['code'] === 302);

$afterDeleteList = httpReq($baseUrl . '/teacher/exams');
assertHttp("Exams index shows deletion flash message", strpos($afterDeleteList['body'], 'Sesi ujian berhasil dihapus') !== false);

// 13. Test RBAC: Student cannot access Teacher Exam routes
if (file_exists($cookieFile)) unlink($cookieFile);
$studentCookie = sys_get_temp_dir() . '/student_exam_e2e_' . uniqid() . '.txt';
$cookieFile = $studentCookie;

$studentLogin = httpReq($baseUrl . '/auth/authenticate', 'POST', [
    'username' => 'siswa_ani',
    'password' => 'password123'
]);

$studentExamAccess = httpReq($baseUrl . '/teacher/exams');
assertHttp("Student blocked from accessing /teacher/exams (redirected to student/dashboard)", 
    $studentExamAccess['code'] === 302 && strpos($studentExamAccess['headers'], 'student/dashboard') !== false);

// Cleanup cookie
if (file_exists($cookieFile)) unlink($cookieFile);

echo "\n=======================================================\n";
echo "HTTP TEST RESULTS: {$passed} PASSED, {$failed} FAILED\n";
echo "=======================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
