<?php
/**
 * End-to-End HTTP Integration Test for Authentication & RBAC
 */

$baseUrl = 'http://localhost/Project2';
$cookieFile = __DIR__ . '/cookie_jar.txt';

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
echo " AI Online Exam - HTTP End-to-End Auth & RBAC Tests\n";
echo "========================================================\n\n";

// TEST 1: Unauthenticated protection
clearCookie();
$res = httpReq($baseUrl . '/admin/dashboard');
echo "Test 1: Unauthenticated access to /admin/dashboard -> HTTP {$res['code']}";
if ($res['code'] === 302 && strpos($res['headers'], 'auth/login') !== false) {
    echo " [PASSED - Redirected to login]\n";
} else {
    echo " [FAILED]\n";
}

// TEST 2: Wrong credentials login
clearCookie();
$res = httpReq($baseUrl . '/auth/authenticate', 'POST', [
    'username' => 'admin@sekolah.sch.id',
    'password' => 'wrongpass'
]);
echo "Test 2: Login with wrong password -> HTTP {$res['code']}";
if ($res['code'] === 302 && strpos($res['headers'], 'auth/login') !== false) {
    echo " [PASSED - Rejected & Redirected to login]\n";
} else {
    echo " [FAILED]\n";
}

// TEST 3: Admin Login & Access
clearCookie();
$res = httpReq($baseUrl . '/auth/authenticate', 'POST', [
    'username' => 'admin@sekolah.sch.id',
    'password' => 'password123'
]);
echo "Test 3: Admin Login (admin@sekolah.sch.id / password123) -> HTTP {$res['code']}";
if ($res['code'] === 302 && strpos($res['headers'], 'admin/dashboard') !== false) {
    echo " [PASSED - Redirected to /admin/dashboard]\n";
} else {
    echo " [FAILED]\n";
}

$dash = httpReq($baseUrl . '/admin/dashboard');
echo "        Access /admin/dashboard with Admin session -> HTTP {$dash['code']}";
if ($dash['code'] === 200 && strpos($dash['body'], 'Dashboard Super Admin') !== false) {
    echo " [PASSED - Admin Dashboard Rendered]\n";
} else {
    echo " [FAILED]\n";
}

// TEST 4: Teacher Login & RBAC protection
clearCookie();
$res = httpReq($baseUrl . '/auth/authenticate', 'POST', [
    'username' => 'guru_budi',
    'password' => 'password123'
]);
echo "Test 4: Teacher Login (guru_budi / password123) -> HTTP {$res['code']}";
if ($res['code'] === 302 && strpos($res['headers'], 'teacher/dashboard') !== false) {
    echo " [PASSED - Redirected to /teacher/dashboard]\n";
} else {
    echo " [FAILED]\n";
}

$dash = httpReq($baseUrl . '/teacher/dashboard');
echo "        Access /teacher/dashboard with Teacher session -> HTTP {$dash['code']}";
if ($dash['code'] === 200 && strpos($dash['body'], 'Dashboard Guru') !== false) {
    echo " [PASSED - Teacher Dashboard Rendered]\n";
} else {
    echo " [FAILED]\n";
}

// Test Teacher trying to access Admin Dashboard
$illegal = httpReq($baseUrl . '/admin/dashboard');
echo "        Teacher trying to access /admin/dashboard -> HTTP {$illegal['code']}";
if ($illegal['code'] === 302 && strpos($illegal['headers'], 'teacher/dashboard') !== false) {
    echo " [PASSED - Blocked & Redirected back to /teacher/dashboard]\n";
} else {
    echo " [FAILED]\n";
}

// TEST 5: Student Login & RBAC protection
clearCookie();
$res = httpReq($baseUrl . '/auth/authenticate', 'POST', [
    'username' => 'siswa_ani',
    'password' => 'password123'
]);
echo "Test 5: Student Login (siswa_ani / password123) -> HTTP {$res['code']}";
if ($res['code'] === 302 && strpos($res['headers'], 'student/dashboard') !== false) {
    echo " [PASSED - Redirected to /student/dashboard]\n";
} else {
    echo " [FAILED]\n";
}

$dash = httpReq($baseUrl . '/student/dashboard');
echo "        Access /student/dashboard with Student session -> HTTP {$dash['code']}";
if ($dash['code'] === 200 && strpos($dash['body'], 'Dashboard Peserta Ujian') !== false) {
    echo " [PASSED - Student Dashboard Rendered]\n";
} else {
    echo " [FAILED]\n";
}

// Test Student trying to access Admin Dashboard
$illegal = httpReq($baseUrl . '/admin/dashboard');
echo "        Student trying to access /admin/dashboard -> HTTP {$illegal['code']}";
if ($illegal['code'] === 302 && strpos($illegal['headers'], 'student/dashboard') !== false) {
    echo " [PASSED - Blocked & Redirected back to /student/dashboard]\n";
} else {
    echo " [FAILED]\n";
}

// TEST 6: Logout
$logout = httpReq($baseUrl . '/auth/logout');
echo "Test 6: Logout -> HTTP {$logout['code']}";
if ($logout['code'] === 302 && strpos($logout['headers'], 'auth/login') !== false) {
    echo " [PASSED - Logged out and redirected to login]\n";
} else {
    echo " [FAILED]\n";
}

$postLogout = httpReq($baseUrl . '/student/dashboard');
echo "        Access /student/dashboard after logout -> HTTP {$postLogout['code']}";
if ($postLogout['code'] === 302 && strpos($postLogout['headers'], 'auth/login') !== false) {
    echo " [PASSED - Access denied, redirected to login]\n";
} else {
    echo " [FAILED]\n";
}

clearCookie();

echo "\n========================================================\n";
echo " ALL HTTP E2E AUTH & RBAC TESTS COMPLETED SUCCESSFULLY!\n";
echo "========================================================\n";
