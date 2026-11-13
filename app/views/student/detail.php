<?php
require_once __DIR__ . '/../layouts/header.php';
$hasToken = !empty($exam['token']);
$isSubmitted = ($exam['participant_status'] === 'submitted');
$isInProgress = ($exam['participant_status'] === 'in_progress');
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

            <!-- Breadcrumb Navigation -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb text-sm mb-0">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL; ?>/student/dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= BASE_URL; ?>/student/exams">Ujian Tersedia</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($exam['title']); ?></li>
                </ol>
            </nav>

            <div class="row g-4">
                <!-- Left Column: Detailed Information -->
                <div class="col-12 col-lg-8">
                    <div class="custom-card p-4 h-100">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-primary-subtle text-primary fw-bold text-xs"><?= htmlspecialchars($exam['subject_code']); ?></span>
                            <span class="badge bg-light text-secondary border text-xs"><?= htmlspecialchars($exam['class_name']); ?></span>
                        </div>
                        <h1 class="h3 fw-bold text-dark mb-3"><?= htmlspecialchars($exam['title']); ?></h1>
                        
                        <?php if (!empty($exam['description'])): ?>
                            <p class="text-muted text-sm mb-4 leading-relaxed"><?= nl2br(htmlspecialchars($exam['description'])); ?></p>
                        <?php else: ?>
                            <p class="text-muted text-sm mb-4 fst-italic">Tidak ada petunjuk khusus tambahan dari guru.</p>
                        <?php endif; ?>

                        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-info-circle text-primary me-2"></i>Informasi & Ketentuan Ujian</h5>
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered text-sm align-middle mb-0">
                                <tbody>
                                    <tr>
                                        <th class="bg-light text-muted fw-semibold" style="width: 35%;">Mata Pelajaran</th>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($exam['subject_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted fw-semibold">Guru Penguji</th>
                                        <td><?= htmlspecialchars($exam['teacher_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted fw-semibold">Durasi Pengerjaan</th>
                                        <td><strong><?= (int)$exam['duration_minutes']; ?> Menit</strong> (Countdown berjalan saat ujian dimulai)</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted fw-semibold">Passing Score (KKM)</th>
                                        <td><span class="badge bg-success-subtle text-success fw-bold"><?= number_format((float)$exam['passing_score'], 1); ?> Poin</span></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted fw-semibold">Jadwal Mulai</th>
                                        <td><?= date('d F Y, H:i', strtotime($exam['start_time'])); ?> WIB</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted fw-semibold">Batas Waktu Akses</th>
                                        <td><?= date('d F Y, H:i', strtotime($exam['end_time'])); ?> WIB</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted fw-semibold">Pengacakan Soal / Opsi</th>
                                        <td>
                                            <?= !empty($exam['is_random_question']) ? '<span class="badge bg-info-subtle text-info me-1">Soal Diacak</span>' : '<span class="badge bg-light text-muted border me-1">Urutan Tetap</span>'; ?>
                                            <?= !empty($exam['is_random_option']) ? '<span class="badge bg-info-subtle text-info">Opsi Diacak</span>' : '<span class="badge bg-light text-muted border">Opsi Tetap</span>'; ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-warning border-0 d-flex align-items-start gap-3 rounded-3 mb-0">
                            <i class="bi bi-exclamation-triangle-fill text-warning fs-4 mt-1"></i>
                            <div class="text-sm">
                                <strong>Penting:</strong>
                                <ul class="mb-0 ps-3 mt-1">
                                    <li>Pastikan koneksi internet stabil sebelum menekan tombol mulai.</li>
                                    <li>Jawaban Anda akan disimpan otomatis setiap kali memilih opsi atau mengisi jawaban.</li>
                                    <li>Jika waktu habis, sistem server akan langsung melakukan <em>auto-submit</em> otomatis.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Status & Start Action Box -->
                <div class="col-12 col-lg-4">
                    <div class="custom-card p-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="fw-bold text-dark mb-3">Status Pengerjaan</h5>
                            <div class="p-3 bg-light rounded-3 mb-4 text-center">
                                <?php if ($isSubmitted): ?>
                                    <i class="bi bi-check-circle-fill text-success fs-1 mb-2 d-block"></i>
                                    <div class="fw-bold text-success">Ujian Telah Selesai</div>
                                    <span class="text-xs text-muted">Dikumpulkan pada: <?= date('d/m/Y H:i', strtotime($exam['participant_submit_time'])); ?></span>
                                <?php elseif ($isInProgress): ?>
                                    <i class="bi bi-hourglass-split text-warning fs-1 mb-2 d-block"></i>
                                    <div class="fw-bold text-warning">Sedang Berlangsung</div>
                                    <span class="text-xs text-muted">Dimulai pada: <?= date('d/m/Y H:i', strtotime($exam['participant_start_time'])); ?></span>
                                <?php elseif ($isBeforeSchedule): ?>
                                    <i class="bi bi-calendar-x text-info fs-1 mb-2 d-block"></i>
                                    <div class="fw-bold text-info">Belum Dibuka</div>
                                    <span class="text-xs text-muted">Ujian dimulai pada <?= date('d/m H:i', strtotime($exam['start_time'])); ?></span>
                                <?php elseif ($isAfterSchedule): ?>
                                    <i class="bi bi-x-circle text-secondary fs-1 mb-2 d-block"></i>
                                    <div class="fw-bold text-secondary">Jadwal Telah Berakhir</div>
                                    <span class="text-xs text-muted">Ujian ditutup pada <?= date('d/m H:i', strtotime($exam['end_time'])); ?></span>
                                <?php else: ?>
                                    <i class="bi bi-pencil-square text-primary fs-1 mb-2 d-block"></i>
                                    <div class="fw-bold text-primary">Siap Dikerjakan</div>
                                    <span class="text-xs text-muted">Silakan masukkan token dan mulai ujian</span>
                                <?php endif; ?>
                            </div>

                            <?php if ($isSubmitted): ?>
                                <a href="<?= BASE_URL; ?>/student/examResult/<?= $exam['exam_id']; ?>" class="btn btn-success w-100 py-2 fw-bold">
                                    <i class="bi bi-trophy me-1"></i> Lihat Hasil Ujian
                                </a>
                            <?php elseif ($isInProgress): ?>
                                <a href="<?= BASE_URL; ?>/student/examRoom/<?= $exam['exam_id']; ?>" class="btn btn-warning w-100 py-2 fw-bold text-dark">
                                    <i class="bi bi-play-circle-fill me-1"></i> Lanjutkan Ujian Sekarang
                                </a>
                            <?php elseif ($isScheduleOpen): ?>
                                <!-- Form Start Exam -->
                                <form action="<?= BASE_URL; ?>/student/startExam/<?= $exam['exam_id']; ?>" method="POST">
                                    <?php if ($hasToken): ?>
                                        <div class="mb-3">
                                            <label for="tokenInput" class="form-label text-sm fw-bold text-dark">
                                                <i class="bi bi-key-fill text-primary me-1"></i> Token Ujian <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" 
                                                   class="form-control text-uppercase text-center fw-bold fs-5 tracking-wide" 
                                                   id="tokenInput" 
                                                   name="token" 
                                                   placeholder="6 Karakter" 
                                                   maxlength="10" 
                                                   required 
                                                   autocomplete="off">
                                            <div class="form-text text-xs text-muted text-center">Mintalah token sesi ujian kepada guru pengawas.</div>
                                        </div>
                                    <?php endif; ?>

                                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                                        <i class="bi bi-arrow-right-circle me-1"></i> Masuk ke System Check
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-secondary w-100 py-2 fw-bold" disabled>
                                    Ujian Tidak Dapat Diikuti
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="pt-4 mt-3 border-top text-center">
                            <a href="<?= BASE_URL; ?>/student/exams" class="text-muted text-xs">
                                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Ujian
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <?php require_once __DIR__ . '/../layouts/footer.php'; ?>
    </div>
</div>
