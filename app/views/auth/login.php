<?php
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <!-- Logo & Header -->
        <div class="text-center mb-4">
            <div class="auth-logo mb-3">
                <i class="bi bi-cpu-fill"></i>
            </div>
            <h1 class="h4 fw-bold text-dark mb-1"><?= APP_NAME; ?></h1>
            <p class="text-muted text-sm mb-0">Masuk untuk mengakses sistem ujian online</p>
        </div>

        <!-- Database Connection Warning if offline -->
        <?php if (!Database::getInstance()->isConnected()): ?>
            <div class="alert alert-danger text-sm py-2 px-3 mb-3 rounded-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                <strong>Database Terputus:</strong> Tidak dapat terhubung ke MySQL. Pastikan modul <strong>MySQL</strong> di <strong>XAMPP Control Panel</strong> sudah dijalankan (status <em>running / Start</em>).
            </div>
        <?php endif; ?>

        <!-- Flash Message Alert -->
        <?php if (!empty($flash_message)): ?>
            <div class="alert alert-<?= htmlspecialchars($flash_message['type']); ?> alert-dismissible fade show text-sm py-2 px-3 mb-3 rounded-3" role="alert">
                <i class="bi bi-info-circle-fill me-1"></i>
                <?= htmlspecialchars($flash_message['message']); ?>
                <button type="button" class="btn-close py-2 px-3" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form action="<?= BASE_URL; ?>/auth/authenticate" method="POST" autocomplete="off">
            <?= Csrf::tokenField(); ?>
            <div class="mb-3">
                <label for="usernameInput" class="form-label text-sm fw-semibold text-dark">Email atau Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-secondary border-end-0"><i class="bi bi-envelope"></i></span>
                    <input type="text" name="username" class="form-control border-start-0" id="usernameInput" placeholder="Masukkan email atau username" value="" autocomplete="username" required autofocus>
                </div>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="passwordInput" class="form-label text-sm fw-semibold text-dark mb-0">Kata Sandi</label>
                    <a href="<?= BASE_URL; ?>/auth/forgot_password" class="text-xs text-primary fw-medium"><i class="bi bi-whatsapp text-success me-1"></i>Lupa sandi?</a>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-light text-secondary border-end-0"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control border-start-0 border-end-0" id="passwordInput" placeholder="Masukkan kata sandi" value="" autocomplete="current-password" required>
                    <button class="btn btn-outline-secondary border-start-0" type="button" id="btnTogglePassword" onclick="togglePasswordVisibility()" title="Lihat kata sandi">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <div class="mb-4 d-flex align-items-center justify-content-between">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="rememberMe" checked>
                    <label class="form-check-label text-xs text-muted" for="rememberMe">
                        Ingat sesi saya
                    </label>
                </div>
                <span class="badge bg-light text-muted border text-xs">
                    <i class="bi bi-shield-lock text-success me-1"></i> BCRYPT Safe
                </span>
            </div>

            <div class="d-grid gap-2 mb-3">
                <button type="submit" class="btn btn-primary py-2 fw-semibold shadow-sm">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Sistem
                </button>
            </div>
        </form>

        <div class="mt-3 pt-3 border-top text-center text-xs text-muted">
            <i class="bi bi-camera-video me-1 text-primary"></i> AI Proctoring Ready &bull; Multi-Role Protected
        </div>
    </div>
</div>

<script>
function togglePasswordVisibility() {
    const pwd = document.getElementById('passwordInput');
    const icon = document.getElementById('eyeIcon');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
        icon.classList.add('text-primary');
    } else {
        pwd.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.remove('text-primary');
        icon.classList.add('bi-eye');
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
