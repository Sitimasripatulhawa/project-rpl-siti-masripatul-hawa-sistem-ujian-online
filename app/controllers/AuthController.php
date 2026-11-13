<?php
/**
 * Authentication Controller
 * AI Online Exam & Proctoring System
 */

class AuthController extends Controller
{
    /**
     * Default auth index
     */
    public function index()
    {
        $this->login();
    }

    /**
     * Render login page
     */
    public function login()
    {
        // If already logged in, redirect straight to the user's dashboard
        if (Auth::check()) {
            Auth::redirectToDashboard();
        }

        $data = [
            'page_title' => 'Masuk ke Sistem - AI Exam System'
        ];

        $this->view('auth/login', $data);
    }

    /**
     * Process login form submission (POST)
     */
    public function authenticate()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('auth/login');
        }

        // Verify CSRF token
        $this->verifyCsrfToken();

        $identifier = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // 1. Validation
        if (empty($identifier) || empty($password)) {
            Auth::setFlash('danger', 'Silakan masukkan username/email dan kata sandi.');
            $this->redirect('auth/login');
        }

        // 2. Check login brute-force throttling
        if (Auth::isLoginThrottled($identifier)) {
            $remSeconds = Auth::getRemainingLockoutTime($identifier);
            $remMinutes = ceil($remSeconds / 60);
            Auth::setFlash('danger', "Terlalu banyak percobaan masuk yang gagal. Akses ditangguhkan sementara selama {$remMinutes} menit demi keamanan akun Anda.");
            $this->redirect('auth/login');
        }

        // 3. Check database connection
        $db = Database::getInstance();
        if (!$db->isConnected()) {
            Auth::setFlash('danger', 'Gagal terhubung ke database. Pastikan modul MySQL di XAMPP Control Panel sudah dalam status running (START).');
            $this->redirect('auth/login');
        }

        // 4. Query user from database
        /** @var UserModel $userModel */
        $userModel = $this->model('UserModel');
        $user = $userModel->findByUsernameOrEmail($identifier);

        // 5. Verify user existence & password hash
        if (!$user || !Auth::verifyPassword($password, $user['password'], $user['role'] ?? '')) {
            Auth::recordFailedLogin($identifier);
            Auth::setFlash('danger', 'Kredensial tidak valid. Username/email atau kata sandi salah. Untuk akun demo, gunakan kata sandi: password123');
            $this->redirect('auth/login');
        }

        // 6. Check if account is active
        if ((int)$user['is_active'] !== 1) {
            Auth::setFlash('warning', 'Akun Anda dinonaktifkan. Silakan hubungi administrator sekolah.');
            $this->redirect('auth/login');
        }

        // 7. Clear failed login count, store session and redirect to role dashboard
        Auth::clearFailedLogins($identifier);
        Auth::login($user);
        Auth::setFlash('success', 'Selamat datang kembali, ' . htmlspecialchars($user['full_name']) . '!');
        Auth::redirectToDashboard();
    }

    /**
     * Process logout
     */
    public function logout()
    {
        Auth::logout();
        Auth::setFlash('success', 'Anda telah berhasil keluar dari sistem.');
        $this->redirect('auth/login');
    }

    // =========================================================================
    // FORGOT PASSWORD VIA WHATSAPP (FONNTE GATEWAY)
    // =========================================================================

    /**
     * Render forgot password request form
     */
    public function forgot_password()
    {
        if (Auth::check()) {
            Auth::redirectToDashboard();
        }

        $data = [
            'page_title' => 'Lupa Kata Sandi (OTP WhatsApp) - ' . APP_NAME
        ];
        $this->view('auth/forgot_password', $data);
    }

    /**
     * Process request to send OTP via WhatsApp (POST)
     */
    public function send_otp()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('auth/forgot_password');
        }

        $this->verifyCsrfToken();

        $identifier = trim($_POST['identifier'] ?? '');
        if (empty($identifier)) {
            Auth::setFlash('danger', 'Silakan masukkan Nomor WhatsApp, Username, atau Email akun Anda.');
            $this->redirect('auth/forgot_password');
        }

        /** @var UserModel $userModel */
        $userModel = $this->model('UserModel');
        $user = $userModel->findByPhoneOrIdentifier($identifier);

        if (!$user) {
            Auth::setFlash('danger', 'Akun dengan identitas tersebut tidak ditemukan dalam sistem.');
            $this->redirect('auth/forgot_password');
        }

        if ((int)$user['is_active'] !== 1) {
            Auth::setFlash('warning', 'Akun Anda dinonaktifkan. Silakan hubungi administrator sekolah.');
            $this->redirect('auth/forgot_password');
        }

        if (empty($user['phone'])) {
            Auth::setFlash('warning', 'Akun Anda belum memiliki nomor WhatsApp terdaftar. Silakan hubungi pihak sekolah/administrator.');
            $this->redirect('auth/forgot_password');
        }

        // Generate 6-digit numeric OTP and secure session token
        $otpCode = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $token = bin2hex(random_bytes(32));

        // Store OTP in database (valid 10 minutes)
        require_once __DIR__ . '/../models/PasswordResetModel.php';
        $resetModel = new PasswordResetModel();
        $resetModel->createOtp((int)$user['id'], $user['phone'], $otpCode, $token, 10);

        // Send OTP via Fonnte Service
        require_once __DIR__ . '/../services/FonnteService.php';
        $fonnte = new FonnteService();
        $sendResult = $fonnte->sendOtpPasswordReset($user['phone'], $user['full_name'], $otpCode);

        // Store reset state in session for verification step
        $_SESSION['reset_user_id'] = (int)$user['id'];
        $_SESSION['reset_phone'] = $user['phone'];
        $_SESSION['reset_masked_phone'] = substr($user['phone'], 0, 4) . '****' . substr($user['phone'], -3);
        $_SESSION['reset_user_name'] = $user['full_name'];

        if ($sendResult['simulated'] ?? false) {
            Auth::setFlash('info', 'Kode OTP berhasil dibuat! (Mode Demo: Kode OTP Anda adalah <strong>' . $otpCode . '</strong>)');
        } else {
            Auth::setFlash('success', 'Kode OTP telah dikirim ke nomor WhatsApp Anda (' . $_SESSION['reset_masked_phone'] . '). Silakan periksa pesan Anda.');
        }

        $this->redirect('auth/verify_otp');
    }

    /**
     * Render verify OTP form
     */
    public function verify_otp()
    {
        if (empty($_SESSION['reset_user_id']) || empty($_SESSION['reset_phone'])) {
            $this->redirect('auth/forgot_password');
        }

        $data = [
            'page_title' => 'Verifikasi Kode OTP WhatsApp - ' . APP_NAME,
            'masked_phone' => $_SESSION['reset_masked_phone'] ?? '',
            'simulated_otp' => $_SESSION['dev_simulated_otp'] ?? null
        ];
        $this->view('auth/verify_otp', $data);
    }

    /**
     * Process OTP verification (POST)
     */
    public function process_verify_otp()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('auth/verify_otp');
        }

        $this->verifyCsrfToken();

        $phone = $_SESSION['reset_phone'] ?? '';
        $otpInput = trim($_POST['otp'] ?? '');

        if (empty($phone) || empty($otpInput)) {
            Auth::setFlash('danger', 'Silakan masukkan 6 digit kode OTP yang telah dikirim ke WhatsApp Anda.');
            $this->redirect('auth/verify_otp');
        }

        require_once __DIR__ . '/../models/PasswordResetModel.php';
        $resetModel = new PasswordResetModel();
        $record = $resetModel->findValidOtp($phone, $otpInput);

        if (!$record) {
            Auth::setFlash('danger', 'Kode OTP tidak valid atau sudah kedaluwarsa. Silakan periksa kembali atau kirim ulang.');
            $this->redirect('auth/verify_otp');
        }

        // OTP verified successfully! Save verified token in session
        $_SESSION['otp_verified_token'] = $record['token'];
        $_SESSION['otp_record_id'] = $record['id'];
        unset($_SESSION['dev_simulated_otp']);

        Auth::setFlash('success', 'Kode OTP berhasil diverifikasi! Silakan tentukan kata sandi baru Anda.');
        $this->redirect('auth/reset_password');
    }

    /**
     * Resend OTP via WhatsApp
     */
    public function resend_otp()
    {
        $userId = $_SESSION['reset_user_id'] ?? null;
        $phone = $_SESSION['reset_phone'] ?? null;
        $name = $_SESSION['reset_user_name'] ?? 'Pengguna';

        if (!$userId || !$phone) {
            $this->redirect('auth/forgot_password');
        }

        // Generate new 6-digit OTP
        $otpCode = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $token = bin2hex(random_bytes(32));

        require_once __DIR__ . '/../models/PasswordResetModel.php';
        $resetModel = new PasswordResetModel();
        $resetModel->createOtp((int)$userId, $phone, $otpCode, $token, 10);

        require_once __DIR__ . '/../services/FonnteService.php';
        $fonnte = new FonnteService();
        $sendResult = $fonnte->sendOtpPasswordReset($phone, $name, $otpCode);

        if ($sendResult['simulated'] ?? false) {
            Auth::setFlash('info', 'Kode OTP baru telah digenerate! (Mode Demo: Kode baru adalah <strong>' . $otpCode . '</strong>)');
        } else {
            Auth::setFlash('success', 'Kode OTP baru telah berhasil dikirim ulang ke WhatsApp Anda.');
        }

        $this->redirect('auth/verify_otp');
    }

    /**
     * Render set new password form
     */
    public function reset_password()
    {
        $verifiedToken = $_SESSION['otp_verified_token'] ?? null;
        if (!$verifiedToken) {
            Auth::setFlash('danger', 'Sesi verifikasi tidak ditemukan atau telah kedaluwarsa.');
            $this->redirect('auth/forgot_password');
        }

        require_once __DIR__ . '/../models/PasswordResetModel.php';
        $resetModel = new PasswordResetModel();
        $record = $resetModel->findByToken($verifiedToken);

        if (!$record) {
            Auth::setFlash('danger', 'Token reset tidak valid atau sudah digunakan.');
            $this->redirect('auth/forgot_password');
        }

        $data = [
            'page_title' => 'Buat Kata Sandi Baru - ' . APP_NAME,
            'user' => $record
        ];
        $this->view('auth/reset_password', $data);
    }

    /**
     * Process updating new password (POST)
     */
    public function update_password()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('auth/reset_password');
        }

        $this->verifyCsrfToken();

        $verifiedToken = $_SESSION['otp_verified_token'] ?? null;
        if (!$verifiedToken) {
            $this->redirect('auth/forgot_password');
        }

        require_once __DIR__ . '/../models/PasswordResetModel.php';
        $resetModel = new PasswordResetModel();
        $record = $resetModel->findByToken($verifiedToken);

        if (!$record) {
            Auth::setFlash('danger', 'Token verifikasi tidak valid atau telah kedaluwarsa.');
            $this->redirect('auth/forgot_password');
        }

        $newPassword = trim($_POST['password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        if (strlen($newPassword) < 6) {
            Auth::setFlash('danger', 'Kata sandi minimal harus terdiri dari 6 karakter.');
            $this->redirect('auth/reset_password');
        }

        if ($newPassword !== $confirmPassword) {
            Auth::setFlash('danger', 'Konfirmasi kata sandi tidak cocok. Silakan ulangi.');
            $this->redirect('auth/reset_password');
        }

        // Hash new password with PASSWORD_BCRYPT
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);

        /** @var UserModel $userModel */
        $userModel = $this->model('UserModel');
        $userModel->updatePassword((int)$record['user_id'], $hash);

        // Mark OTP record as used
        $resetModel->markAsUsed((int)$record['id']);

        // Clean up reset session
        unset(
            $_SESSION['reset_user_id'],
            $_SESSION['reset_phone'],
            $_SESSION['reset_masked_phone'],
            $_SESSION['reset_user_name'],
            $_SESSION['otp_verified_token'],
            $_SESSION['otp_record_id'],
            $_SESSION['dev_simulated_otp']
        );

        Auth::setFlash('success', 'Kata sandi Anda berhasil diperbarui! Silakan masuk menggunakan kata sandi baru Anda.');
        $this->redirect('auth/login');
    }
}
