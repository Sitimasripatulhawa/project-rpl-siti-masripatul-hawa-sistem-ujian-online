<?php
require_once __DIR__ . '/../../layouts/header.php';
$t = $teacher;
?>

<div class="app-wrapper">
    <?php require_once __DIR__ . '/../../layouts/sidebar.php'; ?>

    <div class="app-main">
        <?php require_once __DIR__ . '/../../layouts/navbar.php'; ?>

        <main class="app-content">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1">Detail Tenaga Pendidik</h1>
                    <p class="text-muted mb-0 text-sm">Informasi lengkap data guru & penguji.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL; ?>/admin/teachers" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                    <a href="<?= BASE_URL; ?>/admin/teacherEdit/<?= $t['id']; ?>" class="btn btn-primary btn-sm px-3 shadow-sm">
                        <i class="bi bi-pencil me-1"></i> Edit Guru
                    </a>
                </div>
            </div>

            <!-- Detail Card -->
            <div class="row">
                <div class="col-12 col-lg-8">
                    <div class="custom-card p-4">
                        <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-4 shadow-sm" style="width: 64px; height: 64px;">
                                <?= strtoupper(substr($t['full_name'], 0, 2)); ?>
                            </div>
                            <div>
                                <h2 class="h5 fw-bold text-dark mb-1"><?= htmlspecialchars($t['full_name']); ?></h2>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge badge-role-guru">NIP: <?= htmlspecialchars($t['nip']); ?></span>
                                    <?php if ($t['is_active']): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Nonaktif</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 text-sm">
                            <div class="col-12 col-md-6">
                                <label class="text-muted text-xs fw-semibold text-uppercase d-block mb-1">Bidang Spesialisasi</label>
                                <div class="fw-semibold text-dark"><?= !empty($t['specialization']) ? htmlspecialchars($t['specialization']) : '-'; ?></div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="text-muted text-xs fw-semibold text-uppercase d-block mb-1">Total Bank Soal Dibuat</label>
                                <div class="fw-semibold text-primary"><?= $t['total_questions']; ?> Butir Soal</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="text-muted text-xs fw-semibold text-uppercase d-block mb-1">Username Login</label>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($t['username']); ?></div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="text-muted text-xs fw-semibold text-uppercase d-block mb-1">Alamat Email</label>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($t['email']); ?></div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="text-muted text-xs fw-semibold text-uppercase d-block mb-1">Nomor Telepon</label>
                                <div class="fw-semibold text-dark"><?= !empty($t['phone']) ? htmlspecialchars($t['phone']) : '-'; ?></div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="text-muted text-xs fw-semibold text-uppercase d-block mb-1">Terdaftar Pada</label>
                                <div class="fw-semibold text-dark"><?= date('d F Y, H:i', strtotime($t['created_at'])); ?> WIB</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
    </div>
</div>
