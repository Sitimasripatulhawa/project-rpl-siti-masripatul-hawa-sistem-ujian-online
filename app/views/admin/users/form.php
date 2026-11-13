<?php
require_once __DIR__ . '/../../layouts/header.php';
$u = $user ?? [
    'id' => '',
    'username' => '',
    'email' => '',
    'full_name' => '',
    'phone' => '',
    'role' => 'student',
    'is_active' => 1
];
$actionUrl = $is_edit ? BASE_URL . '/admin/userEdit/' . $u['id'] : BASE_URL . '/admin/userCreate';
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
                    <h1 class="h3 fw-bold text-dark mb-1"><?= $is_edit ? 'Edit Data Pengguna' : 'Tambah Pengguna Baru'; ?></h1>
                    <p class="text-muted mb-0 text-sm"><?= $is_edit ? 'Perbarui profil, hak akses role, atau kata sandi akun.' : 'Isi formulir berikut untuk mendaftarkan akun baru ke sistem.'; ?></p>
                </div>
                <a href="<?= BASE_URL; ?>/admin/users" class="btn btn-outline-secondary btn-sm px-3">
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
                                <div class="col-12 col-md-6">
                                    <label for="fullNameInput" class="form-label text-sm fw-semibold required">Nama Lengkap</label>
                                    <input type="text" name="full_name" class="form-control text-sm" id="fullNameInput" value="<?= htmlspecialchars($u['full_name']); ?>" placeholder="Contoh: Ahmad Dahlan, S.Pd" required>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="roleInput" class="form-label text-sm fw-semibold required">Role / Hak Akses</label>
                                    <select name="role" class="form-select text-sm" id="roleInput" required>
                                        <option value="student" <?= $u['role'] === 'student' ? 'selected' : ''; ?>>Peserta Didik (Student)</option>
                                        <option value="teacher" <?= $u['role'] === 'teacher' ? 'selected' : ''; ?>>Guru / Penguji (Teacher)</option>
                                        <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : ''; ?>>Super Administrator</option>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="usernameInput" class="form-label text-sm fw-semibold required">Username</label>
                                    <input type="text" name="username" class="form-control text-sm" id="usernameInput" value="<?= htmlspecialchars($u['username']); ?>" placeholder="Contoh: ahmad_dahlan" required>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="emailInput" class="form-label text-sm fw-semibold required">Alamat Email</label>
                                    <input type="email" name="email" class="form-control text-sm" id="emailInput" value="<?= htmlspecialchars($u['email']); ?>" placeholder="Contoh: ahmad@sekolah.sch.id" required>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="passwordInput" class="form-label text-sm fw-semibold">
                                        Kata Sandi <?= $is_edit ? '<span class="text-muted fw-normal text-xs">(Kosongkan jika tidak diubah)</span>' : '<span class="text-danger">*</span>'; ?>
                                    </label>
                                    <input type="password" name="password" class="form-control text-sm" id="passwordInput" placeholder="<?= $is_edit ? '••••••••' : 'Minimal 6 karakter'; ?>" <?= $is_edit ? '' : 'required'; ?>>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="phoneInput" class="form-label text-sm fw-semibold">Nomor Telepon / WhatsApp</label>
                                    <input type="text" name="phone" class="form-control text-sm" id="phoneInput" value="<?= htmlspecialchars($u['phone'] ?? ''); ?>" placeholder="Contoh: 081234567890">
                                </div>

                                <div class="col-12">
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="isActiveSwitch" value="1" <?= $u['is_active'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label text-sm fw-semibold" for="isActiveSwitch">Akun Aktif (Dapat Login ke Sistem)</label>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?= BASE_URL; ?>/admin/users" class="btn btn-outline-secondary btn-sm px-3">Batal</a>
                                <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">
                                    <i class="bi bi-save me-1"></i> <?= $is_edit ? 'Simpan Perubahan' : 'Tambah Pengguna'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="custom-card p-4">
                        <h2 class="h6 fw-bold text-dark mb-3"><i class="bi bi-info-circle text-primary me-1"></i> Informasi Panduan</h2>
                        <ul class="text-xs text-muted ps-3 mb-0 d-flex flex-column gap-2">
                            <li><strong>Username & Email</strong> bersifat unik dalam sistem.</li>
                            <li><strong>Kata Sandi</strong> secara otomatis diamankan menggunakan enkripsi <em>BCRYPT</em>.</li>
                            <li>Jika membuat pengguna dengan role <em>Guru</em> atau <em>Peserta</em>, Anda juga dapat mengelola profil spesifiknya pada menu <strong>Guru</strong> atau <strong>Peserta Didik</strong>.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>

        <?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
    </div>
</div>
