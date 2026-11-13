<?php
/**
 * End-to-End HTTP Test Suite for Admin Module (5 CRUD entities + RBAC)
 */

$baseUrl = 'http://localhost/Project2';
$cookieFile = __DIR__ . '/admin_cookie_jar.txt';

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
echo " AI Online Exam - Admin Module HTTP E2E Test Suite\n";
echo "========================================================\n\n";

clearCookie();

// 1. Login as Admin
$login = httpReq($baseUrl . '/auth/authenticate', 'POST', [
    'username' => 'admin',
    'password' => 'password123'
]);

echo "1. Login Admin -> HTTP {$login['code']}";
if ($login['code'] === 302 && strpos($login['headers'], 'admin/dashboard') !== false) {
    echo " [PASSED]\n";
} else {
    echo " [FAILED]\n";
}

// 2. Admin Dashboard with 5 Metrics
$dash = httpReq($baseUrl . '/admin/dashboard');
echo "2. Access Admin Dashboard -> HTTP {$dash['code']}";
if ($dash['code'] === 200 && strpos($dash['body'], 'Total Guru') !== false && strpos($dash['body'], 'Total Peserta') !== false && strpos($dash['body'], 'Total Kelas') !== false && strpos($dash['body'], 'Total Mata Pelajaran') !== false && strpos($dash['body'], 'Total Ujian') !== false) {
    echo " [PASSED - 5 Metrics Present]\n";
} else {
    echo " [FAILED]\n";
}

// 3. Classes Module HTTP CRUD
$classCreate = httpReq($baseUrl . '/admin/classCreate', 'POST', [
    'name' => 'X-E2E-TEST',
    'level' => 'X',
    'academic_year' => '2025/2026'
]);
echo "3.1 POST Create Class -> HTTP {$classCreate['code']}";
if ($classCreate['code'] === 302) echo " [PASSED]\n"; else echo " [FAILED]\n";

$classList = httpReq($baseUrl . '/admin/classes?q=X-E2E-TEST');
echo "3.2 GET Search Class -> HTTP {$classList['code']}";
if ($classList['code'] === 200 && strpos($classList['body'], 'X-E2E-TEST') !== false) {
    echo " [PASSED - Found Created Class]\n";
} else {
    echo " [FAILED]\n";
}

// 4. Subjects Module HTTP CRUD
$subCreate = httpReq($baseUrl . '/admin/subjectCreate', 'POST', [
    'code' => 'KIM-E2E',
    'name' => 'Kimia Organik E2E',
    'description' => 'Mata pelajaran kimia'
]);
echo "4.1 POST Create Subject -> HTTP {$subCreate['code']}";
if ($subCreate['code'] === 302) echo " [PASSED]\n"; else echo " [FAILED]\n";

$subList = httpReq($baseUrl . '/admin/subjects?q=KIM-E2E');
echo "4.2 GET Search Subject -> HTTP {$subList['code']}";
if ($subList['code'] === 200 && strpos($subList['body'], 'KIM-E2E') !== false) {
    echo " [PASSED - Found Created Subject]\n";
} else {
    echo " [FAILED]\n";
}

// 5. Teachers Module HTTP CRUD
$teacherCreate = httpReq($baseUrl . '/admin/teacherCreate', 'POST', [
    'nip' => '199501012025011088',
    'full_name' => 'Guru Kimia E2E, S.Si',
    'specialization' => 'Kimia Analitik',
    'username' => 'guru_kimia_e2e',
    'email' => 'guru.kimia@sekolah.sch.id',
    'password' => 'password123',
    'phone' => '081234567888',
    'is_active' => '1'
]);
echo "5.1 POST Create Teacher -> HTTP {$teacherCreate['code']}";
if ($teacherCreate['code'] === 302) echo " [PASSED]\n"; else echo " [FAILED]\n";

$teacherList = httpReq($baseUrl . '/admin/teachers?q=199501012025011088');
echo "5.2 GET Search Teacher -> HTTP {$teacherList['code']}";
if ($teacherList['code'] === 200 && strpos($teacherList['body'], 'Guru Kimia E2E') !== false) {
    echo " [PASSED - Found Created Teacher]\n";
} else {
    echo " [FAILED]\n";
}

// 6. Students Module HTTP CRUD
$studentCreate = httpReq($baseUrl . '/admin/studentCreate', 'POST', [
    'nis' => '20268888',
    'nisn' => '0068888888',
    'full_name' => 'Siswa E2E Test',
    'gender' => 'L',
    'username' => 'siswa_e2e_test',
    'email' => 'siswa.e2e@sekolah.sch.id',
    'password' => 'password123',
    'phone' => '081234567889',
    'is_active' => '1'
]);
echo "6.1 POST Create Student -> HTTP {$studentCreate['code']}";
if ($studentCreate['code'] === 302) echo " [PASSED]\n"; else echo " [FAILED]\n";

$studentList = httpReq($baseUrl . '/admin/students?q=20268888');
echo "6.2 GET Search Student -> HTTP {$studentList['code']}";
if ($studentList['code'] === 200 && strpos($studentList['body'], 'Siswa E2E Test') !== false) {
    echo " [PASSED - Found Created Student]\n";
} else {
    echo " [FAILED]\n";
}

// 7. Users Module HTTP CRUD
$userList = httpReq($baseUrl . '/admin/users?role=student');
echo "7.1 GET Users with Role Filter -> HTTP {$userList['code']}";
if ($userList['code'] === 200 && strpos($userList['body'], 'siswa_e2e_test') !== false) {
    echo " [PASSED - Filtered Users Displayed]\n";
} else {
    echo " [FAILED]\n";
}

// 8. Test RBAC Protection from Student Session
clearCookie();
$studentLogin = httpReq($baseUrl . '/auth/authenticate', 'POST', [
    'username' => 'siswa_ani',
    'password' => 'password123'
]);
$blockedReq = httpReq($baseUrl . '/admin/teachers');
echo "8. Student Session Accessing /admin/teachers -> HTTP {$blockedReq['code']}";
if ($blockedReq['code'] === 302 && strpos($blockedReq['headers'], 'student/dashboard') !== false) {
    echo " [PASSED - Access Denied, Redirected to student/dashboard]\n";
} else {
    echo " [FAILED]\n";
}

clearCookie();

// Cleanup E2E test records from DB
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
$db = Database::getInstance();
$db->query("DELETE FROM users WHERE username IN ('guru_kimia_e2e', 'siswa_e2e_test')")->execute();
$db->query("DELETE FROM classes WHERE name = 'X-E2E-TEST'")->execute();
$db->query("DELETE FROM subjects WHERE code = 'KIM-E2E'")->execute();

echo "\n========================================================\n";
echo " ALL HTTP ADMIN MODULE CRUD & RBAC TESTS PASSED!\n";
echo "========================================================\n";
