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
                        <h1 class="h3 fw-bold text-dark mb-0">Dashboard Guru & Penguji</h1>
                        <span class="badge badge-role-guru">Panel Guru</span>
                    </div>
                    <p class="text-muted mb-0 text-sm">Selamat datang, <strong><?= htmlspecialchars($user['full_name']); ?></strong> (NIP: <?= htmlspecialchars($teacher['nip'] ?? '-'); ?>)</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL; ?>/teacher/questionCreate" class="btn btn-outline-primary btn-sm px-3 shadow-sm">
                        <i class="bi bi-plus-circle me-1"></i> Buat Soal
                    </a>
                    <a href="<?= BASE_URL; ?>/teacher/examCreate" class="btn btn-primary btn-sm px-3 shadow-sm">
                        <i class="bi bi-journal-plus me-1"></i> Buat Ujian Baru
                    </a>
                </div>
            </div>

            <!-- Exam Status Breakdown (Draft, Published, Active/Running, Finished) -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="custom-card p-3 h-100 hover-lift border-start border-4 border-secondary">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-xs fw-bold text-uppercase">Draft Exams</span>
                                <h3 class="fw-bold mb-0 mt-1 text-secondary"><?= $stats['exam_draft']; ?></h3>
                                <a href="<?= BASE_URL; ?>/teacher/exams?status=draft" class="text-xs text-secondary text-decoration-none mt-1 d-inline-block">
                                    Lihat Draft <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                            <div class="stat-icon bg-secondary-subtle text-secondary">
                                <i class="bi bi-file-earmark"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="custom-card p-3 h-100 hover-lift border-start border-4 border-info">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-xs fw-bold text-uppercase">Published Exams</span>
                                <h3 class="fw-bold mb-0 mt-1 text-info"><?= $stats['exam_published']; ?></h3>
                                <a href="<?= BASE_URL; ?>/teacher/exams?status=published" class="text-xs text-info text-decoration-none mt-1 d-inline-block">
                                    Siap Ujian <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                            <div class="stat-icon bg-info-subtle text-info">
                                <i class="bi bi-check2-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="custom-card p-3 h-100 hover-lift border-start border-4 border-success">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-xs fw-bold text-uppercase">Active / Running</span>
                                <h3 class="fw-bold mb-0 mt-1 text-success"><?= $stats['exam_running']; ?></h3>
                                <a href="<?= BASE_URL; ?>/teacher/exams?status=running" class="text-xs text-success text-decoration-none mt-1 d-inline-block">
                                    Sedang Berlangsung <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                            <div class="stat-icon bg-success-subtle text-success">
                                <i class="bi bi-play-circle-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="custom-card p-3 h-100 hover-lift border-start border-4 border-dark">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-xs fw-bold text-uppercase">Finished Exams</span>
                                <h3 class="fw-bold mb-0 mt-1 text-dark"><?= $stats['exam_finished']; ?></h3>
                                <a href="<?= BASE_URL; ?>/teacher/exams?status=finished" class="text-xs text-dark text-decoration-none mt-1 d-inline-block">
                                    Ujian Selesai <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                            <div class="stat-icon bg-dark-subtle text-dark">
                                <i class="bi bi-award-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resource Metrics Overview (Bank Soal & Mapel) -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-4">
                    <div class="custom-card p-3 h-100 hover-lift">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-xs fw-semibold text-uppercase">Bank Soal Saya</span>
                                <h3 class="fw-bold mb-0 mt-1 text-primary"><?= $stats['my_questions']; ?></h3>
                                <a href="<?= BASE_URL; ?>/teacher/questions" class="text-xs text-primary text-decoration-none mt-1 d-inline-block">
                                    Kelola Bank Soal <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                            <div class="stat-icon stat-icon-indigo">
                                <i class="bi bi-collection-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="custom-card p-3 h-100 hover-lift">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-xs fw-semibold text-uppercase">Total Sesi Ujian</span>
                                <h3 class="fw-bold mb-0 mt-1 text-success"><?= $stats['exam_total']; ?></h3>
                                <a href="<?= BASE_URL; ?>/teacher/exams" class="text-xs text-success text-decoration-none mt-1 d-inline-block">
                                    Semua Ujian <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                            <div class="stat-icon stat-icon-emerald">
                                <i class="bi bi-journal-check"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="custom-card p-3 h-100 hover-lift">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-xs fw-semibold text-uppercase">Kurikulum Mapel</span>
                                <h3 class="fw-bold mb-0 mt-1 text-warning"><?= $stats['total_subjects']; ?></h3>
                                <span class="text-xs text-muted">Mata Pelajaran Sekolah</span>
                            </div>
                            <div class="stat-icon stat-icon-amber">
                                <i class="bi bi-journal-bookmark-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Exams Table -->
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="custom-card">
                        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                            <h2 class="h6 fw-bold text-dark mb-0"><i class="bi bi-journal-text text-primary me-2"></i>Sesi Ujian Terbaru Saya</h2>
                            <a href="<?= BASE_URL; ?>/teacher/exams" class="btn btn-outline-primary btn-sm text-xs">
                                Kelola Semua Ujian <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;" class="text-center">No</th>
                                        <th>Judul Ujian</th>
                                        <th>Mata Pelajaran</th>
                                        <th>Kelas</th>
                                        <th class="text-center">Durasi</th>
                                        <th class="text-center">Jml Soal</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_exams)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted">
                                                <i class="bi bi-journal-x fs-3 d-block mb-1"></i>
                                                Belum ada sesi ujian yang dibuat.
                                                <div class="mt-2">
                                                    <a href="<?= BASE_URL; ?>/teacher/examCreate" class="btn btn-primary btn-sm text-xs">
                                                        <i class="bi bi-plus-circle me-1"></i> Buat Sesi Ujian Pertama
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_exams as $idx => $ex): ?>
                                            <tr>
                                                <td class="text-center text-muted"><?= $idx + 1; ?></td>
                                                <td>
                                                    <a href="<?= BASE_URL; ?>/teacher/examShow/<?= $ex['id']; ?>" class="text-decoration-none fw-bold text-dark">
                                                        <?= htmlspecialchars($ex['title']); ?>
                                                    </a>
                                                    <?php if (!empty($ex['token'])): ?>
                                                        <span class="badge bg-light text-dark border font-monospace ms-1 text-xs"><?= htmlspecialchars($ex['token']); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($ex['subject_name']); ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary-subtle text-secondary border"><?= htmlspecialchars($ex['class_name']); ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="text-xs fw-semibold"><?= $ex['duration_minutes']; ?> mnt</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary-subtle text-primary"><?= $ex['total_questions']; ?> butir</span>
                                                </td>
                                                <td class="text-center">
                                                    <?php
                                                    $stBadge = 'bg-secondary';
                                                    if ($ex['status'] === 'published') $stBadge = 'bg-info text-dark';
                                                    elseif ($ex['status'] === 'running') $stBadge = 'bg-success';
                                                    elseif ($ex['status'] === 'finished') $stBadge = 'bg-dark';
                                                    ?>
                                                    <span class="badge <?= $stBadge; ?> text-capitalize"><?= $ex['status']; ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="<?= BASE_URL; ?>/teacher/examShow/<?= $ex['id']; ?>" class="btn btn-outline-info py-0 px-2" title="Preview Ujian">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="<?= BASE_URL; ?>/teacher/examEdit/<?= $ex['id']; ?>" class="btn btn-outline-warning py-0 px-2" title="Edit Ujian">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Questions Table & Profile Summary -->
            <div class="row g-4">
                <div class="col-12 col-lg-8">
                    <div class="custom-card">
                        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                            <h2 class="h6 fw-bold text-dark mb-0"><i class="bi bi-collection text-primary me-2"></i>Butir Soal Terbaru Saya</h2>
                            <a href="<?= BASE_URL; ?>/teacher/questions" class="btn btn-outline-primary btn-sm text-xs">
                                Lihat Semua Soal <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;" class="text-center">No</th>
                                        <th>Mata Pelajaran</th>
                                        <th>Pertanyaan</th>
                                        <th class="text-center">Tipe</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_questions)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">
                                                <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                                                Belum ada butir soal di Bank Soal Anda.
                                                <div class="mt-2">
                                                    <a href="<?= BASE_URL; ?>/teacher/questionCreate" class="btn btn-primary btn-sm text-xs">
                                                        <i class="bi bi-plus-circle me-1"></i> Buat Soal Pertama
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_questions as $idx => $q): ?>
                                            <tr>
                                                <td class="text-center text-muted"><?= $idx + 1; ?></td>
                                                <td>
                                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($q['subject_name']); ?></span>
                                                </td>
                                                <td>
                                                    <div class="text-truncate text-dark fw-medium" style="max-width: 300px;">
                                                        <?= htmlspecialchars($q['question_text']); ?>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($q['question_type'] === 'multiple_choice'): ?>
                                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Pilihan Ganda</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-info-subtle text-info border border-info-subtle">Essay</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <a href="<?= BASE_URL; ?>/teacher/questionShow/<?= $q['id']; ?>" class="btn btn-outline-info btn-sm py-0 px-2" title="Detail">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="custom-card p-4">
                        <h2 class="h6 fw-bold text-dark mb-3"><i class="bi bi-person-badge text-primary me-2"></i>Profil Penguji</h2>
                        <ul class="list-unstyled mb-0 d-flex flex-column gap-2 text-sm">
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Nama:</span>
                                <strong><?= htmlspecialchars($user['full_name']); ?></strong>
                            </li>
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">NIP:</span>
                                <span><?= htmlspecialchars($teacher['nip'] ?? '-'); ?></span>
                            </li>
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Spesialisasi:</span>
                                <span class="text-truncate" style="max-width: 140px;"><?= htmlspecialchars($teacher['specialization'] ?? '-'); ?></span>
                            </li>
                            <li class="d-flex justify-content-between py-1">
                                <span class="text-muted">Email:</span>
                                <span class="text-truncate" style="max-width: 140px;"><?= htmlspecialchars($user['email']); ?></span>
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
