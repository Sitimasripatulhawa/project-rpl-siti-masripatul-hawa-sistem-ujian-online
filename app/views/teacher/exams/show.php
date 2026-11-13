<?php
require_once __DIR__ . '/../../layouts/header.php';

$exam = $exam ?? [];
$questions = $exam['questions'] ?? [];
$participants = $exam['participants'] ?? [];
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
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <a href="<?= BASE_URL; ?>/teacher/exams" class="btn btn-outline-secondary btn-sm p-1 px-2">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        <h1 class="h3 fw-bold text-dark mb-0"><?= htmlspecialchars($exam['title']); ?></h1>
                        <?php
                        $stClass = 'bg-secondary';
                        if ($exam['status'] === 'published') $stClass = 'bg-info text-dark';
                        elseif ($exam['status'] === 'running') $stClass = 'bg-success';
                        elseif ($exam['status'] === 'finished') $stClass = 'bg-dark';
                        ?>
                        <span class="badge <?= $stClass; ?> text-capitalize"><?= $exam['status']; ?></span>
                    </div>
                    <p class="text-muted mb-0 text-sm">Preview detail sesi ujian, daftar soal yang di-assign, dan daftar peserta kelas sasaran.</p>
                </div>
                <div class="d-flex gap-2">
                    <?php if (in_array($exam['status'], ['draft', 'published'])): ?>
                        <a href="<?= BASE_URL; ?>/teacher/examTogglePublish/<?= $exam['id']; ?>" class="btn btn-outline-info btn-sm px-3 shadow-sm">
                            <?= ($exam['status'] === 'published') ? '<i class="bi bi-arrow-counterclockwise me-1"></i> Ubah ke Draft' : '<i class="bi bi-send-check me-1"></i> Terbitkan (Publish)'; ?>
                        </a>
                    <?php endif; ?>
                    <a href="<?= BASE_URL; ?>/teacher/examEdit/<?= $exam['id']; ?>" class="btn btn-warning btn-sm px-3 shadow-sm">
                        <i class="bi bi-pencil me-1"></i> Edit Ujian
                    </a>
                    <button type="button" class="btn btn-outline-danger btn-sm px-3" data-bs-toggle="modal" data-bs-target="#deleteExamModal">
                        <i class="bi bi-trash me-1"></i> Hapus
                    </button>
                </div>
            </div>

            <!-- Exam Overview Meta Grid -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="custom-card p-3 h-100">
                        <span class="text-muted text-xs fw-semibold text-uppercase d-block mb-1">Mata Pelajaran & Kelas</span>
                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($exam['subject_name']); ?></div>
                        <div class="text-xs text-primary fw-semibold mt-1">
                            <i class="bi bi-door-open me-1"></i><?= htmlspecialchars($exam['class_name']); ?> (Tk. <?= htmlspecialchars($exam['class_level']); ?>)
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <div class="custom-card p-3 h-100">
                        <span class="text-muted text-xs fw-semibold text-uppercase d-block mb-1">Token Akses Ujian</span>
                        <div class="d-flex align-items-center gap-2">
                            <span class="fs-5 fw-bold font-monospace text-primary"><?= htmlspecialchars($exam['token']); ?></span>
                            <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 text-xs" onclick="copyToClipboard('<?= $exam['token']; ?>', this)" title="Salin Token">
                                <i class="bi bi-clipboard"></i> Salin
                            </button>
                            </button>
                        </div>
                        <div class="text-xs text-muted mt-1">Durasi: <?= $exam['duration_minutes']; ?> Menit</div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <div class="custom-card p-3 h-100">
                        <span class="text-muted text-xs fw-semibold text-uppercase d-block mb-1">Jadwal Pelaksanaan</span>
                        <div class="text-xs text-dark fw-bold">Mulai: <?= date('d M Y H:i', strtotime($exam['start_time'])); ?></div>
                        <div class="text-xs text-muted mt-1">Selesai: <?= date('d M Y H:i', strtotime($exam['end_time'])); ?></div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <div class="custom-card p-3 h-100">
                        <span class="text-muted text-xs fw-semibold text-uppercase d-block mb-1">Pengaturan Ujian</span>
                        <div class="d-flex flex-wrap gap-1 mt-1">
                            <span class="badge bg-light text-dark border text-xs">KKM: <?= number_format($exam['passing_score'], 0); ?></span>
                            <?php if ($exam['is_random_question']): ?>
                                <span class="badge bg-secondary-subtle text-secondary text-xs"><i class="bi bi-shuffle"></i> Soal Acak</span>
                            <?php endif; ?>
                            <?php if ($exam['is_random_option']): ?>
                                <span class="badge bg-secondary-subtle text-secondary text-xs"><i class="bi bi-shuffle"></i> Opsi Acak</span>
                            <?php endif; ?>
                            <?php if ($exam['enable_proctoring']): ?>
                                <span class="badge bg-info-subtle text-info text-xs"><i class="bi bi-camera-video"></i> AI Proctoring</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($exam['description'])): ?>
                <div class="custom-card p-3 mb-4 bg-light border">
                    <div class="text-xs fw-bold text-uppercase text-secondary mb-1"><i class="bi bi-info-circle me-1"></i>Petunjuk Pengerjaan</div>
                    <div class="text-sm text-dark"><?= nl2br(htmlspecialchars($exam['description'])); ?></div>
                </div>
            <?php endif; ?>

            <!-- Navigation Tabs: Soal Ujian & Peserta Terdaftar -->
            <ul class="nav nav-tabs mb-4" id="examDetailTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-semibold text-sm" id="questions-tab" data-bs-toggle="tab" data-bs-target="#questions-pane" type="button" role="tab" aria-controls="questions-pane" aria-selected="true">
                        <i class="bi bi-collection-fill me-1 text-primary"></i> Daftar Soal (<?= count($questions); ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold text-sm" id="participants-tab" data-bs-toggle="tab" data-bs-target="#participants-pane" type="button" role="tab" aria-controls="participants-pane" aria-selected="false">
                        <i class="bi bi-people-fill me-1 text-primary"></i> Peserta Kelas Sasaran (<?= count($participants); ?>)
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="examDetailTabContent">
                <!-- TAB 1: Assigned Questions Preview -->
                <div class="tab-pane fade show active" id="questions-pane" role="tabpanel" aria-labelledby="questions-tab">
                    <?php if (empty($questions)): ?>
                        <div class="custom-card p-5 text-center text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            Belum ada soal yang di-assign untuk sesi ujian ini.
                            <div class="mt-2">
                                <a href="<?= BASE_URL; ?>/teacher/examEdit/<?= $exam['id']; ?>" class="btn btn-primary btn-sm text-xs">
                                    <i class="bi bi-plus-circle me-1"></i> Pilih Soal Sekarang
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($questions as $q): ?>
                                <div class="custom-card p-4">
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-primary rounded-circle p-2 px-2.5 fs-6">
                                                <?= $q['question_order']; ?>
                                            </span>
                                            <div>
                                                <span class="badge bg-info-subtle text-info border border-info-subtle text-xs">
                                                    <?= ($q['question_type'] === 'multiple_choice') ? 'Pilihan Ganda' : 'Essay'; ?>
                                                </span>
                                                <span class="badge bg-secondary-subtle text-secondary text-xs">
                                                    <?= ucfirst($q['difficulty']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <span class="badge bg-light text-dark border text-xs">
                                            Bobot: <?= number_format($q['question_score'], 2); ?> Poin
                                        </span>
                                    </div>

                                    <!-- Question Text -->
                                    <div class="text-dark fw-medium text-base mb-3 lh-base">
                                        <?= nl2br(htmlspecialchars($q['question_text'])); ?>
                                    </div>

                                    <!-- Options if Multiple Choice -->
                                    <?php if ($q['question_type'] === 'multiple_choice' && !empty($q['options'])): ?>
                                        <div class="row g-2 mb-2">
                                            <?php foreach ($q['options'] as $opt): ?>
                                                <?php $isCorrect = (bool)$opt['is_correct']; ?>
                                                <div class="col-12 col-md-6">
                                                    <div class="p-2.5 rounded-3 border d-flex align-items-start gap-2 <?= $isCorrect ? 'bg-success-subtle border-success text-dark' : 'bg-light text-secondary'; ?>">
                                                        <span class="badge <?= $isCorrect ? 'bg-success text-white' : 'bg-secondary text-white'; ?> rounded-circle px-2">
                                                            <?= htmlspecialchars($opt['option_label']); ?>
                                                        </span>
                                                        <div class="text-sm flex-grow-1">
                                                            <?= htmlspecialchars($opt['option_text']); ?>
                                                            <?php if ($isCorrect): ?>
                                                                <span class="badge bg-success text-white text-xs ms-1"><i class="bi bi-check-circle me-1"></i>Kunci Jawaban</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Explanation if available -->
                                    <?php if (!empty($q['explanation'])): ?>
                                        <div class="mt-3 p-2.5 rounded-3 bg-light border text-xs text-muted">
                                            <strong class="text-dark"><i class="bi bi-lightbulb text-warning me-1"></i>Pembahasan:</strong>
                                            <?= htmlspecialchars($q['explanation']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- TAB 2: Enrolled Class Participants -->
                <div class="tab-pane fade" id="participants-pane" role="tabpanel" aria-labelledby="participants-tab">
                    <div class="custom-card">
                        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                            <h2 class="h6 fw-bold text-dark mb-0">
                                <i class="bi bi-person-check text-primary me-2"></i>Daftar Peserta Kelas <?= htmlspecialchars($exam['class_name']); ?>
                            </h2>
                            <span class="badge bg-primary text-white"><?= count($participants); ?> Siswa Terdaftar</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;" class="text-center">No</th>
                                        <th>Nama Lengkap Siswa</th>
                                        <th>NIS / NISN</th>
                                        <th>Email</th>
                                        <th class="text-center">Status Ujian</th>
                                        <th class="text-center">Waktu Mulai</th>
                                        <th class="text-center">Waktu Selesai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($participants)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">
                                                <i class="bi bi-people fs-3 d-block mb-1"></i>
                                                Belum ada peserta didik yang terdaftar di kelas ini.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($participants as $idx => $p): ?>
                                            <tr>
                                                <td class="text-center text-muted"><?= $idx + 1; ?></td>
                                                <td>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($p['full_name']); ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($p['nis'] ?? '-'); ?></span>
                                                    <span class="text-xs text-muted"><?= htmlspecialchars($p['nisn'] ?? '-'); ?></span>
                                                </td>
                                                <td><?= htmlspecialchars($p['email']); ?></td>
                                                <td class="text-center">
                                                    <?php
                                                    $pBadge = 'bg-secondary';
                                                    $pLabel = 'Belum Mulai';
                                                    if ($p['status'] === 'in_progress') {
                                                        $pBadge = 'bg-warning text-dark';
                                                        $pLabel = 'Sedang Mengerjakan';
                                                    } elseif ($p['status'] === 'submitted' || $p['status'] === 'graded') {
                                                        $pBadge = 'bg-success';
                                                        $pLabel = 'Selesai';
                                                    } elseif ($p['status'] === 'violation_blocked') {
                                                        $pBadge = 'bg-danger';
                                                        $pLabel = 'Diblokir Proctoring';
                                                    }
                                                    ?>
                                                    <span class="badge <?= $pBadge; ?> text-xs"><?= $pLabel; ?></span>
                                                </td>
                                                <td class="text-center text-xs text-muted">
                                                    <?= !empty($p['start_time']) ? date('d/m/Y H:i', strtotime($p['start_time'])) : '-'; ?>
                                                </td>
                                                <td class="text-center text-xs text-muted">
                                                    <?= !empty($p['submit_time']) ? date('d/m/Y H:i', strtotime($p['submit_time'])) : '-'; ?>
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
        </main>

        <!-- Footer -->
        <?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteExamModal" tabindex="-1" aria-labelledby="deleteExamModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header modal-danger-header">
                <h5 class="modal-title fw-bold text-danger" id="deleteExamModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Konfirmasi Hapus Ujian
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <p class="mb-1 text-sm">Apakah Anda yakin ingin menghapus sesi ujian ini?</p>
                <div class="p-3 bg-light rounded-3 my-2 border">
                    <strong class="text-dark"><?= htmlspecialchars($exam['title']); ?></strong>
                </div>
                <div class="text-xs text-danger mt-2">
                    <i class="bi bi-info-circle me-1"></i> Data nilai, log proctoring, dan lembar jawaban peserta yang terkait akan ikut dihapus. Tindakan ini tidak dapat dibatalkan.
                </div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                <a href="<?= BASE_URL; ?>/teacher/examDelete/<?= $exam['id']; ?>" class="btn btn-danger btn-sm px-3">
                    <i class="bi bi-trash me-1"></i> Ya, Hapus Ujian
                </a>
            </div>
        </div>
    </div>
</div>
