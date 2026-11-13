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
                    <h1 class="h3 fw-bold text-dark mb-1">Manajemen Kelas / Rombel</h1>
                    <p class="text-muted mb-0 text-sm">Kelola rombongan belajar, tingkat kelas, dan pembagian tahun akademik.</p>
                </div>
                <div>
                    <a href="<?= BASE_URL; ?>/admin/classCreate" class="btn btn-primary btn-sm px-3 shadow-sm">
                        <i class="bi bi-plus-circle-fill me-1"></i> Tambah Kelas Baru
                    </a>
                </div>
            </div>

            <!-- Search Card -->
            <div class="custom-card p-3 mb-4">
                <form action="<?= BASE_URL; ?>/admin/classes" method="GET" class="row g-2 align-items-center">
                    <div class="col-12 col-md-8 col-lg-6">
                        <div class="input-group">
                            <span class="input-group-text bg-light text-secondary border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="q" class="form-control border-start-0 text-sm" placeholder="Cari nama kelas, tingkat, tahun ajaran..." value="<?= htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm px-3 w-100">
                            <i class="bi bi-search me-1"></i> Cari
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="<?= BASE_URL; ?>/admin/classes" class="btn btn-outline-secondary btn-sm" title="Reset">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Table Card -->
            <div class="custom-card">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h2 class="h6 fw-bold text-dark mb-0">Daftar Rombongan Belajar</h2>
                    <span class="badge bg-light text-dark border text-xs">Total: <?= count($classes); ?> Kelas</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-sm">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;" class="text-center">No</th>
                                <th>Nama Rombel / Kelas</th>
                                <th>Tingkat (Level)</th>
                                <th>Tahun Ajaran</th>
                                <th class="text-center">Jumlah Siswa</th>
                                <th style="width: 140px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($classes)): ?>
                                <tr>
                                    <td colspan="6" class="p-0">
                                        <div class="empty-state border-0 my-3">
                                            <div class="empty-state-icon">
                                                <i class="bi bi-door-open"></i>
                                            </div>
                                            <div class="empty-state-title">Tidak Ada Data Kelas</div>
                                            <div class="empty-state-text">
                                                Belum ada rombongan belajar atau kelas yang terdaftar dalam sistem.
                                            </div>
                                            <a href="<?= BASE_URL; ?>/admin/classCreate" class="btn btn-primary btn-sm px-3">
                                                <i class="bi bi-plus-circle-fill me-1"></i> Tambah Kelas Baru
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($classes as $i => $c): ?>
                                    <tr>
                                        <td class="text-center text-muted"><?= $i + 1; ?></td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($c['name']); ?></div>
                                            <div class="text-xs text-muted">ID: #<?= $c['id']; ?></div>
                                        </td>
                                        <td><span class="badge bg-light text-dark border">Tingkat <?= htmlspecialchars($c['level']); ?></span></td>
                                        <td><?= htmlspecialchars($c['academic_year']); ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                <?= $c['total_students']; ?> Peserta
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="<?= BASE_URL; ?>/admin/classEdit/<?= $c['id']; ?>" class="btn btn-outline-primary" title="Edit Kelas">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger" title="Hapus Kelas" onclick="confirmDelete(<?= $c['id']; ?>, '<?= htmlspecialchars(addslashes($c['name'])); ?>', <?= $c['total_students']; ?>)">
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
                <p class="mb-0 text-sm">Apakah Anda yakin ingin menghapus kelas <strong id="deleteClassName"></strong>? Siswa yang terdaftar pada kelas ini akan dilepas dari penugasan kelas.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <a href="#" id="btnConfirmDeleteLink" class="btn btn-danger btn-sm">Hapus Kelas</a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name, studentCount) {
    document.getElementById('deleteClassName').textContent = name;
    document.getElementById('btnConfirmDeleteLink').href = '<?= BASE_URL; ?>/admin/classDelete/' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
