<?php
require_once __DIR__ . '/../../layouts/header.php';
?>

<div class="app-wrapper">
    <?php require_once __DIR__ . '/../../layouts/sidebar.php'; ?>

    <div class="app-main">
        <?php require_once __DIR__ . '/../../layouts/navbar.php'; ?>

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
                    <h1 class="h3 fw-bold text-dark mb-1">Bank Soal Saya</h1>
                    <p class="text-muted mb-0 text-sm">Kelola butir soal Pilihan Ganda dan Essay untuk bahan penyusunan ujian sekolah.</p>
                </div>
                <div>
                    <a href="<?= BASE_URL; ?>/teacher/questionCreate" class="btn btn-primary btn-sm px-3 shadow-sm">
                        <i class="bi bi-plus-circle-fill me-1"></i> Tambah Butir Soal Baru
                    </a>
                </div>
            </div>

            <!-- Filter & Search Card -->
            <div class="custom-card p-3 mb-4">
                <form action="<?= BASE_URL; ?>/teacher/questions" method="GET" class="row g-2 align-items-center">
                    <div class="col-12 col-md-5 col-lg-4">
                        <div class="input-group">
                            <span class="input-group-text bg-light text-secondary border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="q" class="form-control border-start-0 text-sm" placeholder="Cari teks soal atau pembahasan..." value="<?= htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                        <select name="subject_id" class="form-select text-sm">
                            <option value="">-- Semua Mata Pelajaran --</option>
                            <?php foreach ($subjects as $sub): ?>
                                <option value="<?= $sub['id']; ?>" <?= $subject_filter == $sub['id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($sub['name']); ?> (<?= htmlspecialchars($sub['code']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-sm-3 col-md-2 col-lg-2">
                        <select name="type" class="form-select text-sm">
                            <option value="">-- Tipe Soal --</option>
                            <option value="multiple_choice" <?= $type_filter === 'multiple_choice' ? 'selected' : ''; ?>>Pilihan Ganda</option>
                            <option value="essay" <?= $type_filter === 'essay' ? 'selected' : ''; ?>>Essay / Uraian</option>
                        </select>
                    </div>
                    <div class="col-6 col-sm-3 col-md-2 col-lg-2">
                        <select name="difficulty" class="form-select text-sm">
                            <option value="">-- Kesulitan --</option>
                            <option value="easy" <?= $difficulty_filter === 'easy' ? 'selected' : ''; ?>>Mudah</option>
                            <option value="medium" <?= $difficulty_filter === 'medium' ? 'selected' : ''; ?>>Sedang</option>
                            <option value="hard" <?= $difficulty_filter === 'hard' ? 'selected' : ''; ?>>Sukar</option>
                        </select>
                    </div>
                    <div class="col-12 col-lg-1 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm px-3 w-100" title="Terapkan Filter">
                            <i class="bi bi-filter"></i>
                        </button>
                        <?php if (!empty($search) || !empty($subject_filter) || !empty($type_filter) || !empty($difficulty_filter)): ?>
                            <a href="<?= BASE_URL; ?>/teacher/questions" class="btn btn-outline-secondary btn-sm" title="Reset Filter">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Questions Table Card -->
            <div class="custom-card">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h2 class="h6 fw-bold text-dark mb-0">Daftar Butir Soal</h2>
                    <span class="badge bg-light text-dark border text-xs">Total: <?= count($questions); ?> Soal</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-sm">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;" class="text-center">No</th>
                                <th style="width: 150px;">Mata Pelajaran</th>
                                <th>Teks Pertanyaan</th>
                                <th style="width: 120px;" class="text-center">Tipe Soal</th>
                                <th style="width: 100px;" class="text-center">Tingkat</th>
                                <th style="width: 80px;" class="text-center">Bobot</th>
                                <th style="width: 140px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($questions)): ?>
                                <tr>
                                    <td colspan="7" class="p-0">
                                        <div class="empty-state border-0 my-3">
                                            <div class="empty-state-icon">
                                                <i class="bi bi-collection"></i>
                                            </div>
                                            <div class="empty-state-title">Bank Soal Kosong</div>
                                            <div class="empty-state-text">
                                                Belum ada butir soal yang sesuai dengan kriteria filter atau mata pelajaran yang dipilih.
                                            </div>
                                            <a href="<?= BASE_URL; ?>/teacher/questionCreate" class="btn btn-primary btn-sm px-3">
                                                <i class="bi bi-plus-circle me-1"></i> Buat Butir Soal Baru
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($questions as $i => $q): ?>
                                    <tr>
                                        <td class="text-center text-muted"><?= $i + 1; ?></td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($q['subject_name']); ?></div>
                                            <span class="badge bg-light text-muted border text-xs"><?= htmlspecialchars($q['subject_code']); ?></span>
                                        </td>
                                        <td>
                                            <div class="text-dark fw-medium text-truncate" style="max-width: 380px;">
                                                <?= htmlspecialchars($q['question_text']); ?>
                                            </div>
                                            <?php if (!empty($q['explanation'])): ?>
                                                <div class="text-xs text-muted text-truncate" style="max-width: 380px;">
                                                    <i class="bi bi-lightbulb me-1 text-warning"></i><?= htmlspecialchars($q['explanation']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($q['question_type'] === 'multiple_choice'): ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                    PG (<?= $q['total_options']; ?> Opsi)
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-info-subtle text-info border border-info-subtle">Essay</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($q['difficulty'] === 'easy'): ?>
                                                <span class="badge bg-success-subtle text-success">Mudah</span>
                                            <?php elseif ($q['difficulty'] === 'medium'): ?>
                                                <span class="badge bg-warning-subtle text-warning">Sedang</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger">Sukar</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center fw-semibold text-dark">
                                            <?= number_format((float)$q['default_score'], 0); ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="<?= BASE_URL; ?>/teacher/questionShow/<?= $q['id']; ?>" class="btn btn-outline-info" title="Lihat Detail">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="<?= BASE_URL; ?>/teacher/questionEdit/<?= $q['id']; ?>" class="btn btn-outline-primary" title="Edit Soal">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger" title="Hapus Soal" onclick="confirmDelete(<?= $q['id']; ?>)">
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

        <?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger" id="deleteModalLabel"><i class="bi bi-exclamation-triangle-fill me-2"></i> Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <p class="mb-0 text-sm">Apakah Anda yakin ingin menghapus butir soal ini dari Bank Soal? Opsi pilihan jawaban terkait akan terhapus otomatis.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <a href="#" id="btnConfirmDeleteLink" class="btn btn-danger btn-sm">Hapus Soal</a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    document.getElementById('btnConfirmDeleteLink').href = '<?= BASE_URL; ?>/teacher/questionDelete/' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
