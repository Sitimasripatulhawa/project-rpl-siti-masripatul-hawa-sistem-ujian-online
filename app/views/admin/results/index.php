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
                    <h1 class="h3 fw-bold text-dark mb-1">Ringkasan Hasil Ujian Tingkat Sekolah</h1>
                    <p class="text-muted mb-0 text-sm">Pemantauan menyeluruh hasil dan statistik performa ujian seluruh mata pelajaran dan guru.</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary btn-sm px-3" type="button" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                    </button>
                </div>
            </div>

            <!-- Global Metric Summary Cards -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="custom-card p-3 h-100 hover-lift">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-xs fw-semibold text-uppercase">Total Sesi Ujian</span>
                                <h3 class="fw-bold mb-0 mt-1 text-primary"><?= $stats['total_exams']; ?></h3>
                                <span class="text-xs text-muted">Sesi Aktif & Selesai</span>
                            </div>
                            <div class="stat-icon stat-icon-indigo">
                                <i class="bi bi-journal-check"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="custom-card p-3 h-100 hover-lift">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-xs fw-semibold text-uppercase">Total Submit Peserta</span>
                                <h3 class="fw-bold mb-0 mt-1 text-dark"><?= $stats['total_submissions']; ?></h3>
                                <span class="text-xs text-muted">Lembar Selesai</span>
                            </div>
                            <div class="stat-icon stat-icon-cyan">
                                <i class="bi bi-clipboard-check-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="custom-card p-3 h-100 hover-lift">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-xs fw-semibold text-uppercase">Rata-rata Sekolah</span>
                                <h3 class="fw-bold mb-0 mt-1 text-success"><?= number_format($stats['school_avg_score'], 1); ?></h3>
                                <span class="text-xs text-success"><i class="bi bi-graph-up-arrow"></i> Performa Global</span>
                            </div>
                            <div class="stat-icon stat-icon-emerald">
                                <i class="bi bi-bar-chart-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="custom-card p-3 h-100 hover-lift">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-xs fw-semibold text-uppercase">Tingkat Kelulusan</span>
                                <h3 class="fw-bold mb-0 mt-1 text-warning"><?= $stats['school_passing_rate']; ?>%</h3>
                                <span class="text-xs text-muted">Mencapai KKM</span>
                            </div>
                            <div class="stat-icon stat-icon-amber">
                                <i class="bi bi-award-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="custom-card p-3 mb-4">
                <form action="<?= BASE_URL; ?>/admin/results" method="GET" class="row g-2 align-items-center">
                    <div class="col-12 col-md-6 col-lg-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" name="search" placeholder="Cari judul ujian, mata pelajaran, guru, kelas..." value="<?= htmlspecialchars($search ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm px-3">Cari</button>
                        <?php if (!empty($search)): ?>
                            <a href="<?= BASE_URL; ?>/admin/results" class="btn btn-outline-secondary btn-sm px-3">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- School Exam Results Table -->
            <div class="custom-card overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-sm">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3 py-3">Sesi Ujian</th>
                                <th>Guru Penguji</th>
                                <th>Kelas & Mapel</th>
                                <th class="text-center">Submit / Peserta</th>
                                <th class="text-center">Rata-rata Nilai</th>
                                <th class="text-center">Tertinggi / Terendah</th>
                                <th class="text-center">Kelulusan KKM</th>
                                <th class="text-center">Publikasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($exams)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        Tidak ada data hasil ujian yang sesuai dengan kriteria.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($exams as $ex): ?>
                                    <tr>
                                        <td class="ps-3 py-3">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($ex['title']); ?></div>
                                            <span class="text-xs text-muted">Jadwal: <?= date('d/m/y H:i', strtotime($ex['start_time'])); ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($ex['teacher_name']); ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($ex['subject_name']); ?></div>
                                            <span class="badge bg-light text-secondary border text-xs"><?= htmlspecialchars($ex['class_name']); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-bold text-dark"><?= $ex['total_submitted']; ?></span>
                                            <span class="text-muted text-xs">/ <?= $ex['total_assigned']; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="h6 fw-bold mb-0 text-primary"><?= number_format($ex['avg_score'], 1); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <div class="text-xs">
                                                <span class="text-success fw-bold"><?= number_format($ex['highest_score'], 1); ?></span>
                                                <span class="text-muted">|</span>
                                                <span class="text-danger fw-bold"><?= number_format($ex['lowest_score'], 1); ?></span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?= $ex['passing_rate'] >= 70 ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning-emphasis'; ?> text-xs">
                                                <?= $ex['passing_rate']; ?>%
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ((int)$ex['show_result'] === 1): ?>
                                                <span class="badge bg-success-subtle text-success text-xs">Terbuka</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-muted text-xs">Tertutup</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
    </div>
</div>
