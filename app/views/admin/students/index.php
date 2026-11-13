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
                    <h1 class="h3 fw-bold text-dark mb-1">Data Peserta Didik</h1>
                    <p class="text-muted mb-0 text-sm">Kelola data siswa, nomor induk (NIS/NISN), pembagian kelas, dan akun pengerjaan ujian.</p>
                </div>
                <div>
                    <a href="<?= BASE_URL; ?>/admin/studentCreate" class="btn btn-primary btn-sm px-3 shadow-sm">
                        <i class="bi bi-person-plus-fill me-1"></i> Tambah Peserta Baru
                    </a>
                </div>
            </div>

            <!-- Filter & Search Card -->
            <div class="custom-card p-3 mb-4">
                <form action="<?= BASE_URL; ?>/admin/students" method="GET" class="row g-2 align-items-center">
                    <div class="col-12 col-md-6 col-lg-5">
                        <div class="input-group">
                            <span class="input-group-text bg-light text-secondary border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="q" class="form-control border-start-0 text-sm" placeholder="Cari nama siswa, NIS, NISN, email..." value="<?= htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                        <select name="class_id" class="form-select text-sm">
                            <option value="">-- Semua Kelas --</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['id']; ?>" <?= $class_filter == $c['id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($c['name']); ?> (<?= htmlspecialchars($c['academic_year']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3 col-lg-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm px-3 w-100">
                            <i class="bi bi-filter me-1"></i> Filter
                        </button>
                        <?php if (!empty($search) || !empty($class_filter)): ?>
                            <a href="<?= BASE_URL; ?>/admin/students" class="btn btn-outline-secondary btn-sm" title="Reset Filter">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Table Card -->
            <div class="custom-card">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h2 class="h6 fw-bold text-dark mb-0">Daftar Peserta Didik</h2>
                    <span class="badge bg-light text-dark border text-xs">Total: <?= count($students); ?> Siswa</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-sm">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;" class="text-center">No</th>
                                <th>Nama Siswa & NIS</th>
                                <th>NISN</th>
                                <th>Kelas</th>
                                <th>L/P</th>
                                <th>Akun Email</th>
                                <th>Status</th>
                                <th style="width: 140px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="8" class="p-0">
                                        <div class="empty-state border-0 my-3">
                                            <div class="empty-state-icon">
                                                <i class="bi bi-mortarboard"></i>
                                            </div>
                                            <div class="empty-state-title">Tidak Ada Peserta Didik</div>
                                            <div class="empty-state-text">
                                                Belum ada data peserta didik yang cocok dengan kriteria pencarian atau kelas yang dipilih.
                                            </div>
                                            <a href="<?= BASE_URL; ?>/admin/studentCreate" class="btn btn-primary btn-sm px-3">
                                                <i class="bi bi-person-plus-fill me-1"></i> Tambah Peserta Baru
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $i => $s): ?>
                                    <tr>
                                        <td class="text-center text-muted"><?= $i + 1; ?></td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($s['full_name']); ?></div>
                                            <div class="text-xs text-muted">NIS: <?= htmlspecialchars($s['nis']); ?></div>
                                        </td>
                                        <td><?= !empty($s['nisn']) ? htmlspecialchars($s['nisn']) : '<span class="text-muted">-</span>'; ?></td>
                                        <td>
                                            <?php if (!empty($s['class_name'])): ?>
                                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($s['class_name']); ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary">Belum ada kelas</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $s['gender'] === 'L' ? 'bg-info-subtle text-info' : 'bg-danger-subtle text-danger'; ?>">
                                                <?= $s['gender'] === 'L' ? 'Laki-laki' : 'Perempuan'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-dark"><?= htmlspecialchars($s['email']); ?></div>
                                            <div class="text-xs text-muted"><?= htmlspecialchars($s['username']); ?></div>
                                        </td>
                                        <td>
                                            <?php if ($s['is_active']): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="<?= BASE_URL; ?>/admin/studentShow/<?= $s['id']; ?>" class="btn btn-outline-info" title="Lihat Detail">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="<?= BASE_URL; ?>/admin/studentEdit/<?= $s['id']; ?>" class="btn btn-outline-primary" title="Edit Data">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger" title="Hapus Siswa" onclick="confirmDelete(<?= $s['id']; ?>, '<?= htmlspecialchars(addslashes($s['full_name'])); ?>')">
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
                <p class="mb-0 text-sm">Apakah Anda yakin ingin menghapus data peserta <strong id="deleteStudentName"></strong> beserta akun pengguna terkait?</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <a href="#" id="btnConfirmDeleteLink" class="btn btn-danger btn-sm">Hapus Peserta</a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('deleteStudentName').textContent = name;
    document.getElementById('btnConfirmDeleteLink').href = '<?= BASE_URL; ?>/admin/studentDelete/' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
