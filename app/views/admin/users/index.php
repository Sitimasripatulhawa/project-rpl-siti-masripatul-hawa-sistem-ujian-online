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
                    <h1 class="h3 fw-bold text-dark mb-1">Manajemen Pengguna (Users)</h1>
                    <p class="text-muted mb-0 text-sm">Kelola seluruh akun pengguna sistem: Admin, Guru, dan Peserta Didik.</p>
                </div>
                <div>
                    <a href="<?= BASE_URL; ?>/admin/userCreate" class="btn btn-primary btn-sm px-3 shadow-sm">
                        <i class="bi bi-person-plus-fill me-1"></i> Tambah Pengguna Baru
                    </a>
                </div>
            </div>

            <!-- Filter & Search Card -->
            <div class="custom-card p-3 mb-4">
                <form action="<?= BASE_URL; ?>/admin/users" method="GET" class="row g-2 align-items-center">
                    <div class="col-12 col-md-6 col-lg-5">
                        <div class="input-group">
                            <span class="input-group-text bg-light text-secondary border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="q" class="form-control border-start-0 text-sm" placeholder="Cari nama, email, username, telepon..." value="<?= htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                        <select name="role" class="form-select text-sm">
                            <option value="">-- Semua Role --</option>
                            <option value="admin" <?= $role_filter === 'admin' ? 'selected' : ''; ?>>Super Admin</option>
                            <option value="teacher" <?= $role_filter === 'teacher' ? 'selected' : ''; ?>>Guru (Teacher)</option>
                            <option value="student" <?= $role_filter === 'student' ? 'selected' : ''; ?>>Peserta (Student)</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3 col-lg-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm px-3 w-100">
                            <i class="bi bi-filter me-1"></i> Filter
                        </button>
                        <?php if (!empty($search) || !empty($role_filter)): ?>
                            <a href="<?= BASE_URL; ?>/admin/users" class="btn btn-outline-secondary btn-sm" title="Reset Filter">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Users Table Card -->
            <div class="custom-card">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h2 class="h6 fw-bold text-dark mb-0">Daftar Pengguna Terdaftar</h2>
                    <span class="badge bg-light text-dark border text-xs">Total: <?= count($users); ?> Pengguna</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-sm">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;" class="text-center">No</th>
                                <th>Nama Lengkap</th>
                                <th>Username / Email</th>
                                <th>Role</th>
                                <th>No. Telepon</th>
                                <th>Status</th>
                                <th style="width: 140px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7" class="p-0">
                                        <div class="empty-state border-0 my-3">
                                            <div class="empty-state-icon">
                                                <i class="bi bi-people"></i>
                                            </div>
                                            <div class="empty-state-title">Tidak Ada Pengguna Ditemukan</div>
                                            <div class="empty-state-text">
                                                Tidak ada data pengguna yang cocok dengan kata kunci atau filter pencarian Anda.
                                            </div>
                                            <a href="<?= BASE_URL; ?>/admin/userCreate" class="btn btn-primary btn-sm px-3">
                                                <i class="bi bi-person-plus-fill me-1"></i> Tambah Pengguna Baru
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $i => $u): ?>
                                    <tr>
                                        <td class="text-center text-muted"><?= $i + 1; ?></td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($u['full_name']); ?></div>
                                            <div class="text-xs text-muted">ID: #<?= $u['id']; ?> &bull; Dibuat: <?= date('d M Y', strtotime($u['created_at'])); ?></div>
                                        </td>
                                        <td>
                                            <div class="text-dark fw-medium"><?= htmlspecialchars($u['username']); ?></div>
                                            <div class="text-xs text-muted"><?= htmlspecialchars($u['email']); ?></div>
                                        </td>
                                        <td>
                                            <?php if ($u['role'] === 'admin'): ?>
                                                <span class="badge badge-role-admin">Admin</span>
                                            <?php elseif ($u['role'] === 'teacher'): ?>
                                                <span class="badge badge-role-guru">Guru</span>
                                            <?php else: ?>
                                                <span class="badge badge-role-peserta">Peserta</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= !empty($u['phone']) ? htmlspecialchars($u['phone']) : '<span class="text-muted">-</span>'; ?></td>
                                        <td>
                                            <?php if ($u['is_active']): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="<?= BASE_URL; ?>/admin/userShow/<?= $u['id']; ?>" class="btn btn-outline-info" title="Lihat Detail">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="<?= BASE_URL; ?>/admin/userEdit/<?= $u['id']; ?>" class="btn btn-outline-primary" title="Edit Data">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php if ($u['id'] !== $auth_user['id']): ?>
                                                    <button type="button" class="btn btn-outline-danger" title="Hapus Pengguna" onclick="confirmDelete(<?= $u['id']; ?>, '<?= htmlspecialchars(addslashes($u['full_name'])); ?>')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                <?php endif; ?>
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
                <p class="mb-0 text-sm">Apakah Anda yakin ingin menghapus akun pengguna <strong id="deleteUserName"></strong>? Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <a href="#" id="btnConfirmDeleteLink" class="btn btn-danger btn-sm">Hapus Pengguna</a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('deleteUserName').textContent = name;
    document.getElementById('btnConfirmDeleteLink').href = '<?= BASE_URL; ?>/admin/userDelete/' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
