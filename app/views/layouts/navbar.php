<?php
$currentUser = $auth_user ?? [
    'full_name' => 'Tamu',
    'role' => 'guest',
    'email' => ''
];
$roleLabels = [
    'admin' => 'Super Admin',
    'teacher' => 'Guru / Penguji',
    'student' => 'Peserta Didik'
];
$displayRole = $roleLabels[$currentUser['role']] ?? ucfirst($currentUser['role']);
$initials = strtoupper(substr($currentUser['full_name'], 0, 2));
?>
<!-- Top Navbar -->
<header class="app-navbar">
    <div class="d-flex align-items-center gap-3">
        <button type="button" class="btn-sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar">
            <i class="bi bi-list fs-4"></i>
        </button>
        <span class="d-none d-sm-inline text-muted text-sm fw-medium">
            <i class="bi bi-shield-check text-primary me-1"></i> AI Online Exam & Proctoring
        </span>
    </div>

    <div class="d-flex align-items-center gap-3">
        <?php if (!Auth::check()): ?>
            <a href="<?= BASE_URL; ?>/auth/login" class="btn btn-primary btn-sm px-3 d-flex align-items-center gap-2 shadow-sm rounded-pill">
                <i class="bi bi-box-arrow-in-right"></i>
                <span class="fw-semibold">Masuk (Login)</span>
            </a>
        <?php else: ?>
            <!-- Notification Bell Dropdown -->
            <div class="dropdown me-1">
                <a href="#" class="position-relative text-dark text-decoration-none p-2 d-inline-flex align-items-center justify-content-center rounded-circle hover-bg-light" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Pusat Notifikasi" style="width: 38px; height: 38px;">
                    <i class="bi bi-bell fs-5 text-secondary"></i>
                    <span class="position-absolute badge rounded-pill bg-danger text-white border border-light" id="navNotifBadge" style="top: 2px; right: 2px; font-size: 0.65rem; display: none; padding: 0.25em 0.45em;">
                        0
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-end shadow border-0 py-0 rounded-4 overflow-hidden" aria-labelledby="notifDropdown" style="width: 320px; max-width: 90vw;">
                    <div class="p-3 bg-light border-bottom d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-bell-fill text-primary"></i>
                            <span class="fw-bold text-sm text-dark">Notifikasi</span>
                        </div>
                        <a href="<?= BASE_URL; ?>/notification/markAllAsRead" class="text-xs text-primary text-decoration-none fw-medium" id="btnMarkAllReadNav">
                            Tandai Dibaca
                        </a>
                    </div>
                    <div class="list-group list-group-flush" id="navNotifList" style="max-height: 280px; overflow-y: auto;">
                        <div class="p-3 text-center text-muted text-xs">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                            Memuat notifikasi...
                        </div>
                    </div>
                    <div class="p-2 border-top bg-light text-center">
                        <a href="<?= BASE_URL; ?>/notification" class="text-xs fw-semibold text-primary text-decoration-none d-block py-1">
                            Lihat Semua Notifikasi <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- User Profile Dropdown -->
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle gap-2" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 36px; height: 36px; font-size: 0.85rem;">
                        <?= $initials; ?>
                    </div>
                    <div class="d-none d-md-block text-start">
                        <div class="fw-semibold text-sm leading-tight text-truncate" style="max-width: 150px;"><?= htmlspecialchars($currentUser['full_name']); ?></div>
                        <div class="text-xs text-muted"><?= $displayRole; ?></div>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-2" aria-labelledby="userDropdown" style="min-width: 200px;">
                    <li class="px-3 py-2 border-bottom">
                        <div class="fw-semibold text-sm text-dark"><?= htmlspecialchars($currentUser['full_name']); ?></div>
                        <div class="text-xs text-muted"><?= htmlspecialchars($currentUser['email']); ?></div>
                        <div class="mt-1">
                            <span class="badge bg-light text-primary border text-xs"><?= $displayRole; ?></span>
                        </div>
                    </li>
                    <?php if ($currentUser['role'] === 'admin'): ?>
                        <li><a class="dropdown-item text-sm py-2" href="<?= BASE_URL; ?>/admin/dashboard"><i class="bi bi-speedometer2 me-2 text-primary"></i> Dashboard Admin</a></li>
                    <?php elseif ($currentUser['role'] === 'teacher'): ?>
                        <li><a class="dropdown-item text-sm py-2" href="<?= BASE_URL; ?>/teacher/dashboard"><i class="bi bi-speedometer2 me-2 text-primary"></i> Dashboard Guru</a></li>
                    <?php elseif ($currentUser['role'] === 'student'): ?>
                        <li><a class="dropdown-item text-sm py-2" href="<?= BASE_URL; ?>/student/dashboard"><i class="bi bi-speedometer2 me-2 text-primary"></i> Dashboard Siswa</a></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-sm text-danger py-2" href="<?= BASE_URL; ?>/auth/logout">
                            <i class="bi bi-box-arrow-right me-2"></i> Keluar (Logout)
                        </a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</header>
