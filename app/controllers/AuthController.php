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
}
