<?php
/**
 * End-to-End Test Suite for OTP Password Reset via WhatsApp (Fonnte)
 * AI Online Exam & Proctoring System
 */

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/Project2/';
$_SERVER['SCRIPT_NAME'] = '/Project2/index.php';

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Csrf.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/models/UserModel.php';
require_once __DIR__ . '/../app/models/PasswordResetModel.php';
require_once __DIR__ . '/../app/services/FonnteService.php';

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
echo " AI Online Exam - OTP Password Reset (Fonnte WA) Tests\n";
echo "========================================================\n\n";

$db = Database::getInstance();
$userModel = new UserModel();
$resetModel = new PasswordResetModel();
$fonnteService = new FonnteService();

// Test 1: User lookup by phone and username
runTest("1.1 User Lookup by Phone", function() use ($userModel) {
    $u = $userModel->findByPhoneOrIdentifier('081234567890');
    return ($u && $u['username'] === 'admin');
});

runTest("1.2 User Lookup by Username", function() use ($userModel) {
    $u = $userModel->findByPhoneOrIdentifier('admin');
    return ($u && !empty($u['phone']));
});

runTest("1.3 User Lookup by Normalized Phone (628...)", function() use ($userModel) {
    $u = $userModel->findByPhoneOrIdentifier('6281234567890');
    return ($u && $u['username'] === 'admin');
});

// Test 2: Fonnte Service configuration & simulation
runTest("2.1 Fonnte Service Simulation Mode", function() {
    $simService = new FonnteService('');
    $res = $simService->sendOtpPasswordReset('081234567890', 'Test Admin', '123456');
    return ($res['success'] === true && $res['simulated'] === true && isset($res['message']));
});

runTest("2.2 Fonnte Service Error Handling & Fallback", function() {
    $errService = new FonnteService('invalid_token');
    $res = $errService->sendOtpPasswordReset('081234567890', 'Test Admin', '123456');
    return ($res['success'] === false && !empty($res['message']));
});

// Test 3: PasswordResetModel Create & Verify OTP
$testOtp = '852963';
$testToken = bin2hex(random_bytes(32));
$testUserId = 1; // admin
$testPhone = '081234567890';
$insertId = 0;

runTest("3.1 Create OTP in Database", function() use ($resetModel, $testUserId, $testPhone, $testOtp, $testToken, &$insertId) {
    $insertId = $resetModel->createOtp($testUserId, $testPhone, $testOtp, $testToken, 10);
    return ($insertId > 0);
});

runTest("3.2 Verify Valid OTP", function() use ($resetModel, $testPhone, $testOtp) {
    $record = $resetModel->findValidOtp($testPhone, $testOtp);
    return ($record && $record['otp_code'] === $testOtp && (int)$record['is_used'] === 0);
});

runTest("3.3 Reject Invalid OTP", function() use ($resetModel, $testPhone) {
    $record = $resetModel->findValidOtp($testPhone, '000000');
    return ($record === null);
});

runTest("3.4 Find Record by Token", function() use ($resetModel, $testToken) {
    $record = $resetModel->findByToken($testToken);
    return ($record && $record['token'] === $testToken);
});

runTest("3.5 Mark OTP as Used", function() use ($resetModel, $insertId, $testPhone, $testOtp) {
    $resetModel->markAsUsed($insertId);
    $record = $resetModel->findValidOtp($testPhone, $testOtp);
    return ($record === null); // Should not find used OTP
});

// Test 4: Password Update and Login Verification
$newTestPassword = 'password123';
runTest("4.1 Update Password Hash", function() use ($userModel, $testUserId, $newTestPassword) {
    $hash = password_hash($newTestPassword, PASSWORD_BCRYPT);
    $ok = $userModel->updatePassword($testUserId, $hash);
    return ($ok === true);
});

runTest("4.2 Verify Authentication with Updated Password", function() use ($newTestPassword) {
    $auth = Auth::attempt('admin@sekolah.sch.id', $newTestPassword);
    return ($auth['success'] === true && $auth['user']['role'] === 'admin');
});

// Test 5: HTTP End-to-End Route Checks via cURL
runTest("5.1 GET /auth/forgot_password Returns 200", function() {
    $ch = curl_init('http://localhost/Project2/auth/forgot_password');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($code === 200 && str_contains($resp, 'Pemulihan Kata Sandi'));
});

// Cleanup test OTP record
$db->query("DELETE FROM password_resets WHERE otp_code = :otp")->bind(':otp', $testOtp)->execute();

echo "\n========================================================\n";
echo " Total Tests: " . ($passed + $failed) . " | Passed: {$passed} | Failed: {$failed}\n";
echo "========================================================\n";

if ($failed === 0) {
    echo " ALL OTP PASSWORD RESET TESTS PASSED PERFECTLY!\n";
} else {
    exit(1);
}
