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
                    <h1 class="h3 fw-bold text-dark mb-1">Daftar Ujian Peserta</h1>
                    <p class="text-muted mb-0 text-sm">Pilih dan ikuti ujian online yang ditugaskan untuk kelas <strong><?= htmlspecialchars($student['class_name'] ?? 'Kelas Anda'); ?></strong>.</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary btn-sm px-3" type="button" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                    </button>
                </div>
            </div>

            <!-- Filter Navigation Tabs -->
            <div class="mb-4">
                <ul class="nav nav-pills custom-pills">
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_filter === 'all') ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/student/exams?filter=all">
                            <i class="bi bi-collection me-1"></i> Semua Ujian
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_filter === 'available') ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/student/exams?filter=available">
                            <i class="bi bi-play-circle me-1"></i> Tersedia & Aktif
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_filter === 'completed') ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/student/exams?filter=completed">
                            <i class="bi bi-check2-circle me-1"></i> Selesai
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Exams Cards Grid -->
            <?php if (empty($exams)): ?>
                <div class="empty-state my-4">
                    <div class="empty-state-icon">
                        <i class="bi bi-journal-x"></i>
                    </div>
                    <div class="empty-state-title">Tidak Ada Ujian Ditemukan</div>
                    <div class="empty-state-text">
                        Tidak ada sesi ujian yang sedang aktif atau sesuai dengan filter pilihan Anda saat ini.
                    </div>
                    <a href="<?= BASE_URL; ?>/student/dashboard" class="btn btn-outline-primary btn-sm px-3">
                        <i class="bi bi-grid-1x2-fill me-1"></i> Kembali ke Dashboard
                    </a>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($exams as $e): 
                        $isSubmitted = ($e['participant_status'] === 'submitted');
                        $isInProgress = ($e['participant_status'] === 'in_progress');
                        $now = time();
                        $startTime = strtotime($e['start_time']);
                        $endTime = strtotime($e['end_time']);
                        $isUpcoming = ($now < $startTime);
                        $isExpired = ($now > $endTime);
                    ?>
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="custom-card p-4 h-100 d-flex flex-column justify-content-between hover-lift">
                                <div>
                                    <!-- Header Badge -->
                                    <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                                        <span class="badge bg-primary-subtle text-primary fw-semibold text-xs">
                                            <?= htmlspecialchars($e['subject_name']); ?>
                                        </span>
                                        <?php if ($isSubmitted): ?>
                                            <span class="badge bg-success-subtle text-success text-xs"><i class="bi bi-check-circle-fill me-1"></i> Selesai</span>
                                        <?php elseif ($isInProgress): ?>
                                            <span class="badge bg-warning text-dark text-xs"><i class="bi bi-hourglass-split me-1"></i> Sedang Dikerjakan</span>
                                        <?php elseif ($isExpired): ?>
                                            <span class="badge bg-secondary text-white text-xs">Waktu Habis</span>
                                        <?php elseif ($isUpcoming): ?>
                                            <span class="badge bg-info-subtle text-info text-xs">Belum Dimulai</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary text-white text-xs">Siap Dikerjakan</span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Title -->
                                    <h5 class="fw-bold text-dark mb-2"><?= htmlspecialchars($e['title']); ?></h5>
                                    
                                    <?php if (!empty($e['description'])): ?>
                                        <p class="text-muted text-xs mb-3 text-truncate-2" style="min-height: 2.2rem;">
                                            <?= htmlspecialchars($e['description']); ?>
                                        </p>
                                    <?php else: ?>
                                        <p class="text-muted text-xs mb-3 fst-italic" style="min-height: 2.2rem;">Tidak ada deskripsi tambahan.</p>
                                    <?php endif; ?>

                                    <!-- Meta List -->
                                    <div class="p-3 bg-light rounded-3 mb-3 text-xs d-flex flex-column gap-1">
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted"><i class="bi bi-person me-1"></i> Guru Penguji:</span>
                                            <span class="fw-semibold text-dark"><?= htmlspecialchars($e['teacher_name']); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted"><i class="bi bi-clock me-1"></i> Durasi:</span>
                                            <span class="fw-semibold text-dark"><?= (int)$e['duration_minutes']; ?> Menit</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted"><i class="bi bi-question-circle me-1"></i> Jumlah Soal:</span>
                                            <span class="fw-semibold text-dark"><?= (int)$e['total_questions']; ?> Butir</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted"><i class="bi bi-calendar3 me-1"></i> Pelaksanaan:</span>
                                            <span class="fw-semibold text-dark"><?= date('d/m/y H:i', $startTime); ?> - <?= date('H:i', $endTime); ?></span>
                                        </div>
                                        <?php if (!empty($e['token'])): ?>
                                            <div class="d-flex justify-content-between">
                                                <span class="text-muted"><i class="bi bi-key me-1"></i> Token:</span>
                                                <span class="badge bg-secondary-subtle text-dark">Memerlukan Token</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="pt-2 border-top">
                                    <?php if ($isSubmitted): ?>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="text-xs text-muted">Nilai Akhir:</span>
                                                <?php if ((int)($e['show_result'] ?? 1) === 1): ?>
                                                    <div class="h5 fw-bold mb-0 text-success"><?= number_format((float)$e['final_score'], 1); ?></div>
                                                <?php else: ?>
                                                    <div class="text-xs fw-semibold text-warning mt-1"><i class="bi bi-clock-history"></i> Belum Diumumkan</div>
                                                <?php endif; ?>
                                            </div>
                                            <a href="<?= BASE_URL; ?>/student/examResult/<?= $e['exam_id']; ?>" class="btn btn-outline-primary btn-sm px-3">
                                                <i class="bi bi-eye me-1"></i> <?= ((int)($e['show_result'] ?? 1) === 1) ? 'Lihat Hasil' : 'Status Ujian'; ?>
                                            </a>
                                        </div>
                                    <?php elseif ($isInProgress): ?>
                                        <a href="<?= BASE_URL; ?>/student/examRoom/<?= $e['exam_id']; ?>" class="btn btn-warning btn-sm w-100 fw-bold">
                                            <i class="bi bi-play-fill me-1"></i> Lanjutkan Ujian
                                        </a>
                                    <?php elseif ($isExpired): ?>
                                        <button class="btn btn-secondary btn-sm w-100" disabled>
                                            <i class="bi bi-x-circle me-1"></i> Waktu Pelaksanaan Habis
                                        </button>
                                    <?php else: ?>
                                        <a href="<?= BASE_URL; ?>/student/examDetail/<?= $e['exam_id']; ?>" class="btn btn-primary btn-sm w-100 fw-semibold">
                                            <i class="bi bi-info-circle me-1"></i> Detail & Kerjakan
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>

        <!-- Footer -->
        <?php require_once __DIR__ . '/../layouts/footer.php'; ?>
    </div>
</div>
