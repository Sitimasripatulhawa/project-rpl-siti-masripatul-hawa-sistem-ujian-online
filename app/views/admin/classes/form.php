<?php
require_once __DIR__ . '/../../layouts/header.php';
$c = $class ?? [
    'id' => '',
    'name' => '',
    'level' => 'XII',
    'academic_year' => '2025/2026'
];
$actionUrl = $is_edit ? BASE_URL . '/admin/classEdit/' . $c['id'] : BASE_URL . '/admin/classCreate';
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1"><?= $is_edit ? 'Edit Data Kelas' : 'Tambah Kelas Baru'; ?></h1>
                    <p class="text-muted mb-0 text-sm"><?= $is_edit ? 'Perbarui identitas rombongan belajar.' : 'Tambahkan rombongan belajar baru untuk tahun ajaran aktif.'; ?></p>
                </div>
                <a href="<?= BASE_URL; ?>/admin/classes" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
                </a>
            </div>

            <!-- Form Card -->
            <div class="row">
                <div class="col-12 col-lg-7">
                    <div class="custom-card p-4">
                        <form action="<?= $actionUrl; ?>" method="POST">
                            <?= Csrf::tokenField(); ?>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="nameInput" class="form-label text-sm fw-semibold required">Nama Rombel / Kelas</label>
                                    <input type="text" name="name" class="form-control text-sm" id="nameInput" value="<?= htmlspecialchars($c['name']); ?>" placeholder="Contoh: XII MIPA 1 atau X RPL 2" required>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="levelInput" class="form-label text-sm fw-semibold">Tingkatan Kelas <span class="text-danger">*</span></label>
                                    <select name="level" class="form-select text-sm" id="levelInput" required>
                                        <option value="X" <?= $c['level'] === 'X' ? 'selected' : ''; ?>>Kelas X (Sepuluh)</option>
                                        <option value="XI" <?= $c['level'] === 'XI' ? 'selected' : ''; ?>>Kelas XI (Sebelas)</option>
                                        <option value="XII" <?= $c['level'] === 'XII' ? 'selected' : ''; ?>>Kelas XII (Dua Belas)</option>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="ayInput" class="form-label text-sm fw-semibold">Tahun Ajaran / Akademik <span class="text-danger">*</span></label>
                                    <input type="text" name="academic_year" class="form-control text-sm" id="ayInput" value="<?= htmlspecialchars($c['academic_year']); ?>" placeholder="Contoh: 2025/2026" required>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?= BASE_URL; ?>/admin/classes" class="btn btn-outline-secondary btn-sm px-3">Batal</a>
                                <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">
                                    <i class="bi bi-save me-1"></i> <?= $is_edit ? 'Simpan Perubahan' : 'Simpan Kelas'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-12 col-lg-5">
                    <div class="custom-card p-4">
                        <h2 class="h6 fw-bold text-dark mb-3"><i class="bi bi-info-circle text-primary me-1"></i> Informasi Kelas</h2>
                        <p class="text-xs text-muted mb-2">
                            Kelas digunakan untuk mengelompokkan siswa agar memudahkan guru saat menjadwalkan dan membagikan sesi ujian online.
                        </p>
                    </div>
                </div>
            </div>
        </main>

        <?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
    </div>
</div>
