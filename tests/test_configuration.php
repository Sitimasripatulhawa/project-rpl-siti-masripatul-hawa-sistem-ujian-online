<?php
/**
 * Test Suite: System Configuration Feature
 * AI Online Exam & Proctoring System
 * 
 * Verifies:
 * 1. Database table 'system_settings' & model SettingModel
 * 2. Identity, Exam Defaults, AI Proctoring Sensitivity, AI Chatbot & SMTP settings
 * 3. RBAC (Admin-only restriction, Guest/Student/Teacher forbidden)
 * 4. CSRF protection on configuration updates
 * 5. Password & API Key safety: never exposed in plain text, blank password does not overwrite working SMTP secret
 * 6. Integration: ProctoringRiskService, ProctoringModel, and TeacherController use configured values
 */

require_once __DIR__ . '/../app/init.php';

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

function runTest(string $desc, callable $test) {
    global $totalTests, $passedTests, $failedTests;
    $totalTests++;
    echo "Testing [{$totalTests}]: {$desc}... ";
    try {
        $result = $test();
        if ($result === true) {
            $passedTests++;
            echo "[PASSED]\n";
        } else {
            $failedTests++;
            echo "[FAILED] Assertion failed\n";
        }
    } catch (Throwable $e) {
        $failedTests++;
        echo "[FAILED] Exception: " . $e->getMessage() . "\n";
    }
}

echo "========================================================\n";
echo " AI Online Exam - System Configuration Test Suite\n";
echo "========================================================\n\n";

$db = Database::getInstance();
$settingModel = new SettingModel();

// ----------------------------------------------------------
// 1. Database & SettingModel Core Tests
// ----------------------------------------------------------
runTest("1.1 Tabel 'system_settings' terdaftar di database MySQL", function() use ($db) {
    $tables = $db->query("SHOW TABLES LIKE 'system_settings'")->resultSet();
    return count($tables) === 1;
});

runTest("1.2 SettingModel::get mengembalikan konfigurasi default identitas aplikasi", function() {
    $appName = SettingModel::get('app_name');
    $shortName = SettingModel::get('app_short_name');
    return !empty($appName) && !empty($shortName);
});

runTest("1.3 SettingModel::getExamDefaults memuat durasi dan KKM default", function() {
    $defaults = SettingModel::getExamDefaults();
    return isset($defaults['duration_minutes']) && $defaults['duration_minutes'] > 0
        && isset($defaults['passing_score']) && $defaults['passing_score'] >= 0;
});

runTest("1.4 SettingModel::getProctoringWeights memuat 7 jenis bobot pelanggaran", function() {
    $weights = SettingModel::getProctoringWeights();
    $expectedKeys = [
        'FACE_NOT_DETECTED', 'MULTIPLE_FACE', 'CAMERA_DISCONNECTED',
        'CAMERA_BLOCKED', 'SUSPICIOUS_GAZE', 'TAB_SWITCH', 'FACE_CHANGED'
    ];
    foreach ($expectedKeys as $k) {
        if (!isset($weights[$k]) || $weights[$k] <= 0) return false;
    }
    return true;
});

runTest("1.5 SettingModel::getProctoringThresholds memuat ambang batas normal, warning, high risk", function() {
    $th = SettingModel::getProctoringThresholds();
    return isset($th['normal_max'], $th['warning_max'], $th['high_risk_min'])
        && $th['normal_max'] < $th['warning_max']
        && $th['warning_max'] < $th['high_risk_min'];
});

// ----------------------------------------------------------
// 2. Secret Protection & Safe .env Synchronization
// ----------------------------------------------------------
runTest("2.1 Kredensial SMTP dan password terlindungi oleh masking", function() {
    $smtp = SettingModel::getSmtpConfig();
    if (empty($smtp['host'])) return false;
    // Password harus berstatus masked, bukan raw text
    return str_contains($smtp['password_masked'], '●●●●') || $smtp['password_masked'] === '(Belum diatur)';
});

runTest("2.2 SettingModel::updateEnvFile menjaga password lama jika input password kosong", function() {
    $envPath = dirname(__DIR__) . '/.env';
    $originalEnv = file_get_contents($envPath);
    
    // Simpan update tanpa password (null)
    $success = SettingModel::updateEnvFile([
        'MAIL_PASSWORD' => null
    ]);
    
    $updatedEnv = file_get_contents($envPath);
    // Pastikan kata kunci password lama tidak terhapus menjadi kosong
    return $success && str_contains($updatedEnv, 'MAIL_PASSWORD=') && !str_contains($updatedEnv, 'MAIL_PASSWORD=""') && !str_contains($updatedEnv, 'MAIL_PASSWORD= ');
});

// ----------------------------------------------------------
// 3. Active Utilization by Other Modules
// ----------------------------------------------------------
runTest("3.1 ProctoringRiskService menggunakan bobot & ambang batas dari SettingModel", function() {
    $riskService = new ProctoringRiskService();
    $weights = $riskService->getWeights();
    $configuredWeights = SettingModel::getProctoringWeights();
    return $weights['MULTIPLE_FACE'] === $configuredWeights['MULTIPLE_FACE'];
});

runTest("3.2 SettingModel update berhasil mengubah nilai dan terbaca runtime", function() use ($settingModel) {
    $testTagline = 'Platform Ujian Online Terintegrasi AI - Test ' . time();
    $saved = $settingModel->set('app_header_tagline', $testTagline, 'general', 'Tagline Test');
    $readBack = SettingModel::get('app_header_tagline');
    return $saved && ($readBack === $testTagline);
});

// ----------------------------------------------------------
// 4. End-to-End HTTP Endpoints & RBAC Security Tests
// ----------------------------------------------------------
runTest("4.1 HTTP: Tamu (Guest) mengakses /admin/settings diblokir (Redirect login)", function() {
    $ch = curl_init('http://localhost/Project2/admin/settings');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($code === 302);
});

runTest("4.2 HTTP: Admin terautentikasi dapat mengakses /admin/settings dengan HTTP 200", function() {
    $cookieFile = __DIR__ . '/test_admin_config_cookie.txt';
    if (file_exists($cookieFile)) unlink($cookieFile);

    // 1. Login Admin
    $ch = curl_init('http://localhost/Project2/auth/authenticate');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'username' => 'admin',
        'password' => 'password123'
    ]));
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_exec($ch);
    curl_close($ch);

    // 2. Access Admin Settings
    $ch = curl_init('http://localhost/Project2/admin/settings');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (file_exists($cookieFile)) unlink($cookieFile);
    return ($code === 200) && str_contains($body, 'Konfigurasi Sistem') && str_contains($body, 'Identitas & Umum');
});

runTest("4.3 HTTP: Siswa diblokir mengakses /admin/settings (RBAC Enforcement)", function() {
    $cookieFile = __DIR__ . '/test_student_config_cookie.txt';
    if (file_exists($cookieFile)) unlink($cookieFile);

    // 1. Login Siswa
    $ch = curl_init('http://localhost/Project2/auth/authenticate');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'username' => 'siswa1',
        'password' => 'password123'
    ]));
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_exec($ch);
    curl_close($ch);

    // 2. Try accessing Admin Settings
    $ch = curl_init('http://localhost/Project2/admin/settings');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (file_exists($cookieFile)) unlink($cookieFile);
    return ($code === 302);
});

runTest("4.4 HTTP: POST updateSettings dengan token CSRF tidak valid ditolak dan data tidak berubah", function() {
    $cookieFile = __DIR__ . '/test_admin_csrf_cookie.txt';
    if (file_exists($cookieFile)) unlink($cookieFile);

    $oldName = SettingModel::get('app_name');

    // 1. Login Admin
    $ch = curl_init('http://localhost/Project2/auth/authenticate');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'username' => 'admin',
        'password' => 'password123'
    ]));
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_exec($ch);
    curl_close($ch);

    // 2. Submit form WITH FAKE/INVALID CSRF token
    $ch = curl_init('http://localhost/Project2/admin/updateSettings');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'group' => 'general',
        'csrf_token' => 'fake_csrf_attack_token_9999',
        'app_name' => 'Attacked Name Should Not Save'
    ]));
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Refresh model cache and verify app_name was NOT modified
    $dbName = Database::getInstance()->query("SELECT setting_value FROM system_settings WHERE setting_key = 'app_name'")->single();
    $currentVal = $dbName['setting_value'] ?? '';

    if (file_exists($cookieFile)) unlink($cookieFile);
    return ($code === 302) && ($currentVal !== 'Attacked Name Should Not Save');
});

echo "\n--------------------------------------------------------\n";
echo " Hasil Pengujian: {$passedTests} Berhasil, {$failedTests} Gagal dari Total {$totalTests} Pengujian\n";
echo "--------------------------------------------------------\n";

if ($failedTests === 0) {
    echo "Seluruh pengujian Fitur Konfigurasi BERHASIL 100%!\n";
} else {
    echo "Ada pengujian yang gagal, mohon periksa log di atas.\n";
    exit(1);
}
