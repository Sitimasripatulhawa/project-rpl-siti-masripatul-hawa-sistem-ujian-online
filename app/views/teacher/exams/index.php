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
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h1 class="h3 fw-bold text-dark mb-0">Manajemen Ujian</h1>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Panel Guru</span>
                    </div>
                    <p class="text-muted mb-0 text-sm">Buat, jadwalkan, dan kelola sesi ujian online untuk kelas yang Anda ajar.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL; ?>/teacher/examCreate" class="btn btn-primary btn-sm px-3 shadow-sm" id="btnCreateExam">
                        <i class="bi bi-plus-circle-fill me-1"></i> Buat Ujian Baru
                    </a>
                </div>
            </div>

            <!-- Status Quick Filter Cards -->
            <div class="row g-2 mb-4">
                <div class="col-6 col-md-3 col-xl-2">
                    <a href="<?= BASE_URL; ?>/teacher/exams" class="card text-decoration-none border-0 shadow-sm <?= empty($status_filter) ? 'bg-primary text-white' : 'bg-white text-dark'; ?> p-2 text-center rounded-3 hover-lift">
                        <div class="text-xs fw-semibold opacity-75">Semua</div>
                        <div class="fs-5 fw-bold"><?= $stats['total']; ?></div>
                    </a>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <a href="<?= BASE_URL; ?>/teacher/exams?status=draft" class="card text-decoration-none border-0 shadow-sm <?= ($status_filter === 'draft') ? 'bg-secondary text-white' : 'bg-white text-dark'; ?> p-2 text-center rounded-3 hover-lift">
                        <div class="text-xs fw-semibold <?= ($status_filter === 'draft') ? 'opacity-75' : 'text-secondary'; ?>">Draft</div>
                        <div class="fs-5 fw-bold text-secondary <?= ($status_filter === 'draft') ? 'text-white' : ''; ?>"><?= $stats['draft']; ?></div>
                    </a>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <a href="<?= BASE_URL; ?>/teacher/exams?status=published" class="card text-decoration-none border-0 shadow-sm <?= ($status_filter === 'published') ? 'bg-info text-white' : 'bg-white text-dark'; ?> p-2 text-center rounded-3 hover-lift">
                        <div class="text-xs fw-semibold <?= ($status_filter === 'published') ? 'opacity-75' : 'text-info'; ?>">Published</div>
                        <div class="fs-5 fw-bold text-info <?= ($status_filter === 'published') ? 'text-white' : ''; ?>"><?= $stats['published']; ?></div>
                    </a>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <a href="<?= BASE_URL; ?>/teacher/exams?status=running" class="card text-decoration-none border-0 shadow-sm <?= ($status_filter === 'running') ? 'bg-success text-white' : 'bg-white text-dark'; ?> p-2 text-center rounded-3 hover-lift">
                        <div class="text-xs fw-semibold <?= ($status_filter === 'running') ? 'opacity-75' : 'text-success'; ?>">Running</div>
                        <div class="fs-5 fw-bold text-success <?= ($status_filter === 'running') ? 'text-white' : ''; ?>"><?= $stats['running']; ?></div>
                    </a>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <a href="<?= BASE_URL; ?>/teacher/exams?status=finished" class="card text-decoration-none border-0 shadow-sm <?= ($status_filter === 'finished') ? 'bg-dark text-white' : 'bg-white text-dark'; ?> p-2 text-center rounded-3 hover-lift">
                        <div class="text-xs fw-semibold <?= ($status_filter === 'finished') ? 'opacity-75' : 'text-muted'; ?>">Finished</div>
                        <div class="fs-5 fw-bold text-dark <?= ($status_filter === 'finished') ? 'text-white' : ''; ?>"><?= $stats['finished']; ?></div>
                    </a>
                </div>
            </div>

            <!-- Filter & Search Bar -->
            <div class="custom-card p-3 mb-4">
                <form action="<?= BASE_URL; ?>/teacher/exams" method="GET" class="row g-2 align-items-center">
                    <?php if (!empty($status_filter)): ?>
                        <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter); ?>">
                    <?php endif; ?>
                    <div class="col-12 col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="q" class="form-control border-start-0" placeholder="Cari judul ujian, deskripsi, kelas..." value="<?= htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <select name="subject_id" class="form-select form-select-sm">
                            <option value="">-- Semua Mata Pelajaran --</option>
                            <?php foreach ($subjects as $s): ?>
                                <option value="<?= $s['id']; ?>" <?= ($subject_filter == $s['id']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($s['name']); ?> (<?= htmlspecialchars($s['code']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-filter me-1"></i> Filter
                        </button>
                        <?php if (!empty($search) || !empty($subject_filter) || !empty($status_filter)): ?>
                            <a href="<?= BASE_URL; ?>/teacher/exams" class="btn btn-outline-secondary btn-sm" title="Reset Filter">
                                <i class="bi bi-x-circle"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Exams List Table -->
            <div class="custom-card">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h2 class="h6 fw-bold text-dark mb-0">
                        <i class="bi bi-journal-check text-primary me-2"></i>Daftar Sesi Ujian (<?= count($exams); ?>)
                    </h2>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-sm">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;" class="text-center">No</th>
                                <th>Sesi Ujian & Token</th>
                                <th>Mata Pelajaran</th>
                                <th>Kelas Sasaran</th>
                                <th class="text-center">Jadwal & Durasi</th>
                                <th class="text-center">Soal & Peserta</th>
                                <th class="text-center">Status</th>
                                <th style="width: 140px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($exams)): ?>
                                <tr>
                                    <td colspan="8" class="p-0">
                                        <div class="empty-state border-0 my-3">
                                            <div class="empty-state-icon">
                                                <i class="bi bi-journal-x"></i>
                                            </div>
                                            <div class="empty-state-title">Tidak Ada Sesi Ujian</div>
                                            <div class="empty-state-text">
                                                Belum ada sesi ujian yang sesuai dengan filter atau kata kunci pencarian Anda.
                                            </div>
                                            <a href="<?= BASE_URL; ?>/teacher/examCreate" class="btn btn-primary btn-sm px-3">
                                                <i class="bi bi-plus-circle me-1"></i> Buat Ujian Baru
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($exams as $idx => $ex): ?>
                                    <tr>
                                        <td class="text-center text-muted"><?= $idx + 1; ?></td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <a href="<?= BASE_URL; ?>/teacher/examShow/<?= $ex['id']; ?>" class="fw-bold text-dark text-decoration-none">
                                                    <?= htmlspecialchars($ex['title']); ?>
                                                </a>
                                                <div class="d-flex align-items-center gap-1 mt-1">
                                                    <span class="badge bg-light text-dark border font-monospace text-xs" title="Token Akses Ujian">
                                                        <i class="bi bi-key me-1"></i><?= htmlspecialchars($ex['token'] ?? '-'); ?>
                                                    </span>
                                                    <?php if ($ex['is_random_question']): ?>
                                                        <span class="badge bg-secondary-subtle text-secondary text-xs" title="Soal Diacak"><i class="bi bi-shuffle"></i> Soal</span>
                                                    <?php endif; ?>
                                                    <?php if ($ex['is_random_option']): ?>
                                                        <span class="badge bg-secondary-subtle text-secondary text-xs" title="Opsi Jawaban Diacak"><i class="bi bi-shuffle"></i> Opsi</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($ex['subject_name']); ?></span>
                                            <div class="text-xs text-muted mt-1"><?= htmlspecialchars($ex['subject_code']); ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= htmlspecialchars($ex['class_name']); ?></span>
                                            <div class="text-xs text-muted mt-1">Tingkat <?= htmlspecialchars($ex['class_level'] ?? '-'); ?></div>
                                        </td>
                                        <td class="text-center">
                                            <div class="text-xs fw-semibold text-dark"><?= $ex['duration_minutes']; ?> Menit</div>
                                            <div class="text-xs text-muted mt-1">
                                                <?= date('d M Y H:i', strtotime($ex['start_time'])); ?><br>
                                                s/d <?= date('d M Y H:i', strtotime($ex['end_time'])); ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info-subtle text-info border border-info-subtle d-block mb-1">
                                                <i class="bi bi-list-check me-1"></i><?= $ex['total_questions']; ?> Soal
                                            </span>
                                            <span class="badge bg-light text-secondary border">
                                                <i class="bi bi-people me-1"></i><?= $ex['total_participants']; ?> Siswa
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            $stClass = 'bg-secondary';
                                            if ($ex['status'] === 'published') $stClass = 'bg-info text-dark';
                                            elseif ($ex['status'] === 'running') $stClass = 'bg-success';
                                            elseif ($ex['status'] === 'finished') $stClass = 'bg-dark';
                                            ?>
                                            <div class="d-flex flex-column align-items-center gap-1">
                                                <span class="badge <?= $stClass; ?> text-capitalize px-2 py-1">
                                                    <?= $ex['status']; ?>
                                                </span>
                                                <?php if (in_array($ex['status'], ['draft', 'published'])): ?>
                                                    <a href="<?= BASE_URL; ?>/teacher/examTogglePublish/<?= $ex['id']; ?>" class="btn btn-link btn-sm p-0 text-xs text-decoration-none" title="Klik untuk ubah status publish">
                                                        <?= ($ex['status'] === 'published') ? '<i class="bi bi-arrow-counterclockwise"></i> Jadikan Draft' : '<i class="bi bi-send-check"></i> Publish'; ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= BASE_URL; ?>/teacher/examShow/<?= $ex['id']; ?>" class="btn btn-outline-info" title="Preview Ujian">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="<?= BASE_URL; ?>/teacher/examEdit/<?= $ex['id']; ?>" class="btn btn-outline-warning" title="Edit Ujian">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger" onclick="confirmDeleteExam(<?= $ex['id']; ?>, '<?= htmlspecialchars(addslashes($ex['title'])); ?>')" title="Hapus Ujian">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
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
                    <strong class="text-dark" id="deleteExamTitle"></strong>
                </div>
                <div class="text-xs text-danger mt-2">
                    <i class="bi bi-info-circle me-1"></i> Data nilai, log proctoring, dan lembar jawaban peserta yang terkait akan ikut dihapus. Tindakan ini tidak dapat dibatalkan.
                </div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                <a href="#" id="btnConfirmDeleteExamLink" class="btn btn-danger btn-sm px-3">
                    <i class="bi bi-trash me-1"></i> Ya, Hapus Ujian
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDeleteExam(id, title) {
    document.getElementById('deleteExamTitle').textContent = title;
    document.getElementById('btnConfirmDeleteExamLink').href = '<?= BASE_URL; ?>/teacher/examDelete/' + id;
    new bootstrap.Modal(document.getElementById('deleteExamModal')).show();
}
</script>
