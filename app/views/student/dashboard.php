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
                        <h1 class="h3 fw-bold text-dark mb-0">Dashboard Peserta Ujian</h1>
                        <span class="badge badge-role-peserta">Peserta Role</span>
                    </div>
                    <p class="text-muted mb-0 text-sm">Selamat datang, <strong><?= htmlspecialchars($user['full_name']); ?></strong> (Kelas: <?= htmlspecialchars($student['class_name'] ?? 'Belum ada kelas'); ?>)</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary btn-sm px-3" type="button" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                    </button>
                    <a href="<?= BASE_URL; ?>/auth/logout" class="btn btn-outline-danger btn-sm px-3">
                        <i class="bi bi-box-arrow-right me-1"></i> Logout
                    </a>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="custom-card p-3 h-100 hover-lift">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-xs fw-semibold text-uppercase">Ujian Terjadwal</span>
                                <h3 class="fw-bold mb-0 mt-1 text-primary"><?= $stats['assigned_exams']; ?></h3>
                                <span class="text-xs text-primary"><i class="bi bi-calendar2-week"></i> Total Ditugaskan</span>
                            </div>
                            <div class="stat-icon stat-icon-indigo">
                                <i class="bi bi-journal-text"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="custom-card p-3 h-100 hover-lift">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-xs fw-semibold text-uppercase">Ujian Siap Dikerjakan</span>
                                <h3 class="fw-bold mb-0 mt-1 text-warning"><?= $stats['available_exams']; ?></h3>
                                <span class="text-xs text-warning"><i class="bi bi-play-circle"></i> Sesi Aktif</span>
                            </div>
                            <div class="stat-icon stat-icon-amber">
                                <i class="bi bi-laptop-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="custom-card p-3 h-100 hover-lift">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-xs fw-semibold text-uppercase">Ujian Selesai</span>
                                <h3 class="fw-bold mb-0 mt-1 text-success"><?= $stats['completed_exams']; ?></h3>
                                <span class="text-xs text-success"><i class="bi bi-check2-circle"></i> Riwayat Submit</span>
                            </div>
                            <div class="stat-icon stat-icon-emerald">
                                <i class="bi bi-clipboard-check-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Student Profile & Exam Notice -->
            <div class="row g-4">
                <div class="col-12 col-lg-8">
                    <div class="custom-card p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h5 fw-bold text-dark mb-0">Ujian Siap Dikerjakan</h2>
                            <a href="<?= BASE_URL; ?>/student/exams" class="btn btn-sm btn-outline-primary">
                                Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>

                        <?php if (empty($recent_exams)): ?>
                            <div class="empty-state border-0 my-2">
                                <div class="empty-state-icon text-success bg-success-subtle">
                                    <i class="bi bi-journal-check"></i>
                                </div>
                                <div class="empty-state-title">Tidak Ada Ujian Aktif</div>
                                <div class="empty-state-text">
                                    Semua tugas ujian Anda telah selesai dikerjakan atau belum ada sesi ujian baru yang dijadwalkan oleh guru.
                                </div>
                                <a href="<?= BASE_URL; ?>/student/exams?filter=completed" class="btn btn-outline-secondary btn-sm px-3">
                                    <i class="bi bi-clipboard-check me-1"></i> Lihat Riwayat Ujian
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column gap-3">
                                <?php foreach ($recent_exams as $ex): ?>
                                    <div class="p-3 border rounded-3 bg-white shadow-xs hover-lift d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <span class="badge bg-primary-subtle text-primary fw-bold text-xs"><?= htmlspecialchars($ex['subject_name']); ?></span>
                                                <?php if ($ex['participant_status'] === 'in_progress'): ?>
                                                    <span class="badge bg-warning text-dark text-xs"><i class="bi bi-hourglass-split"></i> Sedang Dikerjakan</span>
                                                <?php else: ?>
                                                    <span class="badge bg-info-subtle text-info text-xs">Belum Dimulai</span>
                                                <?php endif; ?>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($ex['title']); ?></h6>
                                            <div class="text-xs text-muted d-flex flex-wrap gap-3">
                                                <span><i class="bi bi-person me-1"></i> <?= htmlspecialchars($ex['teacher_name']); ?></span>
                                                <span><i class="bi bi-clock me-1"></i> <?= (int)$ex['duration_minutes']; ?> Menit</span>
                                                <span><i class="bi bi-calendar3 me-1"></i> <?= date('d M, H:i', strtotime($ex['start_time'])); ?> - <?= date('H:i', strtotime($ex['end_time'])); ?></span>
                                            </div>
                                        </div>
                                        <div>
                                            <?php if ($ex['participant_status'] === 'in_progress'): ?>
                                                <a href="<?= BASE_URL; ?>/student/examRoom/<?= $ex['exam_id']; ?>" class="btn btn-warning btn-sm px-3 fw-semibold">
                                                    <i class="bi bi-play-fill me-1"></i> Lanjutkan
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= BASE_URL; ?>/student/examDetail/<?= $ex['exam_id']; ?>" class="btn btn-primary btn-sm px-3 fw-semibold">
                                                    <i class="bi bi-arrow-right-circle me-1"></i> Ikuti Ujian
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="custom-card p-4 h-100">
                        <h2 class="h5 fw-bold text-dark mb-3">Data Peserta Didik</h2>
                        <ul class="list-unstyled mb-0 d-flex flex-column gap-2 text-sm">
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Nama:</span>
                                <strong><?= htmlspecialchars($user['full_name']); ?></strong>
                            </li>
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">NIS:</span>
                                <span><?= htmlspecialchars($student['nis'] ?? '-'); ?></span>
                            </li>
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">NISN:</span>
                                <span><?= htmlspecialchars($student['nisn'] ?? '-'); ?></span>
                            </li>
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Kelas:</span>
                                <span><?= htmlspecialchars($student['class_name'] ?? '-'); ?></span>
                            </li>
                            <li class="d-flex justify-content-between py-1">
                                <span class="text-muted">Role:</span>
                                <span class="badge badge-role-peserta">student</span>
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
