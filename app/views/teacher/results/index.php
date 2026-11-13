<?php
require_once __DIR__ . '/../../layouts/header.php';
?>

<div class="app-wrapper">
    <!-- Sidebar Navigation -->
    <?php require_once __DIR__ . '/../../layouts/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="app-main">
        <!-- Top Navbar -->
        <?php require_once __DIR__ . '/../../layouts/navbar.php'; ?>

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
                    <h1 class="h3 fw-bold text-dark mb-1">Hasil Ujian & Analitik Peserta</h1>
                    <p class="text-muted mb-0 text-sm">Pantau perolehan nilai, periksa lembar jawaban peserta, dan lakukan penilaian essay manual.</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary btn-sm px-3" type="button" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                    </button>
                </div>
            </div>

            <!-- Search & Filters -->
            <div class="custom-card p-3 mb-4">
                <form action="<?= BASE_URL; ?>/teacher/results" method="GET" class="row g-2 align-items-center">
                    <div class="col-12 col-md-6 col-lg-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" name="search" placeholder="Cari judul ujian, mata pelajaran, atau kelas..." value="<?= htmlspecialchars($search ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm px-3">Cari</button>
                        <?php if (!empty($search)): ?>
                            <a href="<?= BASE_URL; ?>/teacher/results" class="btn btn-outline-secondary btn-sm px-3">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Exam Results Table / List -->
            <?php if (empty($exams)): ?>
                <div class="custom-card p-5 text-center text-muted">
                    <i class="bi bi-trophy text-secondary fs-1 d-block mb-2"></i>
                    <h5 class="fw-bold text-dark mb-1">Belum Ada Data Hasil Ujian</h5>
                    <p class="text-sm text-muted mb-0">Belum ada sesi ujian yang dibuat atau selesai dikerjakan peserta.</p>
                </div>
            <?php else: ?>
                <div class="custom-card overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-sm">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3 py-3" style="min-width: 220px;">Sesi Ujian</th>
                                    <th>Mata Pelajaran & Kelas</th>
                                    <th class="text-center">Peserta Selesai</th>
                                    <th class="text-center">Nilai Rata-rata</th>
                                    <th class="text-center">Tertinggi / Terendah</th>
                                    <th class="text-center">Status Publikasi</th>
                                    <th class="text-end pe-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($exams)): ?>
                                    <tr>
                                        <td colspan="7" class="p-0">
                                            <div class="empty-state border-0 my-3">
                                                <div class="empty-state-icon">
                                                    <i class="bi bi-trophy"></i>
                                                </div>
                                                <div class="empty-state-title">Belum Ada Hasil Ujian</div>
                                                <div class="empty-state-text">
                                                    Belum ada rekapitulasi nilai ujian yang selesai atau sesuai dengan filter yang Anda cari.
                                                </div>
                                                <a href="<?= BASE_URL; ?>/teacher/exams" class="btn btn-primary btn-sm px-3">
                                                    <i class="bi bi-journal-text me-1"></i> Buka Manajemen Ujian
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($exams as $ex): ?>
                                    <tr>
                                        <td class="ps-3 py-3">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($ex['title']); ?></div>
                                            <span class="badge bg-light text-secondary border text-xs"><?= (int)$ex['duration_minutes']; ?> Menit</span>
                                            <?php if ((int)$ex['pending_essay_participants'] > 0): ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis text-xs ms-1">
                                                    <i class="bi bi-pencil-fill me-1"></i> <?= (int)$ex['pending_essay_participants']; ?> Essay Pending
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($ex['subject_name']); ?></div>
                                            <span class="text-xs text-muted"><?= htmlspecialchars($ex['class_name']); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-bold text-dark"><?= $ex['total_submitted']; ?></span>
                                            <span class="text-muted text-xs">/ <?= $ex['total_assigned']; ?></span>
                                            <div class="text-xs text-muted">Kelulusan: <?= $ex['passing_rate']; ?>%</div>
                                        </td>
                                        <td class="text-center">
                                            <div class="h6 fw-bold mb-0 text-primary"><?= number_format($ex['avg_score'], 1); ?></div>
                                            <span class="text-xs text-muted">KKM: <?= number_format((float)$ex['passing_score'], 1); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <div class="text-xs">
                                                <span class="text-success fw-bold"><i class="bi bi-arrow-up"></i> <?= number_format($ex['highest_score'], 1); ?></span>
                                                <span class="text-muted mx-1">|</span>
                                                <span class="text-danger fw-bold"><i class="bi bi-arrow-down"></i> <?= number_format($ex['lowest_score'], 1); ?></span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($ex['show_result'] === 1): ?>
                                                <span class="badge bg-success-subtle text-success text-xs"><i class="bi bi-eye-fill me-1"></i> Dipublikasikan</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-muted text-xs"><i class="bi bi-eye-slash-fill me-1"></i> Disembunyikan</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-3">
                                            <a href="<?= BASE_URL; ?>/teacher/examResultDetail/<?= $ex['exam_id']; ?>" class="btn btn-primary btn-sm px-3">
                                                <i class="bi bi-clipboard-data-fill me-1"></i> Detail Hasil
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </main>

        <!-- Footer -->
        <?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
    </div>
</div>
