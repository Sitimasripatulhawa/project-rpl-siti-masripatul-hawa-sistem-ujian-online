<?php
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <!-- Logo & Header -->
        <div class="text-center mb-4">
            <div class="auth-logo mb-3" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white;">
                <i class="bi bi-shield-check"></i>
            </div>
            <h1 class="h4 fw-bold text-dark mb-1">Verifikasi Kode OTP</h1>
            <p class="text-muted text-sm mb-1">Masukkan 6 digit kode yang telah dikirim ke nomor WhatsApp Anda:</p>
            <div class="badge bg-light text-dark border py-2 px-3 mt-1 font-monospace fs-6">
                <i class="bi bi-whatsapp text-success me-1"></i> <?= htmlspecialchars($masked_phone ?? ''); ?>
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

        <!-- Dev / Simulation Mode / Fallback Helper Badge -->
        <?php if (!empty($simulated_otp)): ?>
            <div class="p-3 mb-3 <?= !empty($simulated_otp['is_error']) ? 'bg-danger-subtle border-danger text-danger-emphasis' : 'bg-warning-subtle border-warning text-warning-emphasis'; ?> border rounded-3 text-xs">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <div>
                        <i class="bi <?= !empty($simulated_otp['is_error']) ? 'bi-exclamation-triangle-fill text-danger' : 'bi-cpu text-warning'; ?> me-1"></i>
                        <strong><?= !empty($simulated_otp['is_error']) ? 'Bantuan Darurat Gateway WhatsApp:' : 'Mode Demo / Uji Coba:'; ?></strong>
                    </div>
                    <button type="button" class="btn btn-sm btn-dark py-0 px-2 text-xs" onclick="pasteSimulatedOtp('<?= htmlspecialchars($simulated_otp['otp'] ?? ''); ?>')">
                        <i class="bi bi-clipboard-check me-1"></i> Tempel OTP
                    </button>
                </div>
                <?php if (!empty($simulated_otp['error_message'])): ?>
                    <div class="small text-danger mb-2 border-top border-danger-subtle pt-1 mt-1">
                        <?= htmlspecialchars($simulated_otp['error_message']); ?>
                    </div>
                <?php endif; ?>
                <div>
                    Kode OTP Anda: <strong class="fs-6 font-monospace text-dark"><?= htmlspecialchars($simulated_otp['otp'] ?? ''); ?></strong>
                </div>
            </div>
        <?php endif; ?>

        <!-- Verify OTP Form -->
        <form action="<?= BASE_URL; ?>/auth/process_verify_otp" method="POST" autocomplete="off">
            <?= Csrf::tokenField(); ?>
            <div class="mb-3">
                <label for="otpInput" class="form-label text-sm fw-semibold text-dark text-center w-100">6 Digit Kode OTP</label>
                <div class="input-group">
                    <input type="text" name="otp" class="form-control form-control-lg text-center font-monospace fw-bold letter-spacing-lg border-2 border-primary" id="otpInput" placeholder="••••••" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" required autofocus style="letter-spacing: 0.5em; font-size: 1.5rem;">
                </div>
                <div class="d-flex justify-content-between align-items-center text-xs text-muted mt-2">
                    <span>Masa berlaku: <strong id="countdownText" class="text-danger font-monospace">10:00</strong></span>
                    <a href="<?= BASE_URL; ?>/auth/resend_otp" class="text-primary text-decoration-none fw-semibold" id="btnResend">
                        <i class="bi bi-arrow-clockwise me-1"></i> Kirim Ulang OTP
                    </a>
                </div>
            </div>

            <div class="d-grid gap-2 mb-3">
                <button type="submit" class="btn btn-primary py-2 fw-semibold shadow-sm">
                    <i class="bi bi-check2-circle me-1"></i> Verifikasi &amp; Lanjutkan
                </button>
            </div>

            <div class="text-center">
                <a href="<?= BASE_URL; ?>/auth/forgot_password" class="text-xs text-secondary text-decoration-none fw-medium">
                    <i class="bi bi-arrow-left me-1"></i> Ganti Nomor WhatsApp Lain
                </a>
            </div>
        </form>

        <div class="mt-4 pt-3 border-top text-center text-xs text-muted">
            <i class="bi bi-shield-lock text-success me-1"></i> Dilindungi oleh Enkripsi Sesi &amp; Time-based Expiration
        </div>
    </div>
</div>

<script>
function pasteSimulatedOtp(otp) {
    const input = document.getElementById('otpInput');
    input.value = otp;
    input.focus();
}

// 10-minute Countdown Timer
let timeLeft = 600; // 10 minutes in seconds
const timerEl = document.getElementById('countdownText');
const interval = setInterval(function() {
    if (timeLeft <= 0) {
        clearInterval(interval);
        timerEl.innerText = "Kedaluwarsa";
    } else {
        timeLeft--;
        const mins = Math.floor(timeLeft / 60);
        const secs = timeLeft % 60;
        timerEl.innerText = String(mins).padStart(2, '0') + ":" + String(secs).padStart(2, '0');
    }
}, 1000);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

