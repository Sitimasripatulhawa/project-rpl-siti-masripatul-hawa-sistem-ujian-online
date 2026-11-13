<?php
$role = $auth_user['role'] ?? 'guest';
$userName = $auth_user['full_name'] ?? 'Pengguna';
$currentUri = $_SERVER['REQUEST_URI'] ?? '';

// Helper to determine active menu
function isNavActive($path, $uri) {
    return (strpos($uri, $path) !== false) ? 'active' : '';
}
?>
<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- App Sidebar -->
<aside class="app-sidebar" id="appSidebar">
    <!-- Brand Header -->
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="bi bi-cpu-fill"></i>
        </div>
        <div>
            <div class="lh-1"><?= APP_SHORT_NAME; ?></div>
            <span class="text-xs text-secondary fw-normal">Proctoring AI</span>
        </div>
    </div>

    <!-- Navigation Menu -->
    <div class="sidebar-menu">
        <?php if ($role === 'admin'): ?>
            <!-- ================= ADMIN MENU ================= -->
            <div class="sidebar-heading">Super Admin</div>
            <ul class="nav-sidebar">
                <li class="nav-item">
                    <a href="<?= BASE_URL; ?>/admin/dashboard" class="nav-link <?= isNavActive('/admin/dashboard', $currentUri) ?: (strpos($currentUri, '/admin') === false ? 'active' : ''); ?>">
                        <i class="bi bi-grid-1x2-fill"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL; ?>/admin/users" class="nav-link <?= isNavActive('/admin/users', $currentUri) ?: isNavActive('/admin/user', $currentUri); ?>">
                        <i class="bi bi-people"></i>
                        <span>Users</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL; ?>/admin/teachers" class="nav-link <?= isNavActive('/admin/teachers', $currentUri) ?: isNavActive('/admin/teacher', $currentUri); ?>">
                        <i class="bi bi-person-badge"></i>
                        <span>Guru</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL; ?>/admin/students" class="nav-link <?= isNavActive('/admin/students', $currentUri) ?: isNavActive('/admin/student', $currentUri); ?>">
                        <i class="bi bi-mortarboard"></i>
                        <span>Peserta Didik</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL; ?>/admin/classes" class="nav-link <?= isNavActive('/admin/classes', $currentUri) ?: isNavActive('/admin/class', $currentUri); ?>">
                        <i class="bi bi-door-open"></i>
                        <span>Kelas</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL; ?>/admin/subjects" class="nav-link <?= isNavActive('/admin/subjects', $currentUri) ?: isNavActive('/admin/subject', $currentUri); ?>">
                        <i class="bi bi-journal-bookmark"></i>
                        <span>Mata Pelajaran</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL; ?>/admin/results" class="nav-link <?= isNavActive('/admin/result', $currentUri); ?>">
                        <i class="bi bi-trophy"></i>
                        <span>Hasil Ujian</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL; ?>/admin/settings" class="nav-link <?= (isNavActive('/admin/settings', $currentUri) || isNavActive('/admin/configuration', $currentUri)) ? 'active' : ''; ?>">
                        <i class="bi bi-gear-fill"></i>
                        <span>Konfigurasi Sistem</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL; ?>/admin/mailSettings" class="nav-link <?= isNavActive('/admin/mailSettings', $currentUri); ?>">
                        <i class="bi bi-envelope-gear"></i>
                        <span>Email & SMTP</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL; ?>/notification" class="nav-link <?= isNavActive('/notification', $currentUri); ?>">
                        <i class="bi bi-bell"></i>
                        <span>Notifikasi</span>
                    </a>
                </li>
            </ul>

        <?php elseif ($role === 'teacher'): ?>
            <!-- ================= TEACHER MENU ================= -->
            <div class="sidebar-heading">Panel Guru</div>
            <ul class="nav-sidebar">
                <li class="nav-item">
                    <a href="<?= BASE_URL; ?>/teacher/dashboard" class="nav-link <?= (strpos($currentUri, '/teacher/dashboard') !== false || $currentUri === '/Project2/' || $currentUri === '/Project2/teacher') ? 'active' : ''; ?>">
                        <i class="bi bi-grid-1x2-fill"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL; ?>/teacher/questions" class="nav-link <?= (strpos($currentUri, '/teacher/question') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-collection"></i>
                        <span>Bank Soal</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL; ?>/teacher/exams" class="nav-link <?= (strpos($currentUri, '/teacher/exam') !== false && strpos($currentUri, '/teacher/examResult') === false) ? 'active' : ''; ?>">
                        <i class="bi bi-file-earmark-text"></i>
                        <span>Ujian</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL; ?>/teacher/results" class="nav-link <?= (strpos($currentUri, '/teacher/result') !== false || strpos($currentUri, '/teacher/examResult') !== false || strpos($currentUri, '/teacher/reviewAnswer') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-trophy"></i>
                        <span>Hasil Ujian</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL; ?>/teacher/monitoring" class="nav-link <?= (strpos($currentUri, '/teacher/monitoring') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-broadcast"></i>
                        <span>Live Monitoring</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL; ?>/notification" class="nav-link <?= isNavActive('/notification', $currentUri); ?>">
                        <i class="bi bi-bell"></i>
                        <span>Notifikasi</span>
                    </a>
                </li>
            </ul>

        <?php elseif ($role === 'student'): ?>
            <!-- ================= STUDENT MENU ================= -->
            <div class="sidebar-heading">Panel Peserta</div>
            <ul class="nav-sidebar">
                <li class="nav-item">
                    <a href="<?= BASE_URL; ?>/student/dashboard" class="nav-link <?= (strpos($currentUri, '/student/dashboard') !== false || $currentUri === '/Project2/' || $currentUri === '/Project2/student') ? 'active' : ''; ?>">
                        <i class="bi bi-grid-1x2-fill"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL; ?>/student/exams" class="nav-link <?= (strpos($currentUri, '/student/exam') !== false && strpos($currentUri, 'filter=completed') === false) ? 'active' : ''; ?>">
                        <i class="bi bi-laptop"></i>
                        <span>Ujian Tersedia</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL; ?>/student/exams?filter=completed" class="nav-link <?= (strpos($currentUri, 'filter=completed') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-clipboard-check"></i>
                        <span>Riwayat Hasil</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL; ?>/notification" class="nav-link <?= isNavActive('/notification', $currentUri); ?>">
                        <i class="bi bi-bell"></i>
                        <span>Notifikasi</span>
                    </a>
                </li>
            </ul>
        <?php else: ?>
            <!-- ================= GUEST MENU ================= -->
            <div class="sidebar-heading">Menu Utama</div>
            <ul class="nav-sidebar">
                <li class="nav-item">
                    <a href="<?= BASE_URL; ?>/" class="nav-link <?= ($currentUri === '/' || $currentUri === '/Project2/' || strpos($currentUri, '/home') !== false) ? 'active' : ''; ?>">
                        <i class="bi bi-house-door-fill"></i>
                        <span>Beranda Sistem</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL; ?>/auth/login" class="nav-link">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span>Masuk (Login)</span>
                    </a>
                </li>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="d-flex align-items-center justify-content-between">
            <div class="text-truncate me-2">
                <div class="text-white fw-semibold text-xs text-truncate"><?= htmlspecialchars($userName); ?></div>
                <div class="text-xs text-secondary text-capitalize"><?= $role === 'guest' ? 'Pengunjung Tamu' : $role; ?></div>
            </div>
            <?php if ($role === 'guest'): ?>
                <a href="<?= BASE_URL; ?>/auth/login" class="text-primary" title="Masuk ke Akun">
                    <i class="bi bi-box-arrow-in-right fs-5"></i>
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL; ?>/auth/logout" class="text-danger" title="Keluar dari Akun">
                    <i class="bi bi-box-arrow-right fs-5"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
</aside>
