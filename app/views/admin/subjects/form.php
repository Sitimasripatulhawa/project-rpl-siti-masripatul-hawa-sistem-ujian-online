<?php
require_once __DIR__ . '/../../layouts/header.php';
$sub = $subject ?? [
    'id' => '',
    'code' => '',
    'name' => '',
    'description' => ''
];
$actionUrl = $is_edit ? BASE_URL . '/admin/subjectEdit/' . $sub['id'] : BASE_URL . '/admin/subjectCreate';
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
                    <h1 class="h3 fw-bold text-dark mb-1"><?= $is_edit ? 'Edit Mata Pelajaran' : 'Tambah Mata Pelajaran Baru'; ?></h1>
                    <p class="text-muted mb-0 text-sm"><?= $is_edit ? 'Perbarui kode dan nama mata pelajaran kurikulum.' : 'Daftarkan mata pelajaran kurikulum baru.'; ?></p>
                </div>
                <a href="<?= BASE_URL; ?>/admin/subjects" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
                </a>
            </div>

            <!-- Form Card -->
            <div class="row">
                <div class="col-12 col-lg-8">
                    <div class="custom-card p-4">
                        <form action="<?= $actionUrl; ?>" method="POST">
                            <?= Csrf::tokenField(); ?>
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label for="codeInput" class="form-label text-sm fw-semibold required">Kode Mapel</label>
                                    <input type="text" name="code" class="form-control text-sm text-uppercase" id="codeInput" value="<?= htmlspecialchars($sub['code']); ?>" placeholder="Contoh: MTK-XII-01" required>
                                </div>

                                <div class="col-12 col-md-8">
                                    <label for="nameInput" class="form-label text-sm fw-semibold">Nama Mata Pelajaran <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control text-sm" id="nameInput" value="<?= htmlspecialchars($sub['name']); ?>" placeholder="Contoh: Matematika Peminatan & Terapan" required>
                                </div>

                                <div class="col-12">
                                    <label for="descInput" class="form-label text-sm fw-semibold">Deskripsi / Silabus Singkat</label>
                                    <textarea name="description" class="form-control text-sm" id="descInput" rows="4" placeholder="Keterangan kurikulum, cakupan materi, atau silabus mata pelajaran..."><?= htmlspecialchars($sub['description'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?= BASE_URL; ?>/admin/subjects" class="btn btn-outline-secondary btn-sm px-3">Batal</a>
                                <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">
                                    <i class="bi bi-save me-1"></i> <?= $is_edit ? 'Simpan Perubahan' : 'Simpan Mapel'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="custom-card p-4">
                        <h2 class="h6 fw-bold text-dark mb-3"><i class="bi bi-info-circle text-primary me-1"></i> Informasi Mapel</h2>
                        <p class="text-xs text-muted mb-2">
                            <strong>Kode Mapel</strong> harus unik untuk mencegah kerancuan saat guru membuat bank butir soal dan jadwal ujian.
                        </p>
                    </div>
                </div>
            </div>
        </main>

        <?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
    </div>
</div>
