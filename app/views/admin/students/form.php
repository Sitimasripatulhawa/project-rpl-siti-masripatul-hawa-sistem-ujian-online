<?php
require_once __DIR__ . '/../../layouts/header.php';
$s = $student ?? [
    'id' => '',
    'nis' => '',
    'nisn' => '',
    'class_id' => '',
    'gender' => 'L',
    'full_name' => '',
    'username' => '',
    'email' => '',
    'phone' => '',
    'is_active' => 1
];
$actionUrl = $is_edit ? BASE_URL . '/admin/studentEdit/' . $s['id'] : BASE_URL . '/admin/studentCreate';
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
                    <h1 class="h3 fw-bold text-dark mb-1"><?= $is_edit ? 'Edit Data Peserta Didik' : 'Tambah Peserta Didik Baru'; ?></h1>
                    <p class="text-muted mb-0 text-sm"><?= $is_edit ? 'Perbarui nomor induk, kelas, dan akun pengerjaan ujian siswa.' : 'Daftarkan peserta didik baru ke dalam rombel & sistem ujian.'; ?></p>
                </div>
                <a href="<?= BASE_URL; ?>/admin/students" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
                </a>
            </div>

            <!-- Form Card -->
            <div class="row">
                <div class="col-12 col-lg-8">
                    <div class="custom-card p-4">
                        <form action="<?= $actionUrl; ?>" method="POST">
                            <?= Csrf::tokenField(); ?>
                            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-mortarboard me-1"></i> 1. Identitas Peserta Didik</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label for="nisInput" class="form-label text-sm fw-semibold required">Nomor Induk Siswa (NIS)</label>
                                    <input type="text" name="nis" class="form-control text-sm" id="nisInput" value="<?= htmlspecialchars($s['nis']); ?>" placeholder="Contoh: 20261001" required>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="nisnInput" class="form-label text-sm fw-semibold">NISN (Nasional)</label>
                                    <input type="text" name="nisn" class="form-control text-sm" id="nisnInput" value="<?= htmlspecialchars($s['nisn'] ?? ''); ?>" placeholder="Contoh: 0061234561">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="fullNameInput" class="form-label text-sm fw-semibold">Nama Lengkap Siswa <span class="text-danger">*</span></label>
                                    <input type="text" name="full_name" class="form-control text-sm" id="fullNameInput" value="<?= htmlspecialchars($s['full_name']); ?>" placeholder="Contoh: Ani Lestari" required>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="classInput" class="form-label text-sm fw-semibold">Rombongan Belajar / Kelas</label>
                                    <select name="class_id" class="form-select text-sm" id="classInput">
                                        <option value="">-- Pilih Kelas --</option>
                                        <?php foreach ($classes as $c): ?>
                                            <option value="<?= $c['id']; ?>" <?= $s['class_id'] == $c['id'] ? 'selected' : ''; ?>>
                                                <?= htmlspecialchars($c['name']); ?> (<?= htmlspecialchars($c['academic_year']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label text-sm fw-semibold d-block">Jenis Kelamin</label>
                                    <div class="d-flex gap-4 mt-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="gender" id="genderL" value="L" <?= $s['gender'] === 'L' ? 'checked' : ''; ?>>
                                            <label class="form-check-label text-sm" for="genderL">Laki-laki</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="gender" id="genderP" value="P" <?= $s['gender'] === 'P' ? 'checked' : ''; ?>>
                                            <label class="form-check-label text-sm" for="genderP">Perempuan</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="phoneInput" class="form-label text-sm fw-semibold">No. Telepon / WhatsApp</label>
                                    <input type="text" name="phone" class="form-control text-sm" id="phoneInput" value="<?= htmlspecialchars($s['phone'] ?? ''); ?>" placeholder="Contoh: 081234567890">
                                </div>
                            </div>

                            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-key me-1"></i> 2. Akun Ujian Online</h6>
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label for="usernameInput" class="form-label text-sm fw-semibold">Username <span class="text-danger">*</span></label>
                                    <input type="text" name="username" class="form-control text-sm" id="usernameInput" value="<?= htmlspecialchars($s['username']); ?>" placeholder="Contoh: siswa_ani" required>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="emailInput" class="form-label text-sm fw-semibold">Alamat Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control text-sm" id="emailInput" value="<?= htmlspecialchars($s['email']); ?>" placeholder="Contoh: ani.lestari@sekolah.sch.id" required>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="passwordInput" class="form-label text-sm fw-semibold">
                                        Kata Sandi <?= $is_edit ? '<span class="text-muted fw-normal text-xs">(Kosongkan jika tidak diganti)</span>' : '<span class="text-danger">*</span>'; ?>
                                    </label>
                                    <input type="password" name="password" class="form-control text-sm" id="passwordInput" placeholder="<?= $is_edit ? '••••••••' : 'Minimal 6 karakter'; ?>" <?= $is_edit ? '' : 'required'; ?>>
                                </div>

                                <div class="col-12 col-md-6 d-flex align-items-end">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="isActiveSwitch" value="1" <?= $s['is_active'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label text-sm fw-semibold" for="isActiveSwitch">Akun Aktif (Dapat Mengikuti Ujian)</label>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?= BASE_URL; ?>/admin/students" class="btn btn-outline-secondary btn-sm px-3">Batal</a>
                                <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">
                                    <i class="bi bi-save me-1"></i> <?= $is_edit ? 'Simpan Perubahan' : 'Simpan Peserta'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="custom-card p-4">
                        <h2 class="h6 fw-bold text-dark mb-3"><i class="bi bi-info-circle text-primary me-1"></i> Informasi</h2>
                        <p class="text-xs text-muted mb-2">
                            Pendaftaran peserta didik otomatis membuat akun pengguna dengan peran <strong>Peserta Didik (Student)</strong>.
                        </p>
                        <p class="text-xs text-muted mb-0">
                            Peserta yang terdaftar pada suatu kelas dapat ditugaskan untuk mengerjakan sesi ujian yang dibuat oleh guru.
                        </p>
                    </div>
                </div>
            </div>
        </main>

        <?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
    </div>
</div>
