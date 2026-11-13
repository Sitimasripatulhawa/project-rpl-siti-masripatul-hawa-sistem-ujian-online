<?php
require_once __DIR__ . '/../layouts/header.php';
$enableProctoring = !empty($exam['enable_proctoring']);
?>

<div class="app-wrapper">
    <!-- Sidebar Navigation -->
    <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="app-main">
        <!-- Top Navbar -->
        <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

        <!-- Content Area -->
        <main class="app-content">
            <!-- Flash Message -->
            <?php if (!empty($flash_message)): ?>
                <div class="alert alert-<?= htmlspecialchars($flash_message['type']); ?> alert-dismissible fade show text-sm py-2 px-3 mb-4 rounded-3" role="alert">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    <?= htmlspecialchars($flash_message['message']); ?>
                    <button type="button" class="btn-close py-2 px-3" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="max-w-900 mx-auto" style="max-width: 880px;">
                <!-- Card Container -->
                <div class="custom-card p-4 p-md-5 mb-4">
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <div class="stat-icon stat-icon-indigo mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.6rem;">
                            <i class="bi bi-camera-video"></i>
                        </div>
                        <h2 class="h4 fw-bold text-dark mb-1">Pemeriksaan Kesiapan Kamera & Sistem</h2>
                        <p class="text-muted text-sm mb-2">Ujian: <strong><?= htmlspecialchars($exam['title']); ?></strong> (<?= (int)$exam['duration_minutes']; ?> Menit)</p>
                        <div>
                            <?php if ($enableProctoring): ?>
                                <span class="badge bg-primary-subtle text-primary text-xs"><i class="bi bi-shield-check me-1"></i> Pengawasan Kamera Diaktifkan (Wajib Kamera Utama)</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-muted text-xs"><i class="bi bi-info-circle me-1"></i> Pengawasan Kamera Opsional</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Error Alert Banner -->
                    <div id="cameraErrorBox" class="alert alert-danger text-sm rounded-3 d-none mb-4" role="alert">
                        <!-- Dynamic error text / permission guide -->
                    </div>

                    <!-- Dual Camera Preview Section -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-webcam text-primary me-2"></i>Uji Coba Kamera Peserta</h5>
                            <button type="button" class="btn btn-outline-primary btn-sm px-3" id="btnRefreshCameras">
                                <i class="bi bi-arrow-clockwise me-1"></i> Uji Ulang Kamera
                            </button>
                        </div>

                        <div class="row g-3">
                            <!-- Camera 1: Webcam Utama -->
                            <div class="col-12 col-md-6">
                                <div class="p-3 border rounded-3 bg-white h-100 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-bold text-dark text-sm">Kamera 1: Wajah (Utama)</span>
                                            <span class="badge bg-primary-subtle text-primary text-xs">Primary</span>
                                        </div>

                                        <!-- Video Frame -->
                                        <div class="camera-box mb-2">
                                            <div class="camera-overlay-badge" id="camera1Status">
                                                <span class="camera-status-dot dot-warning"></span>
                                                <span>Meminta Izin...</span>
                                            </div>
                                            <video id="camera1Video" class="camera-video mirror" autoplay playsinline muted></video>
                                        </div>
                                    </div>

                                    <div>
                                        <label for="camera1Select" class="form-label text-xs text-muted mb-1">Pilih Perangkat Kamera 1:</label>
                                        <select id="camera1Select" class="form-select form-select-sm"></select>
                                    </div>
                                </div>
                            </div>

                            <!-- Camera 2: Kamera Kedua (Sekunder) / Fallback -->
                            <div class="col-12 col-md-6">
                                <div class="p-3 border rounded-3 bg-white h-100 d-flex flex-column justify-content-between">
                                    <!-- Container when 2 cameras are available -->
                                    <div id="camera2Container" class="d-none h-100 d-flex flex-column justify-content-between">
                                        <div>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="fw-bold text-dark text-sm">Kamera 2: Ruangan (Sekunder)</span>
                                                <span class="badge bg-info-subtle text-info text-xs">Secondary</span>
                                            </div>

                                            <div class="camera-box mb-2">
                                                <div class="camera-overlay-badge" id="camera2Status">
                                                    <span class="camera-status-dot dot-warning"></span>
                                                    <span>Memeriksa...</span>
                                                </div>
                                                <video id="camera2Video" class="camera-video" autoplay playsinline muted></video>
                                            </div>
                                        </div>

                                        <div>
                                            <label for="camera2Select" class="form-label text-xs text-muted mb-1">Pilih Perangkat Kamera 2:</label>
                                            <select id="camera2Select" class="form-select form-select-sm"></select>
                                        </div>
                                    </div>

                                    <!-- Fallback card when only 1 camera exists -->
                                    <div id="camera2Fallback" class="camera-fallback-card h-100">
                                        <i class="bi bi-camera-video text-secondary fs-1 mb-2"></i>
                                        <h6 class="fw-bold text-dark mb-1">Mode Kamera Tunggal</h6>
                                        <p class="text-xs text-muted mb-0">
                                            Hanya 1 kamera terdeteksi pada perangkat ini. Sistem akan beroperasi menggunakan <strong>Kamera Utama</strong> (Single Camera Mode).
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System & Peripherals Checklist -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-speedometer2 text-primary me-2"></i>Status Perangkat & Konektivitas</h6>
                        <div class="row g-2">
                            <div class="col-6 col-md-3">
                                <div class="p-2 border rounded-3 bg-light text-center">
                                    <div class="text-xs text-muted">Koneksi Internet</div>
                                    <span class="badge bg-success-subtle text-success text-xs mt-1" id="netBadge">Online</span>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-2 border rounded-3 bg-light text-center">
                                    <div class="text-xs text-muted">Browser Web</div>
                                    <span class="badge bg-success-subtle text-success text-xs mt-1" id="browserBadge">Mendukung</span>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-2 border rounded-3 bg-light text-center">
                                    <div class="text-xs text-muted">Mikrofon</div>
                                    <span class="badge bg-secondary-subtle text-muted text-xs mt-1" id="micStatus">
                                        <i class="bi bi-mic-fill me-1"></i> Memeriksa...
                                    </span>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-2 border rounded-3 bg-light text-center">
                                    <div class="text-xs text-muted">Resolusi Layar</div>
                                    <span class="badge bg-info-subtle text-info text-xs mt-1" id="screenBadge">Optimal</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Privacy & Consent Notice Banner -->
                    <div class="p-3 border rounded-3 bg-light mb-4 text-sm" id="aiPrivacyConsentBox">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-shield-lock-fill text-primary fs-5 mt-1"></i>
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Pemberitahuan Privasi & AI Proctoring (*Privacy by Design*)</h6>
                                <p class="text-xs text-muted mb-1">
                                    Sistem pengawasan cerdas bekerja secara lokal di peramban Anda untuk memastikan integritas ujian yang adil:
                                </p>
                                <ul class="text-xs text-muted ps-3 mb-2">
                                    <li><strong>Tanpa Rekaman Video Penuh:</strong> Video Anda tidak direkam atau disimpan secara penuh di server.</li>
                                    <li><strong>Deteksi Indikasi Lokal:</strong> AI hanya mendeteksi keberadaan wajah, jumlah orang di depan layar, dan status koneksi kamera.</li>
                                    <li><strong>Bukan Vonis Otomatis:</strong> Indikasi yang tercatat hanya menjadi bahan pertimbangan guru pengawas manusia.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Integrity Pledge Form & Enter Room -->
                    <form action="<?= BASE_URL; ?>/student/enterExam/<?= $exam['exam_id']; ?>" method="POST" id="startExamForm">
                        <div class="p-3 border border-primary-subtle bg-primary-subtle rounded-3 mb-4">
                            <div class="form-check">
                                <input class="form-check-input mt-1" type="checkbox" id="pledgeCheck" required style="cursor: pointer;">
                                <label class="form-check-label text-sm text-dark fw-semibold" for="pledgeCheck" style="cursor: pointer;">
                                    Persetujuan Privasi & Pakta Integritas Kejujuran:
                                </label>
                                <p class="text-xs text-muted mb-0 mt-1">
                                    "Saya membaca dan menyetujui ketentuan privasi di atas, berjanji mengerjakan ujian ini secara mandiri tanpa bantuan orang lain, dan menyetujui verifikasi visual kamera selama sesi ujian berlangsung."
                                </p>
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-sm-row justify-content-between gap-3 align-items-center">
                            <a href="<?= BASE_URL; ?>/student/examDetail/<?= $exam['exam_id']; ?>" class="btn btn-outline-secondary btn-sm px-4">
                                <i class="bi bi-arrow-left me-1"></i> Kembali ke Detail
                            </a>
                            <button type="submit" class="btn btn-primary px-4 py-2 fw-bold text-sm" id="btnEnterExam" disabled>
                                <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Ruang Ujian
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <?php require_once __DIR__ . '/../layouts/footer.php'; ?>
    </div>
</div>

<!-- Proctoring Camera JavaScript Engine -->
<script src="<?= BASE_URL; ?>/public/js/proctoring-camera.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const enableProctoring = <?= $enableProctoring ? 'true' : 'false'; ?>;

    // 1. Initialize WebRTC Proctoring Camera Manager
    const cameraManager = new ProctoringCameraManager({
        camera1VideoId: 'camera1Video',
        camera2VideoId: 'camera2Video',
        camera1SelectId: 'camera1Select',
        camera2SelectId: 'camera2Select',
        camera1StatusId: 'camera1Status',
        camera2StatusId: 'camera2Status',
        micStatusId: 'micStatus',
        cameraErrorBoxId: 'cameraErrorBox',
        camera2ContainerId: 'camera2Container',
        camera2FallbackId: 'camera2Fallback',
        btnContinueId: 'btnEnterExam',
        enableProctoring: enableProctoring
    });

    // Request permissions and start live streams
    cameraManager.requestPermissions();

    // Refresh Camera Button
    document.getElementById('btnRefreshCameras').addEventListener('click', function() {
        cameraManager.requestPermissions();
    });

    // Pledge check listener to toggle continue button
    const pledgeCheck = document.getElementById('pledgeCheck');
    pledgeCheck.addEventListener('change', function() {
        cameraManager.updateContinueButton();
    });

    // 2. Hardware and Network Quick Check
    const netBadge = document.getElementById('netBadge');
    if (navigator.onLine) {
        netBadge.textContent = 'Online';
        netBadge.className = 'badge bg-success-subtle text-success text-xs mt-1';
    } else {
        netBadge.textContent = 'Offline';
        netBadge.className = 'badge bg-danger-subtle text-danger text-xs mt-1';
    }

    const screenBadge = document.getElementById('screenBadge');
    screenBadge.textContent = window.screen.width + 'x' + window.screen.height;

    // Stop camera streams before leaving the page
    document.getElementById('startExamForm').addEventListener('submit', function() {
        cameraManager.stopAllStreams();
    });
});
</script>
