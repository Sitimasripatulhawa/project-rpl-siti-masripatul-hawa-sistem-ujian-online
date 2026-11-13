<?php
require_once __DIR__ . '/../../layouts/header.php';
$u = $user_detail;
?>

<div class="app-wrapper">
    <?php require_once __DIR__ . '/../../layouts/sidebar.php'; ?>

    <div class="app-main">
        <?php require_once __DIR__ . '/../../layouts/navbar.php'; ?>

        <main class="app-content">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1">Detail Pengguna</h1>
                    <p class="text-muted mb-0 text-sm">Informasi lengkap akun ID #<?= $u['id']; ?>.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL; ?>/admin/users" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                    <a href="<?= BASE_URL; ?>/admin/userEdit/<?= $u['id']; ?>" class="btn btn-primary btn-sm px-3 shadow-sm">
                        <i class="bi bi-pencil me-1"></i> Edit Pengguna
                    </a>
                </div>
            </div>

            <!-- Detail Card -->
            <div class="row">
                <div class="col-12 col-lg-8">
                    <div class="custom-card p-4">
                        <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-4 shadow-sm" style="width: 64px; height: 64px;">
                                <?= strtoupper(substr($u['full_name'], 0, 2)); ?>
                            </div>
                            <div>
                                <h2 class="h5 fw-bold text-dark mb-1"><?= htmlspecialchars($u['full_name']); ?></h2>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="badge badge-role-admin">Super Admin</span>
                                    <?php elseif ($u['role'] === 'teacher'): ?>
                                        <span class="badge badge-role-guru">Guru / Penguji</span>
                                    <?php else: ?>
                                        <span class="badge badge-role-peserta">Peserta Didik</span>
                                    <?php endif; ?>

                                    <?php if ($u['is_active']): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Nonaktif</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 text-sm">
                            <div class="col-12 col-md-6">
                                <label class="text-muted text-xs fw-semibold text-uppercase d-block mb-1">Username</label>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($u['username']); ?></div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="text-muted text-xs fw-semibold text-uppercase d-block mb-1">Alamat Email</label>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($u['email']); ?></div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="text-muted text-xs fw-semibold text-uppercase d-block mb-1">Nomor Telepon</label>
                                <div class="fw-semibold text-dark"><?= !empty($u['phone']) ? htmlspecialchars($u['phone']) : '-'; ?></div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="text-muted text-xs fw-semibold text-uppercase d-block mb-1">Tanggal Pendaftaran</label>
                                <div class="fw-semibold text-dark"><?= date('d F Y, H:i', strtotime($u['created_at'])); ?> WIB</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="text-muted text-xs fw-semibold text-uppercase d-block mb-1">Pembaruan Terakhir</label>
                                <div class="fw-semibold text-dark"><?= !empty($u['updated_at']) ? date('d F Y, H:i', strtotime($u['updated_at'])) . ' WIB' : '-'; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
    </div>
</div>
