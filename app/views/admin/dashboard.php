<?php
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="app-wrapper">
    <!-- Sidebar Navigation -->
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="app-main">
        <!-- Top Navbar -->
        <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

        <!-- Content Area -->
        <main class="app-content">
            <!-- Flash Message -->
            <?php if (!empty($flash_message)): ?>
                <div class="alert alert-<?= htmlspecialchars($flash_message['type']); ?> alert-dismissible fade show text-sm py-2 px-3 mb-4 rounded-3" role="alert">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    <?= htmlspecialchars($flash_message['message']); ?>
                    <button type="button" class="btn-close py-2 px-3" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h1 class="h3 fw-bold text-dark mb-0">Dashboard Super Admin</h1>
                        <span class="badge badge-role-admin">Admin Control Center</span>
                    </div>
                    <p class="text-muted mb-0 text-sm">Pusat kendali master data & konfigurasi sistem <strong><?= APP_NAME; ?></strong>.</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary btn-sm px-3" type="button" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise me-1"></i> Segarkan Data
                    </button>
                    <a href="<?= BASE_URL; ?>/admin/settings" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="bi bi-gear-fill me-1"></i> Konfigurasi Sistem
                    </a>
                    <a href="<?= BASE_URL; ?>/admin/userCreate" class="btn btn-primary btn-sm px-3">
                        <i class="bi bi-person-plus-fill me-1"></i> Tambah Pengguna
                    </a>
                </div>
            </div>

            <!-- 5 Primary Dashboard Metrics (PRD Spec) -->
            <div class="row g-3 mb-4">
                <!-- 1. Total Guru -->
                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="custom-card p-3 h-100 hover-lift">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-xs fw-semibold text-uppercase">Total Guru</span>
                                <h3 class="fw-bold mb-0 mt-1 text-primary"><?= $stats['total_teachers']; ?></h3>
                                <a href="<?= BASE_URL; ?>/admin/teachers" class="text-xs text-primary text-decoration-none mt-1 d-inline-block">
                                    Kelola Data Guru <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                            <div class="stat-icon stat-icon-indigo">
                                <i class="bi bi-person-badge-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Total Peserta -->
                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="custom-card p-3 h-100 hover-lift">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-xs fw-semibold text-uppercase">Total Peserta</span>
                                <h3 class="fw-bold mb-0 mt-1 text-success"><?= $stats['total_students']; ?></h3>
                                <a href="<?= BASE_URL; ?>/admin/students" class="text-xs text-success text-decoration-none mt-1 d-inline-block">
                                    Kelola Peserta Didik <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                            <div class="stat-icon stat-icon-emerald">
                                <i class="bi bi-mortarboard-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Total Kelas -->
                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="custom-card p-3 h-100 hover-lift">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-xs fw-semibold text-uppercase">Total Kelas</span>
                                <h3 class="fw-bold mb-0 mt-1 text-warning"><?= $stats['total_classes']; ?></h3>
                                <a href="<?= BASE_URL; ?>/admin/classes" class="text-xs text-warning text-decoration-none mt-1 d-inline-block">
                                    Kelola Rombel / Kelas <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                            <div class="stat-icon stat-icon-amber">
                                <i class="bi bi-door-open-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Total Mata Pelajaran -->
                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="custom-card p-3 h-100 hover-lift">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-xs fw-semibold text-uppercase">Total Mata Pelajaran</span>
                                <h3 class="fw-bold mb-0 mt-1 text-info"><?= $stats['total_subjects']; ?></h3>
                                <a href="<?= BASE_URL; ?>/admin/subjects" class="text-xs text-info text-decoration-none mt-1 d-inline-block">
                                    Kelola Mata Pelajaran <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                            <div class="stat-icon stat-icon-cyan">
                                <i class="bi bi-journal-bookmark-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. Total Ujian -->
                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="custom-card p-3 h-100 hover-lift">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-xs fw-semibold text-uppercase">Total Ujian</span>
                                <h3 class="fw-bold mb-0 mt-1 text-danger"><?= $stats['total_exams']; ?></h3>
                                <span class="text-xs text-muted">Sesi Ujian Sekolah</span>
                            </div>
                            <div class="stat-icon stat-icon-rose">
                                <i class="bi bi-file-earmark-text-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 6. Total Users -->
                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="custom-card p-3 h-100 hover-lift">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-xs fw-semibold text-uppercase">Semua Pengguna</span>
                                <h3 class="fw-bold mb-0 mt-1 text-dark"><?= $stats['total_users']; ?></h3>
                                <a href="<?= BASE_URL; ?>/admin/users" class="text-xs text-secondary text-decoration-none mt-1 d-inline-block">
                                    Manajemen Users <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                            <div class="stat-icon stat-icon-indigo">
                                <i class="bi bi-people-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Access Modules & System Info -->
            <div class="row g-4">
                <div class="col-12 col-lg-8">
                    <div class="custom-card p-4 h-100">
                        <h2 class="h5 fw-bold text-dark mb-3">Pintasan Manajemen Master Data</h2>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <a href="<?= BASE_URL; ?>/admin/users" class="text-decoration-none">
                                    <div class="p-3 border rounded-3 bg-light hover-lift h-100">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="bi bi-people-fill text-primary fs-5"></i>
                                            <strong class="text-dark">Manajemen Users</strong>
                                        </div>
                                        <p class="text-xs text-muted mb-0">Kelola seluruh akun Admin, Guru, dan Siswa, reset kata sandi, dan status aktif.</p>
                                    </div>
                                </a>
                            </div>

                            <div class="col-12 col-md-6">
                                <a href="<?= BASE_URL; ?>/admin/teachers" class="text-decoration-none">
                                    <div class="p-3 border rounded-3 bg-light hover-lift h-100">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="bi bi-person-badge-fill text-primary fs-5"></i>
                                            <strong class="text-dark">Data Guru</strong>
                                        </div>
                                        <p class="text-xs text-muted mb-0">Kelola data tenaga penguji dengan NIP, spesialisasi keahlian, dan akun login.</p>
                                    </div>
                                </a>
                            </div>

                            <div class="col-12 col-md-6">
                                <a href="<?= BASE_URL; ?>/admin/students" class="text-decoration-none">
                                    <div class="p-3 border rounded-3 bg-light hover-lift h-100">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="bi bi-mortarboard-fill text-success fs-5"></i>
                                            <strong class="text-dark">Data Peserta Didik</strong>
                                        </div>
                                        <p class="text-xs text-muted mb-0">Kelola siswa berdasarkan rombel/kelas, NIS, NISN, gender, dan kredensial ujian.</p>
                                    </div>
                                </a>
                            </div>

                            <div class="col-12 col-md-6">
                                <a href="<?= BASE_URL; ?>/admin/classes" class="text-decoration-none">
                                    <div class="p-3 border rounded-3 bg-light hover-lift h-100">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="bi bi-door-open-fill text-warning fs-5"></i>
                                            <strong class="text-dark">Manajemen Kelas</strong>
                                        </div>
                                        <p class="text-xs text-muted mb-0">Kelola rombongan belajar, tingkatan kelas, dan pembagian tahun ajaran akademik.</p>
                                    </div>
                                </a>
                            </div>

                            <div class="col-12 col-md-6">
                                <a href="<?= BASE_URL; ?>/admin/subjects" class="text-decoration-none">
                                    <div class="p-3 border rounded-3 bg-light hover-lift h-100">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="bi bi-journal-bookmark-fill text-info fs-5"></i>
                                            <strong class="text-dark">Mata Pelajaran</strong>
                                        </div>
                                        <p class="text-xs text-muted mb-0">Kelola daftar kurikulum, kode mata pelajaran unik, dan deskripsi kompetensi.</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="custom-card p-4 h-100">
                        <h2 class="h5 fw-bold text-dark mb-3">Informasi Admin</h2>
                        <ul class="list-unstyled mb-0 d-flex flex-column gap-2 text-sm">
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Nama:</span>
                                <strong><?= htmlspecialchars($user['full_name']); ?></strong>
                            </li>
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Email:</span>
                                <span class="text-truncate" style="max-width: 150px;"><?= htmlspecialchars($user['email']); ?></span>
                            </li>
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Hak Akses:</span>
                                <span class="badge bg-warning text-dark">SUPER ADMIN</span>
                            </li>
                            <li class="d-flex justify-content-between py-1">
                                <span class="text-muted">Proteksi Rute:</span>
                                <span class="text-success fw-semibold"><i class="bi bi-shield-fill-check"></i> Aktif</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <?php require_once __DIR__ . '/../layouts/footer.php'; ?>
    </div>
</div>
