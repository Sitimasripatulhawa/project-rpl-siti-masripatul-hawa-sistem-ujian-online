<?php
require_once __DIR__ . '/../../layouts/header.php';
$t = $teacher ?? [
    'id' => '',
    'nip' => '',
    'specialization' => '',
    'full_name' => '',
    'username' => '',
    'email' => '',
    'phone' => '',
    'is_active' => 1
];
$actionUrl = $is_edit ? BASE_URL . '/admin/teacherEdit/' . $t['id'] : BASE_URL . '/admin/teacherCreate';
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
                    <h1 class="h3 fw-bold text-dark mb-1"><?= $is_edit ? 'Edit Data Guru' : 'Tambah Guru Baru'; ?></h1>
                    <p class="text-muted mb-0 text-sm"><?= $is_edit ? 'Perbarui informasi profil penguji dan akun pengguna.' : 'Daftarkan tenaga penguji baru ke dalam sistem.'; ?></p>
                </div>
                <a href="<?= BASE_URL; ?>/admin/teachers" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
                </a>
            </div>

            <!-- Form Card -->
            <div class="row">
                <div class="col-12 col-lg-8">
                    <div class="custom-card p-4">
                        <form action="<?= $actionUrl; ?>" method="POST">
                            <?= Csrf::tokenField(); ?>
                            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-person-badge me-1"></i> 1. Profil Tenaga Pendidik</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label for="nipInput" class="form-label text-sm fw-semibold required">Nomor Induk Pegawai (NIP)</label>
                                    <input type="text" name="nip" class="form-control text-sm" id="nipInput" value="<?= htmlspecialchars($t['nip']); ?>" placeholder="Contoh: 198503152010011005" required>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="fullNameInput" class="form-label text-sm fw-semibold required">Nama Lengkap & Gelar</label>
                                    <input type="text" name="full_name" class="form-control text-sm" id="fullNameInput" value="<?= htmlspecialchars($t['full_name']); ?>" placeholder="Contoh: Dr. Budi Santoso, M.Pd" required>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="specInput" class="form-label text-sm fw-semibold">Bidang Spesialisasi / Mapel Utama</label>
                                    <input type="text" name="specialization" class="form-control text-sm" id="specInput" value="<?= htmlspecialchars($t['specialization'] ?? ''); ?>" placeholder="Contoh: Matematika Peminatan & Terapan">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="phoneInput" class="form-label text-sm fw-semibold">Nomor HP / WhatsApp</label>
                                    <input type="text" name="phone" class="form-control text-sm" id="phoneInput" value="<?= htmlspecialchars($t['phone'] ?? ''); ?>" placeholder="Contoh: 081234567890">
                                </div>
                            </div>

                            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-key me-1"></i> 2. Kredensial Akun Login</h6>
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label for="usernameInput" class="form-label text-sm fw-semibold">Username Login <span class="text-danger">*</span></label>
                                    <input type="text" name="username" class="form-control text-sm" id="usernameInput" value="<?= htmlspecialchars($t['username']); ?>" placeholder="Contoh: guru_budi" required>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="emailInput" class="form-label text-sm fw-semibold">Alamat Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control text-sm" id="emailInput" value="<?= htmlspecialchars($t['email']); ?>" placeholder="Contoh: budi.santoso@sekolah.sch.id" required>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="passwordInput" class="form-label text-sm fw-semibold">
                                        Kata Sandi <?= $is_edit ? '<span class="text-muted fw-normal text-xs">(Kosongkan jika tidak diganti)</span>' : '<span class="text-danger">*</span>'; ?>
                                    </label>
                                    <input type="password" name="password" class="form-control text-sm" id="passwordInput" placeholder="<?= $is_edit ? '••••••••' : 'Minimal 6 karakter'; ?>" <?= $is_edit ? '' : 'required'; ?>>
                                </div>

                                <div class="col-12 col-md-6 d-flex align-items-end">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="isActiveSwitch" value="1" <?= $t['is_active'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label text-sm fw-semibold" for="isActiveSwitch">Status Akun Aktif</label>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?= BASE_URL; ?>/admin/teachers" class="btn btn-outline-secondary btn-sm px-3">Batal</a>
                                <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">
                                    <i class="bi bi-save me-1"></i> <?= $is_edit ? 'Simpan Perubahan' : 'Simpan Data Guru'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="custom-card p-4">
                        <h2 class="h6 fw-bold text-dark mb-3"><i class="bi bi-info-circle text-primary me-1"></i> Informasi</h2>
                        <p class="text-xs text-muted mb-2">
                            Pendaftaran guru akan secara otomatis membuat akun login dengan role <strong>Guru (Teacher)</strong>.
                        </p>
                        <p class="text-xs text-muted mb-0">
                            Guru yang aktif dapat masuk ke Panel Guru untuk mengelola bank soal dan membuat jadwal ujian.
                        </p>
                    </div>
                </div>
            </div>
        </main>

        <?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
    </div>
</div>
