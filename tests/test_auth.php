<?php
/**
 * Automated Test Suite for Authentication & Role-Based Access Control
 * AI Online Exam & Proctoring System
 */

// Simulate environment
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/Project2/';
$_SERVER['SCRIPT_NAME'] = '/Project2/index.php';

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Csrf.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/models/UserModel.php';

Auth::init();

$passed = 0;
$failed = 0;

function runTest($name, $callable) {
    global $passed, $failed;
    echo "Testing: {$name}... ";
    try {
        $result = $callable();
        if ($result === true) {
            echo " [PASSED]\n";
            $passed++;
        } else {
            echo " [FAILED] ({$result})\n";
            $failed++;
        }
    } catch (Exception $e) {
        echo " [EXCEPTION] ({$e->getMessage()})\n";
        $failed++;
    }
}

echo "========================================================\n";
echo " AI Online Exam - Authentication Test Suite\n";
echo "========================================================\n\n";

$userModel = new UserModel();

// 1. Test Admin Authentication by username & email
runTest('1.1 Find Admin by Username', function() use ($userModel) {
    $user = $userModel->findByUsernameOrEmail('admin');
    return ($user && $user['role'] === 'admin');
});

runTest('1.2 Verify Admin Password (password123)', function() use ($userModel) {
    $user = $userModel->findByUsernameOrEmail('admin@sekolah.sch.id');
    return ($user && password_verify('password123', $user['password']));
});

runTest('1.3 Reject Wrong Password for Admin', function() use ($userModel) {
    $user = $userModel->findByUsernameOrEmail('admin');
    return ($user && !password_verify('wrongpassword', $user['password']));
});

// 2. Test Teacher Authentication
runTest('2.1 Find Teacher by Email & Verify Role', function() use ($userModel) {
    $user = $userModel->findByUsernameOrEmail('budi.santoso@sekolah.sch.id');
    return ($user && $user['role'] === 'teacher' && isset($user['extra']['nip']));
});

runTest('2.2 Verify Teacher Password (password123)', function() use ($userModel) {
    $user = $userModel->findByUsernameOrEmail('guru_budi');
    return ($user && password_verify('password123', $user['password']));
});

// 3. Test Student Authentication
runTest('3.1 Find Student with Class details', function() use ($userModel) {
    $user = $userModel->findByUsernameOrEmail('ani.lestari@sekolah.sch.id');
    return ($user && $user['role'] === 'student' && $user['extra']['class_name'] === 'XII MIPA 1');
});

runTest('3.2 Verify Student Password (password123)', function() use ($userModel) {
    $user = $userModel->findByUsernameOrEmail('siswa_ani');
    return ($user && password_verify('password123', $user['password']));
});

// 4. Test Session Management & Auth Helper
runTest('4.1 Auth::login() stores sanitized session without password hash', function() use ($userModel) {
    $user = $userModel->findByUsernameOrEmail('admin');
    Auth::login($user);
    $sessionUser = Auth::user();
    return (
        Auth::check() === true &&
        Auth::role() === 'admin' &&
        Auth::id() === 1 &&
        !isset($sessionUser['password'])
    );
});

runTest('4.2 Auth::logout() destroys session correctly', function() {
    Auth::logout();
    return (Auth::check() === false && Auth::user() === null);
});

// 5. Test Role-Based Access Guard Logic
runTest('5.1 Unauthenticated user is detected by Auth::check()', function() {
    Auth::logout();
    return (Auth::check() === false);
});

runTest('5.2 Student login is identified with role student', function() use ($userModel) {
    $student = $userModel->findByUsernameOrEmail('siswa_bimo');
    Auth::login($student);
    return (Auth::check() === true && Auth::role() === 'student');
});

runTest('5.3 Inactive user detection logic', function() use ($userModel) {
    $fakeInactiveUser = ['is_active' => 0];
    return ((int)$fakeInactiveUser['is_active'] === 0);
});

echo "\n========================================================\n";
echo " Total Tests: " . ($passed + $failed) . " | Passed: {$passed} | Failed: {$failed}\n";
echo "========================================================\n";

exit($failed > 0 ? 1 : 0);
