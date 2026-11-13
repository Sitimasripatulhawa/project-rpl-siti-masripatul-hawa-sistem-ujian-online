<?php
require_once __DIR__ . '/../../layouts/header.php';
$showResult = (int)($exam['show_result'] ?? 1);
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

            <!-- Breadcrumb Navigation -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb text-sm mb-0">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL; ?>/teacher/dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= BASE_URL; ?>/teacher/results">Hasil Ujian</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($exam['title']); ?></li>
                </ol>
            </nav>

            <!-- Page Header & Action Bar -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h1 class="h3 fw-bold text-dark mb-0"><?= htmlspecialchars($exam['title']); ?></h1>
                        <span class="badge bg-primary-subtle text-primary fw-bold text-xs"><?= htmlspecialchars($exam['subject_name']); ?></span>
                        <span class="badge bg-light text-secondary border text-xs"><?= htmlspecialchars($exam['class_name']); ?></span>
                    </div>
                    <p class="text-muted mb-0 text-sm">
                        KKM: <strong><?= number_format((float)$exam['passing_score'], 1); ?> Poin</strong> | Durasi: <?= (int)$exam['duration_minutes']; ?> Menit
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <!-- Toggle Result Visibility Button -->
                    <form action="<?= BASE_URL; ?>/teacher/toggleResultVisibility/<?= $exam['exam_id']; ?>" method="POST" class="m-0">
                        <?= Csrf::tokenField(); ?>
                        <?php if ($showResult === 1): ?>
                            <button type="submit" class="btn btn-outline-warning btn-sm px-3" title="Klik untuk menyembunyikan nilai dari peserta">
                                <i class="bi bi-eye-slash-fill me-1"></i> Sembunyikan Hasil dari Siswa
                            </button>
                        <?php else: ?>
                            <button type="submit" class="btn btn-success btn-sm px-3" title="Klik untuk mempublikasikan nilai ke peserta">
                                <i class="bi bi-eye-fill me-1"></i> Publikasikan Hasil ke Siswa
                            </button>
                        <?php endif; ?>
                    </form>
                    <a href="<?= BASE_URL; ?>/teacher/results" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>

            <!-- 4 Simple Statistics Cards -->
            <div class="row g-3 mb-4">
                <!-- 1. Nilai Tertinggi -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="custom-card p-3 h-100 hover-lift">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-xs fw-semibold text-uppercase">Nilai Tertinggi</span>
                                <h3 class="fw-bold mb-0 mt-1 text-success"><?= number_format($stats['highest_score'], 1); ?></h3>
                                <span class="text-xs text-muted">Skor Maksimal</span>
                            </div>
                            <div class="stat-icon stat-icon-emerald">
                                <i class="bi bi-arrow-up-right-circle-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Nilai Terendah -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="custom-card p-3 h-100 hover-lift">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-xs fw-semibold text-uppercase">Nilai Terendah</span>
                                <h3 class="fw-bold mb-0 mt-1 text-danger"><?= number_format($stats['lowest_score'], 1); ?></h3>
                                <span class="text-xs text-muted">Skor Minimal</span>
                            </div>
                            <div class="stat-icon stat-icon-rose">
                                <i class="bi bi-arrow-down-right-circle-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Nilai Rata-rata -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="custom-card p-3 h-100 hover-lift">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-xs fw-semibold text-uppercase">Nilai Rata-rata</span>
                                <h3 class="fw-bold mb-0 mt-1 text-primary"><?= number_format($stats['avg_score'], 1); ?></h3>
                                <span class="text-xs text-primary">Kelulusan: <?= $stats['passing_rate']; ?>%</span>
                            </div>
                            <div class="stat-icon stat-icon-indigo">
                                <i class="bi bi-bar-chart-line-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Jumlah Peserta -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="custom-card p-3 h-100 hover-lift">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-xs fw-semibold text-uppercase">Jumlah Peserta</span>
                                <h3 class="fw-bold mb-0 mt-1 text-dark"><?= $stats['total_submitted']; ?> <small class="text-muted fs-6 fw-normal">/ <?= $stats['total_assigned']; ?></small></h3>
                                <span class="text-xs text-muted"><?= $stats['total_passed']; ?> Peserta Lulus KKM</span>
                            </div>
                            <div class="stat-icon stat-icon-amber">
                                <i class="bi bi-people-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters & Search Toolbar -->
            <div class="custom-card p-3 mb-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <!-- Filter Tabs -->
                    <ul class="nav nav-pills custom-pills">
                        <li class="nav-item">
                            <a class="nav-link <?= ($current_filter === 'all') ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/teacher/examResultDetail/<?= $exam['exam_id']; ?>?filter=all">Semua Peserta</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= ($current_filter === 'passed') ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/teacher/examResultDetail/<?= $exam['exam_id']; ?>?filter=passed">Lulus KKM</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= ($current_filter === 'failed') ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/teacher/examResultDetail/<?= $exam['exam_id']; ?>?filter=failed">Belum Lulus</a>
                        </li>
                        <?php if ((int)$exam['total_essay_questions'] > 0): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= ($current_filter === 'pending_essay') ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/teacher/examResultDetail/<?= $exam['exam_id']; ?>?filter=pending_essay">
                                    Menunggu Penilaian Essay (<?= $stats['pending_essay_total']; ?>)
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>

                    <!-- Search Input -->
                    <form action="<?= BASE_URL; ?>/teacher/examResultDetail/<?= $exam['exam_id']; ?>" method="GET" class="d-flex gap-2">
                        <input type="hidden" name="filter" value="<?= htmlspecialchars($current_filter); ?>">
                        <input type="text" class="form-control form-control-sm" name="search" placeholder="Cari nama / NIS..." value="<?= htmlspecialchars($search ?? ''); ?>" style="min-width: 180px;">
                        <button type="submit" class="btn btn-primary btn-sm px-3">Cari</button>
                        <?php if (!empty($search)): ?>
                            <a href="<?= BASE_URL; ?>/teacher/examResultDetail/<?= $exam['exam_id']; ?>?filter=<?= htmlspecialchars($current_filter); ?>" class="btn btn-outline-secondary btn-sm px-2">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Participants Results Table -->
            <div class="custom-card overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-sm">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3 py-3" style="width: 50px;">No</th>
                                <th>Peserta Didik</th>
                                <th>Waktu Submit</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Benar / Salah / Kosong</th>
                                <th class="text-center">Nilai Akhir</th>
                                <th class="text-center">Hasil KKM</th>
                                <th class="text-center">Review Essay</th>
                                <th class="text-end pe-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($participants)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">
                                        Tidak ada peserta yang sesuai dengan filter atau pencarian Anda.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($participants as $p): 
                                    $isSubmitted = ($p['participant_status'] === 'submitted');
                                    $hasResult = ($p['result_id'] !== null);
                                    $isPassed = $hasResult && ((int)$p['is_passed'] === 1);
                                    $pendingEssay = (int)$p['pending_essay_count'];
                                ?>
                                    <tr>
                                        <td class="ps-3 py-3 text-muted"><?= $no++; ?></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($p['student_name']); ?></div>
                                            <span class="text-xs text-muted">NIS: <?= htmlspecialchars($p['nis']); ?> (<?= $p['gender']; ?>)</span>
                                        </td>
                                        <td>
                                            <?php if ($isSubmitted && !empty($p['participant_submit_time'])): ?>
                                                <div class="text-dark fw-semibold"><?= date('d/m/y H:i', strtotime($p['participant_submit_time'])); ?></div>
                                                <span class="text-xs text-muted">Mulai: <?= date('H:i', strtotime($p['participant_start_time'])); ?></span>
                                            <?php elseif ($p['participant_status'] === 'in_progress'): ?>
                                                <span class="badge bg-warning text-dark text-xs"><i class="bi bi-hourglass-split"></i> Sedang Mengerjakan</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-muted text-xs">Belum Dimulai</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($isSubmitted): ?>
                                                <span class="badge bg-success-subtle text-success text-xs">Selesai</span>
                                            <?php elseif ($p['participant_status'] === 'in_progress'): ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis text-xs">In Progress</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border text-xs">Not Started</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($hasResult): ?>
                                                <span class="text-success fw-bold"><?= (int)$p['total_correct']; ?></span>
                                                <span class="text-muted">/</span>
                                                <span class="text-danger fw-bold"><?= (int)$p['total_incorrect']; ?></span>
                                                <span class="text-muted">/</span>
                                                <span class="text-secondary"><?= (int)$p['total_unanswered']; ?></span>
                                            <?php else: ?>
                                                <span class="text-muted text-xs">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($hasResult): ?>
                                                <span class="h6 fw-bold mb-0 <?= $isPassed ? 'text-success' : 'text-danger'; ?>">
                                                    <?= number_format((float)$p['final_score'], 1); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted text-xs">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($hasResult): ?>
                                                <?php if ($isPassed): ?>
                                                    <span class="badge bg-success-subtle text-success text-xs">Lulus</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger text-xs">Tidak Lulus</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border text-xs">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ((int)$exam['total_essay_questions'] > 0): ?>
                                                <?php if ($pendingEssay > 0): ?>
                                                    <span class="badge bg-warning-subtle text-warning-emphasis text-xs">
                                                        <i class="bi bi-clock-history me-1"></i> <?= $pendingEssay; ?> Menunggu Penilaian
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-success-subtle text-success text-xs">Lengkap</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted text-xs">Otomatis (MCQ)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-3">
                                            <?php if ($isSubmitted): ?>
                                                <a href="<?= BASE_URL; ?>/teacher/reviewAnswers/<?= $p['participant_id']; ?>" class="btn btn-outline-primary btn-sm px-3">
                                                    <i class="bi bi-search me-1"></i> Review Jawaban
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-light btn-sm px-3 text-muted border" disabled>
                                                    Belum Submit
                                                </button>
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
