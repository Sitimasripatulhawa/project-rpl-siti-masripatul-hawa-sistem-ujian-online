<?php
require_once __DIR__ . '/../../layouts/header.php';

$configDetails = $config_details ?? EmailService::getConfigurationDetails();
$currentScope = $current_scope ?? 'all';
$currentFilter = $current_filter ?? '';

function buildMailFilterUrl($scope, $status) {
    $params = [];
    if ($scope !== 'all') $params['scope'] = $scope;
    if (!empty($status)) $params['status'] = $status;
    $query = !empty($params) ? '?' . http_build_query($params) : '';
    return BASE_URL . '/admin/mailSettings' . $query;
}
?>

<div class="app-wrapper">
    <!-- Sidebar Navigation -->
    <?php require_once __DIR__ . '/../../layouts/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="app-main">
        <!-- Top Navbar -->
        <?php require_once __DIR__ . '/../../layouts/navbar.php'; ?>

        <!-- Content Area -->
        <main class="app-content">
            <!-- Flash Message -->
            <?php if (!empty($flash_message)): ?>
                <div class="alert alert-<?= htmlspecialchars($flash_message['type']); ?> alert-dismissible fade show text-sm py-3 px-4 mb-4 rounded-3 shadow-sm border-0" role="alert">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle-fill fs-5"></i>
                        <div><?= htmlspecialchars($flash_message['message']); ?></div>
                    </div>
                    <button type="button" class="btn-close py-3 px-3" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h1 class="h3 fw-bold text-dark mb-0">Konfigurasi SMTP & Log Email</h1>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1 text-xs">Super Admin</span>
                    </div>
                    <p class="text-muted mb-0 text-sm">Kelola pengaturan pengiriman email SMTP dan pantau riwayat pengiriman notifikasi terverifikasi.</p>
                </div>

                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL; ?>/admin/settings?tab=mail" class="btn btn-primary btn-sm px-3 shadow-sm">
                        <i class="bi bi-gear-fill me-1"></i> Edit Pengaturan SMTP
                    </a>
                    <button class="btn btn-light btn-sm px-3 border shadow-sm" type="button" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise me-1"></i> Muat Ulang
                    </button>
                    <a href="<?= BASE_URL; ?>/admin/dashboard" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
                    </a>
                </div>
            </div>

            <!-- Configuration & Test Row -->
            <div class="row g-4 mb-4">
                <!-- Status & Configuration -->
                <div class="col-12 col-lg-7">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                <i class="bi bi-server text-primary"></i> Status Konfigurasi SMTP
                            </h5>
                            <?php if ($configDetails['is_configured']): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 text-xs">
                                    <i class="bi bi-check-circle-fill me-1"></i> SMTP Siap (Terkonfigurasi)
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-1 text-xs">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Perlu Dikonfigurasi
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-4">
                            <!-- Checklist Kelengkapan Konfigurasi -->
                            <div class="mb-3 p-3 rounded-3 bg-light border">
                                <span class="text-xs fw-bold text-dark d-block mb-2 text-uppercase">Checklist Kesiapan Pengiriman:</span>
                                <div class="row g-2 text-xs">
                                    <div class="col-6">
                                        <?php if ($configDetails['has_env_file']): ?>
                                            <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> File <code>.env</code> ada</span>
                                        <?php else: ?>
                                            <span class="text-danger fw-semibold"><i class="bi bi-x-circle-fill me-1"></i> File <code>.env</code> belum dibuat</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-6">
                                        <?php if ($configDetails['has_username']): ?>
                                            <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> MAIL_USERNAME terisi</span>
                                        <?php else: ?>
                                            <span class="text-danger fw-semibold"><i class="bi bi-x-circle-fill me-1"></i> MAIL_USERNAME kosong</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-6">
                                        <?php if ($configDetails['has_password']): ?>
                                            <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> MAIL_PASSWORD terisi</span>
                                        <?php else: ?>
                                            <span class="text-danger fw-semibold"><i class="bi bi-x-circle-fill me-1"></i> MAIL_PASSWORD kosong</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> Host & Port (587 TLS)</span>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm table-borderless mb-3 text-sm">
                                    <tbody>
                                        <tr>
                                            <td class="text-muted" style="width: 38%;">SMTP Host</td>
                                            <td class="fw-semibold text-dark"><?= htmlspecialchars($mail_host); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Port & Enkripsi</td>
                                            <td class="fw-semibold text-dark"><?= (int)$mail_port; ?> (<?= htmlspecialchars(strtoupper($mail_encryption)); ?>)</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Akun Pengirim (Username)</td>
                                            <td class="fw-semibold text-dark"><?= htmlspecialchars($mail_username_masked); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Alamat Email Pengirim</td>
                                            <td class="fw-semibold text-dark"><?= htmlspecialchars($mail_from_address); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Nama Tampilan Pengirim</td>
                                            <td class="fw-semibold text-dark"><?= htmlspecialchars($mail_from_name); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="p-3 bg-light rounded-3 text-xs text-secondary border">
                                <strong class="text-dark d-block mb-1"><i class="bi bi-shield-lock text-primary me-1"></i> Panduan Pengisian di Lingkungan XAMPP:</strong>
                                1. Buka folder <code class="text-dark">C:\xampp\htdocs\Project2\</code> lalu buat salinan file <code class="text-primary">.env.example</code> dengan nama <code class="text-primary">.env</code>.<br>
                                2. Isi <code class="text-dark">MAIL_USERNAME=email_anda@gmail.com</code>.<br>
                                3. Isi <code class="text-dark">MAIL_PASSWORD=xxxx xxxx xxxx xxxx</code> dengan <strong>Google App Password (16 karakter)</strong>, BUKAN password login biasa.<br>
                                4. Sandi aplikasi diperoleh di <a href="https://myaccount.google.com/apppasswords" target="_blank" class="text-primary text-decoration-none fw-semibold">Google Account &rarr; Security &rarr; App Passwords</a> setelah Verifikasi 2-Langkah aktif.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Test Email Form -->
                <div class="col-12 col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                            <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                <i class="bi bi-send-check text-primary"></i> Uji Coba Pengiriman Email
                            </h5>
                        </div>
                        <div class="card-body p-4 d-flex flex-column justify-content-between">
                            <div>
                                <p class="text-muted text-sm mb-3">
                                    Kirim satu email pengujian ke alamat email pribadi atau admin untuk memverifikasi autentikasi dan koneksi SMTP relay secara nyata.
                                </p>

                                <?php if (!$configDetails['is_configured']): ?>
                                    <div class="alert alert-warning text-xs py-2 px-3 mb-3 rounded-3 border-0">
                                        <i class="bi bi-exclamation-circle-fill me-1"></i>
                                        <strong>SMTP belum lengkap:</strong> <?= htmlspecialchars(implode(', ', $configDetails['missing'])); ?>. Isi file <code>.env</code> terlebih dahulu agar tes berhasil.
                                    </div>
                                <?php endif; ?>

                                <form action="<?= BASE_URL; ?>/admin/sendTestEmail" method="POST">
                                    <?= Csrf::tokenField(); ?>
                                    <div class="mb-3">
                                        <label for="test_email" class="form-label text-xs fw-semibold text-dark">Alamat Email Penerima Uji Coba</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                                            <input type="email" class="form-control text-sm border-start-0" id="test_email" name="test_email" placeholder="contoh: admin@gmail.com" value="<?= htmlspecialchars($user['email']); ?>" required>
                                        </div>
                                        <div class="form-text text-xs text-muted mt-1">
                                            Hanya 1 pesan uji coba yang akan dikirim (tidak mengirim ke siswa/guru).
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100 btn-sm py-2 rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2">
                                        <i class="bi bi-send"></i>
                                        <span>Kirim Email Pengujian</span>
                                    </button>
                                </form>
                            </div>

                            <div class="mt-4 pt-3 border-top text-xs text-muted">
                                <div class="d-flex align-items-center gap-1 mb-1 text-success fw-medium">
                                    <i class="bi bi-shield-check"></i> Keamanan Terjamin
                                </div>
                                Kredensial tidak pernah dicatat dalam log, HTML, atau pesan error sistem.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="custom-card p-3 h-100">
                        <span class="text-muted text-xs fw-semibold text-uppercase">Total Riwayat</span>
                        <h4 class="fw-bold mb-0 mt-1 text-dark"><?= $stats['total']; ?></h4>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="custom-card p-3 h-100">
                        <span class="text-muted text-xs fw-semibold text-uppercase">Diterima SMTP</span>
                        <h4 class="fw-bold mb-0 mt-1 text-success"><?= $stats['sent']; ?></h4>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="custom-card p-3 h-100">
                        <span class="text-muted text-xs fw-semibold text-uppercase">Gagal</span>
                        <h4 class="fw-bold mb-0 mt-1 text-danger"><?= $stats['failed']; ?></h4>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="custom-card p-3 h-100">
                        <span class="text-muted text-xs fw-semibold text-uppercase">SMTP Belum Diatur</span>
                        <h4 class="fw-bold mb-0 mt-1 text-warning"><?= $stats['smtp_disabled']; ?></h4>
                    </div>
                </div>
            </div>

            <!-- Email Logs Table -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white border-0 py-3 px-4 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <div>
                        <h5 class="fw-bold text-dark mb-0">Riwayat Pengiriman Email</h5>
                        <span class="text-xs text-muted">Log penyerahan notifikasi email ke server SMTP berdasarkan akun pengguna di database.</span>
                    </div>

                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <!-- Scope Filter -->
                        <div class="btn-group btn-group-sm border rounded-pill p-1 bg-light" role="group">
                            <a href="<?= buildMailFilterUrl('all', $currentFilter); ?>" class="btn btn-xs rounded-pill px-3 py-1 <?= ($currentScope === 'all') ? 'btn-white bg-white text-dark shadow-sm fw-bold' : 'btn-light text-muted border-0'; ?>">
                                Semua Lingkup
                            </a>
                            <a href="<?= buildMailFilterUrl('academic', $currentFilter); ?>" class="btn btn-xs rounded-pill px-3 py-1 <?= ($currentScope === 'academic') ? 'btn-white bg-white text-dark shadow-sm fw-bold' : 'btn-light text-muted border-0'; ?>">
                                <i class="bi bi-mortarboard me-1"></i> Akademik
                            </a>
                            <a href="<?= buildMailFilterUrl('test', $currentFilter); ?>" class="btn btn-xs rounded-pill px-3 py-1 <?= ($currentScope === 'test') ? 'btn-white bg-white text-dark shadow-sm fw-bold' : 'btn-light text-muted border-0'; ?>">
                                <i class="bi bi-wrench me-1"></i> Uji Coba
                            </a>
                        </div>

                        <!-- Status Filter -->
                        <div class="d-flex gap-1 flex-wrap">
                            <a href="<?= buildMailFilterUrl($currentScope, ''); ?>" class="btn btn-xs rounded-pill px-3 py-1 <?= empty($currentFilter) ? 'btn-primary' : 'btn-light text-muted border'; ?>">
                                Semua Status
                            </a>
                            <a href="<?= buildMailFilterUrl($currentScope, 'sent'); ?>" class="btn btn-xs rounded-pill px-3 py-1 <?= ($currentFilter === 'sent') ? 'btn-success' : 'btn-light text-muted border'; ?>">
                                Diterima SMTP
                            </a>
                            <a href="<?= buildMailFilterUrl($currentScope, 'failed'); ?>" class="btn btn-xs rounded-pill px-3 py-1 <?= ($currentFilter === 'failed') ? 'btn-danger' : 'btn-light text-muted border'; ?>">
                                Gagal
                            </a>
                            <a href="<?= buildMailFilterUrl($currentScope, 'smtp_disabled'); ?>" class="btn btn-xs rounded-pill px-3 py-1 <?= ($currentFilter === 'smtp_disabled') ? 'btn-warning' : 'btn-light text-muted border'; ?>">
                                Dilewati
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-sm">
                            <thead class="table-light text-xs text-muted text-uppercase">
                                <tr>
                                    <th class="ps-4">Waktu</th>
                                    <th>Penerima (Akun Database)</th>
                                    <th>Event & Peran</th>
                                    <th>Subjek</th>
                                    <th>Status SMTP</th>
                                    <th class="text-center">Percobaan</th>
                                    <th>Detail / Keterangan</th>
                                    <th class="pe-4 text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-2 d-block mb-1 text-secondary"></i>
                                            Belum ada catatan riwayat email untuk filter yang dipilih.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                        <?php
                                        $statusBadges = [
                                            'sent' => 'bg-success-subtle text-success border border-success-subtle',
                                            'failed' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                            'smtp_disabled' => 'bg-warning-subtle text-warning border border-warning-subtle',
                                            'invalid_email' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                                            'pending' => 'bg-info-subtle text-info border border-info-subtle'
                                        ];
                                        $badgeClass = $statusBadges[$log['status']] ?? 'bg-light text-dark';

                                        $statusLabels = [
                                            'sent' => 'Diterima Server SMTP',
                                            'failed' => 'Gagal Terkirim',
                                            'smtp_disabled' => 'SMTP Dilewati',
                                            'invalid_email' => 'Email Tidak Valid',
                                            'pending' => 'Menunggu'
                                        ];
                                        $statusLabel = $statusLabels[$log['status']] ?? ucfirst($log['status']);

                                        // Human-friendly event tags
                                        $eventMeta = [
                                            'exam_published' => ['label' => 'Ujian Diterbitkan', 'badge' => 'bg-primary-subtle text-primary border-primary-subtle', 'role' => 'Peserta Didik (Siswa)'],
                                            'exam_published_teacher' => ['label' => 'Ujian Diterbitkan', 'badge' => 'bg-info-subtle text-info border-info-subtle', 'role' => 'Guru Pengampu'],
                                            'exam_schedule_changed' => ['label' => 'Jadwal Berubah', 'badge' => 'bg-warning-subtle text-warning border-warning-subtle', 'role' => 'Peserta Didik (Siswa)'],
                                            'exam_cancelled' => ['label' => 'Ujian Dibatalkan', 'badge' => 'bg-danger-subtle text-danger border-danger-subtle', 'role' => 'Peserta Didik (Siswa)'],
                                            'exam_result_ready' => ['label' => 'Nilai Tersedia', 'badge' => 'bg-success-subtle text-success border-success-subtle', 'role' => 'Peserta Didik (Siswa)'],
                                            'system_test' => ['label' => 'Uji Coba Sistem', 'badge' => 'bg-secondary-subtle text-secondary border-secondary-subtle', 'role' => 'Administrator']
                                        ];
                                        $meta = $eventMeta[$log['event_type']] ?? [
                                            'label' => $log['event_type'],
                                            'badge' => 'bg-light text-dark border',
                                            'role' => 'Pengguna'
                                        ];
                                        ?>
                                        <tr>
                                            <td class="ps-4 text-xs text-muted" style="white-space: nowrap;">
                                                <?= date('d M Y, H:i', strtotime($log['created_at'])); ?>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark text-truncate" style="max-width: 170px;">
                                                    <?= htmlspecialchars($log['recipient_name'] ?: 'Pengguna'); ?>
                                                </div>
                                                <div class="text-xs text-muted text-truncate" style="max-width: 170px;">
                                                    <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($log['recipient_email']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge <?= $meta['badge']; ?> text-xs mb-1">
                                                    <?= htmlspecialchars($meta['label']); ?>
                                                </span>
                                                <div class="text-xs text-muted"><?= htmlspecialchars($meta['role']); ?></div>
                                            </td>
                                            <td>
                                                <div class="text-xs text-dark text-truncate fw-medium" style="max-width: 220px;" title="<?= htmlspecialchars($log['subject']); ?>">
                                                    <?= htmlspecialchars($log['subject']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge rounded-pill <?= $badgeClass; ?> text-xs px-2 py-1">
                                                    <?= $statusLabel; ?>
                                                </span>
                                            </td>
                                            <td class="text-xs text-muted text-center">
                                                <span class="badge bg-light text-dark border text-xs"><?= (int)$log['attempt_count']; ?>x</span>
                                            </td>
                                            <td>
                                                <?php if (!empty($log['error_message'])): ?>
                                                    <span class="text-xs text-danger text-truncate d-inline-block" style="max-width: 220px;" title="<?= htmlspecialchars($log['error_message']); ?>">
                                                        <?= htmlspecialchars($log['error_message']); ?>
                                                    </span>
                                                <?php elseif ($log['status'] === 'sent'): ?>
                                                    <span class="text-xs text-success"><i class="bi bi-check2"></i> Diserahkan ke relay SMTP</span>
                                                <?php else: ?>
                                                    <span class="text-xs text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <?php if (in_array($log['status'], ['failed', 'smtp_disabled'])): ?>
                                                    <form action="<?= BASE_URL; ?>/admin/retryEmail/<?= $log['id']; ?>" method="POST" class="d-inline">
                                                        <?= Csrf::tokenField(); ?>
                                                        <button type="submit" class="btn btn-xs btn-outline-primary rounded-pill px-2 py-1 text-xs" title="Coba Kirim Ulang">
                                                            <i class="bi bi-arrow-repeat me-1"></i> Coba Ulang
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted text-xs">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer bg-light border-0 py-3 px-4 text-xs text-muted">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle text-primary"></i>
                        <span>
                            <strong>Catatan Status "Diterima Server SMTP":</strong> Status ini menandakan bahwa email telah diterima oleh server relay pengirim (Google SMTP) untuk diteruskan ke alamat tujuan. Keterbukaan pesan pada folder Inbox atau Spam penerima selanjutnya diatur oleh filter keamanan email masing-masing pengguna.
                        </span>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
    </div>
</div>
