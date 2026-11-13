<?php
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <!-- Logo & Header -->
        <div class="text-center mb-4">
            <div class="auth-logo mb-3" style="background: linear-gradient(135deg, #25D366, #128C7E); color: white;">
                <i class="bi bi-whatsapp"></i>
            </div>
            <h1 class="h4 fw-bold text-dark mb-1">Pemulihan Kata Sandi</h1>
            <p class="text-muted text-sm mb-0">Verifikasi cepat melalui kode OTP WhatsApp</p>
        </div>

        <!-- Flash Message Alert -->
        <?php if (!empty($flash_message)): ?>
            <div class="alert alert-<?= htmlspecialchars($flash_message['type']); ?> alert-dismissible fade show text-sm py-2 px-3 mb-3 rounded-3" role="alert">
                <i class="bi bi-info-circle-fill me-1"></i>
                <?= $flash_message['message']; ?>
                <button type="button" class="btn-close py-2 px-3" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Quick Demo Quick-Fill Buttons -->
        <div class="mb-3">
            <label class="form-label text-xs fw-semibold text-muted text-uppercase mb-2">Uji Coba Akun Demo (Nomor WA):</label>
            <div class="btn-group w-100 mb-2" role="group">
                <button type="button" class="btn btn-outline-success btn-sm" onclick="fillPhone('081234567890')">Admin (0812..90)</button>
                <button type="button" class="btn btn-outline-success btn-sm" onclick="fillPhone('081234567891')">Guru (0812..91)</button>
                <button type="button" class="btn btn-outline-success btn-sm" onclick="fillPhone('081234567892')">Siswa (0812..92)</button>
            </div>
        </div>

        <!-- Forgot Password Form -->
        <form action="<?= BASE_URL; ?>/auth/send_otp" method="POST" autocomplete="off">
            <?= Csrf::tokenField(); ?>
            <div class="mb-3">
                <label for="identifierInput" class="form-label text-sm fw-semibold text-dark">Nomor WhatsApp / Username / Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-success border-end-0"><i class="bi bi-whatsapp"></i></span>
                    <input type="text" name="identifier" class="form-control border-start-0" id="identifierInput" placeholder="Contoh: 081234567890" value="081234567890" required autofocus>
                </div>
                <div class="form-text text-xs text-muted mt-1">
                    <i class="bi bi-shield-check text-success me-1"></i> Kode OTP 6 digit akan dikirimkan ke nomor WhatsApp yang terdaftar.
                </div>
            </div>

            <div class="d-grid gap-2 mb-3">
                <button type="submit" class="btn btn-success py-2 fw-semibold shadow-sm" style="background-color: #25D366; border-color: #25D366;">
                    <i class="bi bi-send-fill me-1"></i> Kirim Kode OTP WhatsApp
                </button>
            </div>

            <div class="text-center">
                <a href="<?= BASE_URL; ?>/auth/login" class="text-xs text-secondary text-decoration-none fw-medium">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Halaman Masuk
                </a>
            </div>
        </form>

        <div class="mt-4 pt-3 border-top text-center text-xs text-muted">
            <i class="bi bi-shield-lock text-primary me-1"></i> Powered by <strong>Fonnte WhatsApp API Gateway</strong>
        </div>
    </div>
</div>

<script>
function fillPhone(phone) {
    document.getElementById('identifierInput').value = phone;
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
