<?php
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <!-- Logo & Header -->
        <div class="text-center mb-4">
            <div class="auth-logo mb-3" style="background: linear-gradient(135deg, #10b981, #047857); color: white;">
                <i class="bi bi-key-fill"></i>
            </div>
            <h1 class="h4 fw-bold text-dark mb-1">Buat Kata Sandi Baru</h1>
            <p class="text-muted text-sm mb-1">Tentukan kata sandi baru yang aman untuk akun Anda</p>
            <div class="p-2 bg-light rounded-3 border text-xs text-muted mt-2">
                Memperbarui sandi untuk: <strong class="text-dark"><?= htmlspecialchars($user['full_name'] ?? ''); ?></strong> (<code><?= htmlspecialchars($user['username'] ?? ''); ?></code>)
            </div>
        </div>

        <!-- Flash Message Alert -->
        <?php if (!empty($flash_message)): ?>
            <div class="alert alert-<?= htmlspecialchars($flash_message['type']); ?> alert-dismissible fade show text-sm py-2 px-3 mb-3 rounded-3" role="alert">
                <i class="bi bi-info-circle-fill me-1"></i>
                <?= $flash_message['message']; ?>
                <button type="button" class="btn-close py-2 px-3" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Reset Password Form -->
        <form action="<?= BASE_URL; ?>/auth/update_password" method="POST" autocomplete="off" onsubmit="return validatePasswordMatch()">
            <?= Csrf::tokenField(); ?>

            <div class="mb-3">
                <label for="newPassword" class="form-label text-sm fw-semibold text-dark">Kata Sandi Baru</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-secondary border-end-0"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control border-start-0 border-end-0" id="newPassword" placeholder="Minimal 6 karakter" required autofocus minlength="6" oninput="checkStrength()">
                    <button class="btn btn-outline-secondary border-start-0" type="button" onclick="togglePass('newPassword', 'eye1')">
                        <i class="bi bi-eye" id="eye1"></i>
                    </button>
                </div>
                <!-- Strength bar -->
                <div class="progress mt-2" style="height: 4px;">
                    <div id="strengthBar" class="progress-bar bg-danger" role="progressbar" style="width: 0%"></div>
                </div>
                <div id="strengthText" class="text-xs text-muted mt-1">Kekuatan: Sangat lemah</div>
            </div>

            <div class="mb-4">
                <label for="confirmPassword" class="form-label text-sm fw-semibold text-dark">Konfirmasi Kata Sandi Baru</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-secondary border-end-0"><i class="bi bi-shield-lock"></i></span>
                    <input type="password" name="confirm_password" class="form-control border-start-0 border-end-0" id="confirmPassword" placeholder="Ulangi kata sandi baru" required minlength="6">
                    <button class="btn btn-outline-secondary border-start-0" type="button" onclick="togglePass('confirmPassword', 'eye2')">
                        <i class="bi bi-eye" id="eye2"></i>
                    </button>
                </div>
            </div>

            <div class="d-grid gap-2 mb-3">
                <button type="submit" class="btn btn-primary py-2 fw-semibold shadow-sm">
                    <i class="bi bi-check-lg me-1"></i> Simpan Kata Sandi Baru
                </button>
            </div>
        </form>

        <div class="mt-4 pt-3 border-top text-center text-xs text-muted">
            <i class="bi bi-shield-lock text-success me-1"></i> Enkripsi otomatis menggunakan standar <strong>PASSWORD_BCRYPT</strong>
        </div>
    </div>
</div>

<script>
function togglePass(inputId, iconId) {
    const el = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (el.type === 'password') {
        el.type = 'text';
        icon.className = 'bi bi-eye-slash text-primary';
    } else {
        el.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

function checkStrength() {
    const val = document.getElementById('newPassword').value;
    const bar = document.getElementById('strengthBar');
    const text = document.getElementById('strengthText');
    let strength = 0;

    if (val.length >= 6) strength += 25;
    if (val.length >= 8) strength += 25;
    if (/[A-Z]/.test(val) && /[a-z]/.test(val)) strength += 25;
    if (/[0-9]/.test(val) && /[^A-Za-z0-9]/.test(val)) strength += 25;

    bar.style.width = strength + '%';
    if (strength <= 25) {
        bar.className = 'progress-bar bg-danger';
        text.innerText = 'Kekuatan: Lemah';
    } else if (strength <= 50) {
        bar.className = 'progress-bar bg-warning';
        text.innerText = 'Kekuatan: Sedang';
    } else if (strength <= 75) {
        bar.className = 'progress-bar bg-info';
        text.innerText = 'Kekuatan: Baik';
    } else {
        bar.className = 'progress-bar bg-success';
        text.innerText = 'Kekuatan: Sangat Kuat';
    }
}

function validatePasswordMatch() {
    const p1 = document.getElementById('newPassword').value;
    const p2 = document.getElementById('confirmPassword').value;
    if (p1 !== p2) {
        alert('Konfirmasi kata sandi tidak cocok! Silakan periksa kembali.');
        return false;
    }
    return true;
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
