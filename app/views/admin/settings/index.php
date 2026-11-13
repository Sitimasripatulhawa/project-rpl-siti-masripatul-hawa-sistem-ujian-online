<?php
require_once __DIR__ . '/../../layouts/header.php';

$activeTab = $active_tab ?? 'general';
$gen = $settings['general'] ?? [];
$exm = $settings['exam'] ?? [];
$proc = $settings['proctoring'] ?? [];
$ai = $settings['ai'] ?? [];

// Helper to safely get value from settings array or fallback
function getSettingVal($settingsArray, $key, $default = '') {
    return isset($settingsArray[$key]['setting_value']) ? $settingsArray[$key]['setting_value'] : $default;
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
            <!-- Flash Message Alert -->
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
                        <h1 class="h3 fw-bold text-dark mb-0">Konfigurasi Sistem</h1>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1 text-xs">Super Admin</span>
                    </div>
                    <p class="text-muted mb-0 text-sm">
                        Kelola parameter aplikasi, standar ujian, sensitivitas AI proctoring, model chatbot, dan konfigurasi server.
                    </p>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-light btn-sm px-3 border shadow-sm" type="button" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise me-1"></i> Muat Ulang
                    </button>
                    <a href="<?= BASE_URL; ?>/admin/mailSettings" class="btn btn-outline-primary btn-sm px-3 shadow-sm">
                        <i class="bi bi-envelope-check me-1"></i> Log & Uji SMTP
                    </a>
                </div>
            </div>

            <!-- Configuration Tabs Navigation -->
            <ul class="nav nav-pills mb-4 gap-2 border-bottom pb-3" id="configTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?= ($activeTab === 'general') ? 'active fw-semibold shadow-sm' : 'text-secondary'; ?> rounded-pill px-3 py-2 text-sm d-flex align-items-center gap-2"
                       href="<?= BASE_URL; ?>/admin/settings?tab=general">
                        <i class="bi bi-sliders"></i>
                        <span>Identitas & Umum</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?= ($activeTab === 'exam') ? 'active fw-semibold shadow-sm' : 'text-secondary'; ?> rounded-pill px-3 py-2 text-sm d-flex align-items-center gap-2"
                       href="<?= BASE_URL; ?>/admin/settings?tab=exam">
                        <i class="bi bi-file-earmark-check"></i>
                        <span>Default Ujian</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?= ($activeTab === 'proctoring') ? 'active fw-semibold shadow-sm' : 'text-secondary'; ?> rounded-pill px-3 py-2 text-sm d-flex align-items-center gap-2"
                       href="<?= BASE_URL; ?>/admin/settings?tab=proctoring">
                        <i class="bi bi-shield-shaded"></i>
                        <span>AI Proctoring</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?= ($activeTab === 'ai') ? 'active fw-semibold shadow-sm' : 'text-secondary'; ?> rounded-pill px-3 py-2 text-sm d-flex align-items-center gap-2"
                       href="<?= BASE_URL; ?>/admin/settings?tab=ai">
                        <i class="bi bi-robot"></i>
                        <span>AI Chatbot</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?= ($activeTab === 'mail') ? 'active fw-semibold shadow-sm' : 'text-secondary'; ?> rounded-pill px-3 py-2 text-sm d-flex align-items-center gap-2"
                       href="<?= BASE_URL; ?>/admin/settings?tab=mail">
                        <i class="bi bi-envelope-gear"></i>
                        <span>Email & SMTP</span>
                    </a>
                </li>
            </ul>

            <!-- TAB 1: IDENTITAS & UMUM -->
            <?php if ($activeTab === 'general'): ?>
                <div class="row g-4">
                    <div class="col-12 col-lg-8">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                                <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                    <i class="bi bi-building-gear text-primary"></i> Identitas Aplikasi & Sekolah
                                </h5>
                                <p class="text-muted text-xs mb-0">Atur penamaan sistem, nama lembaga pendidikan, zona waktu, dan slogan header.</p>
                            </div>
                            <div class="card-body p-4">
                                <form action="<?= BASE_URL; ?>/admin/updateSettings" method="POST">
                                    <?= Csrf::tokenField(); ?>
                                    <input type="hidden" name="group" value="general">

                                    <div class="mb-3">
                                        <label for="app_name" class="form-label text-sm fw-semibold text-dark">
                                            Nama Lengkap Aplikasi <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control text-sm" id="app_name" name="app_name"
                                               value="<?= htmlspecialchars(getSettingVal($gen, 'app_name', APP_NAME)); ?>" required maxlength="100">
                                        <div class="form-text text-xs text-muted">Ditampilkan pada judul website (title tag), login, dan footer.</div>
                                    </div>

                                    <div class="row g-3 mb-3">
                                        <div class="col-12 col-md-6">
                                            <label for="app_short_name" class="form-label text-sm fw-semibold text-dark">
                                                Nama Singkat / Brand <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control text-sm" id="app_short_name" name="app_short_name"
                                                   value="<?= htmlspecialchars(getSettingVal($gen, 'app_short_name', APP_SHORT_NAME)); ?>" required maxlength="30">
                                            <div class="form-text text-xs text-muted">Ditampilkan di header sidebar dan badge sistem.</div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label for="app_institution" class="form-label text-sm fw-semibold text-dark">
                                                Nama Lembaga / Sekolah <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control text-sm" id="app_institution" name="app_institution"
                                                   value="<?= htmlspecialchars(getSettingVal($gen, 'app_institution', 'SMA / SMK Digital Indonesia')); ?>" required maxlength="150">
                                            <div class="form-text text-xs text-muted">Instansi resmi penyelenggara ujian online.</div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="app_timezone" class="form-label text-sm fw-semibold text-dark">
                                            Zona Waktu Server <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select text-sm" id="app_timezone" name="app_timezone" required>
                                            <?php
                                            $currTz = getSettingVal($gen, 'app_timezone', date_default_timezone_get());
                                            foreach ($popular_timezones as $tzVal => $tzLabel):
                                            ?>
                                                <option value="<?= $tzVal; ?>" <?= ($currTz === $tzVal) ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($tzLabel); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text text-xs text-muted">Menentukan sinkronisasi waktu mulai dan selesai ujian bagi siswa.</div>
                                    </div>

                                    <div class="mb-4">
                                        <label for="app_header_tagline" class="form-label text-sm fw-semibold text-dark">
                                            Tagline / Slogan Sistem
                                        </label>
                                        <input type="text" class="form-control text-sm" id="app_header_tagline" name="app_header_tagline"
                                               value="<?= htmlspecialchars(getSettingVal($gen, 'app_header_tagline', 'Platform Ujian Online Cerdas dengan Pengawasan AI')); ?>" maxlength="255">
                                        <div class="form-text text-xs text-muted">Deskripsi ringkas yang tampil pada halaman beranda dan dashboard.</div>
                                    </div>

                                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                                        <button type="reset" class="btn btn-light btn-sm px-3 border">Batal</button>
                                        <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">
                                            <i class="bi bi-check2-circle me-1"></i> Simpan Perubahan
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Side Info Card -->
                    <div class="col-12 col-lg-4">
                        <div class="card border-0 shadow-sm rounded-4 bg-light">
                            <div class="card-body p-4">
                                <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                                    <i class="bi bi-info-circle text-primary"></i> Sinkronisasi Otomatis
                                </h6>
                                <p class="text-xs text-secondary leading-relaxed mb-3">
                                    Perubahan identitas aplikasi dan zona waktu secara otomatis disinkronkan ke dalam file <code>.env</code> dan database.
                                </p>
                                <div class="p-3 bg-white rounded-3 border text-xs">
                                    <div class="fw-semibold text-dark mb-1">Status Runtime Saat Ini:</div>
                                    <div class="text-muted">Zona Waktu: <span class="badge bg-secondary-subtle text-dark"><?= date_default_timezone_get(); ?></span></div>
                                    <div class="text-muted mt-1">Waktu Server: <span class="fw-medium text-dark"><?= date('d M Y, H:i:s'); ?></span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <!-- TAB 2: DEFAULT UJIAN -->
            <?php elseif ($activeTab === 'exam'): ?>
                <div class="row g-4">
                    <div class="col-12 col-lg-8">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                                <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                    <i class="bi bi-file-earmark-ruled text-primary"></i> Standar Default Sesi Ujian Baru
                                </h5>
                                <p class="text-muted text-xs mb-0">Parameter default yang akan otomatis terisi saat Guru membuat sesi ujian baru.</p>
                            </div>
                            <div class="card-body p-4">
                                <form action="<?= BASE_URL; ?>/admin/updateSettings" method="POST">
                                    <?= Csrf::tokenField(); ?>
                                    <input type="hidden" name="group" value="exam">

                                    <div class="row g-3 mb-4">
                                        <div class="col-12 col-md-6">
                                            <label for="exam_default_duration" class="form-label text-sm fw-semibold text-dark">
                                                Durasi Default Pengerjaan <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <input type="number" class="form-control text-sm" id="exam_default_duration" name="exam_default_duration"
                                                       value="<?= (int)getSettingVal($exm, 'exam_default_duration', 90); ?>" min="1" max="600" required>
                                                <span class="input-group-text bg-light text-xs">Menit</span>
                                            </div>
                                            <div class="form-text text-xs text-muted">Contoh: 90 menit untuk ujian standar sekolah.</div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <label for="exam_default_passing_score" class="form-label text-sm fw-semibold text-dark">
                                                Nilai Kelulusan Default (KKM) <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <input type="number" step="0.5" class="form-control text-sm" id="exam_default_passing_score" name="exam_default_passing_score"
                                                       value="<?= htmlspecialchars(getSettingVal($exm, 'exam_default_passing_score', '75.00')); ?>" min="0" max="100" required>
                                                <span class="input-group-text bg-light text-xs">Poin (0-100)</span>
                                            </div>
                                            <div class="form-text text-xs text-muted">Batas minimal status dinyatakan Lulus (Passed).</div>
                                        </div>
                                    </div>

                                    <div class="border rounded-3 p-3 mb-4 bg-light">
                                        <span class="text-xs fw-bold text-dark d-block mb-3 text-uppercase">Fitur Keamanan & Pengacakan Default:</span>

                                        <div class="d-flex flex-column gap-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="exam_default_random_question" name="exam_default_random_question" value="1"
                                                       <?= (getSettingVal($exm, 'exam_default_random_question', 1) == 1) ? 'checked' : ''; ?>>
                                                <label class="form-check-label text-sm fw-semibold" for="exam_default_random_question">
                                                    Acak Urutan Butir Soal (Random Questions)
                                                </label>
                                                <div class="text-xs text-muted">Setiap siswa menerima urutan nomor soal yang berlainan secara otomatis.</div>
                                            </div>

                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="exam_default_random_option" name="exam_default_random_option" value="1"
                                                       <?= (getSettingVal($exm, 'exam_default_random_option', 1) == 1) ? 'checked' : ''; ?>>
                                                <label class="form-check-label text-sm fw-semibold" for="exam_default_random_option">
                                                    Acak Urutan Opsi Pilihan Ganda (Random Options)
                                                </label>
                                                <div class="text-xs text-muted">Pilihan opsi A, B, C, D, E diacak acak untuk setiap peserta ujian.</div>
                                            </div>

                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="exam_default_enable_proctoring" name="exam_default_enable_proctoring" value="1"
                                                       <?= (getSettingVal($exm, 'exam_default_enable_proctoring', 1) == 1) ? 'checked' : ''; ?>>
                                                <label class="form-check-label text-sm fw-semibold" for="exam_default_enable_proctoring">
                                                    Wajibkan Pengawasan AI Proctoring & Kamera
                                                </label>
                                                <div class="text-xs text-muted">Pemeriksaan webcam ganda/tunggal dan deteksi kecurangan real-time saat ujian.</div>
                                            </div>

                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="exam_default_show_result" name="exam_default_show_result" value="1"
                                                       <?= (getSettingVal($exm, 'exam_default_show_result', 1) == 1) ? 'checked' : ''; ?>>
                                                <label class="form-check-label text-sm fw-semibold" for="exam_default_show_result">
                                                    Tampilkan Nilai ke Siswa Segera Setelah Submit
                                                </label>
                                                <div class="text-xs text-muted">Jika dinonaktifkan, siswa hanya melihat status selesai tanpa nilai angka.</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                                        <button type="reset" class="btn btn-light btn-sm px-3 border">Batal</button>
                                        <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">
                                            <i class="bi bi-check2-circle me-1"></i> Simpan Pengaturan Ujian
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-4">
                        <div class="card border-0 shadow-sm rounded-4 bg-light">
                            <div class="card-body p-4">
                                <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                                    <i class="bi bi-person-workspace text-primary"></i> Fleksibilitas Guru
                                </h6>
                                <p class="text-xs text-secondary leading-relaxed mb-0">
                                    Nilai default ini membantu Guru menghemat waktu saat merancang sesi ujian baru. Guru tetap dapat menyesuaikan parameter tersebut secara spesifik pada masing-masing mata pelajaran jika diperlukan.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            <!-- TAB 3: AI PROCTORING -->
            <?php elseif ($activeTab === 'proctoring'): ?>
                <div class="row g-4">
                    <div class="col-12 col-lg-8">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                                <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                    <i class="bi bi-shield-lock-fill text-primary"></i> Bobot Poin Pelanggaran & Ambang Batas Risiko
                                </h5>
                                <p class="text-muted text-xs mb-0">Atur sensitivitas deteksi kecurangan dan bobot penalti risiko yang dihitung oleh Proctoring Engine.</p>
                            </div>
                            <div class="card-body p-4">
                                <form action="<?= BASE_URL; ?>/admin/updateSettings" method="POST">
                                    <?= Csrf::tokenField(); ?>
                                    <input type="hidden" name="group" value="proctoring">

                                    <div class="mb-4">
                                        <label for="proctoring_cooldown_seconds" class="form-label text-sm fw-semibold text-dark">
                                            Debounce Cooldown (Detik) <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group" style="max-width: 250px;">
                                            <input type="number" class="form-control text-sm" id="proctoring_cooldown_seconds" name="proctoring_cooldown_seconds"
                                                   value="<?= (int)getSettingVal($proc, 'proctoring_cooldown_seconds', 10); ?>" min="1" max="120" required>
                                            <span class="input-group-text bg-light text-xs">Detik</span>
                                        </div>
                                        <div class="form-text text-xs text-muted">Jeda waktu minimal antar event sejenis agar tidak terjadi duplikasi log berlebihan.</div>
                                    </div>

                                    <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom text-sm">
                                        <i class="bi bi-graph-up-arrow text-danger me-1"></i> Bobot Skor Pelanggaran per Event:
                                    </h6>

                                    <div class="row g-3 mb-4">
                                        <div class="col-12 col-md-6">
                                            <label class="form-label text-xs fw-semibold text-dark">Wajah Tidak Terdeteksi (FACE_NOT_DETECTED)</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control text-sm" name="proctoring_weight_face_not_detected"
                                                       value="<?= (int)getSettingVal($proc, 'proctoring_weight_face_not_detected', 10); ?>" min="1" max="100" required>
                                                <span class="input-group-text bg-light text-xs">Poin</span>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label text-xs fw-semibold text-dark">Multi-Wajah Terdeteksi (MULTIPLE_FACE)</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control text-sm" name="proctoring_weight_multiple_face"
                                                       value="<?= (int)getSettingVal($proc, 'proctoring_weight_multiple_face', 40); ?>" min="1" max="100" required>
                                                <span class="input-group-text bg-light text-xs">Poin</span>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label text-xs fw-semibold text-dark">Kamera Terputus (CAMERA_DISCONNECTED)</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control text-sm" name="proctoring_weight_camera_disconnected"
                                                       value="<?= (int)getSettingVal($proc, 'proctoring_weight_camera_disconnected', 20); ?>" min="1" max="100" required>
                                                <span class="input-group-text bg-light text-xs">Poin</span>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label text-xs fw-semibold text-dark">Kamera Tertutup / Gelap (CAMERA_BLOCKED)</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control text-sm" name="proctoring_weight_camera_blocked"
                                                       value="<?= (int)getSettingVal($proc, 'proctoring_weight_camera_blocked', 15); ?>" min="1" max="100" required>
                                                <span class="input-group-text bg-light text-xs">Poin</span>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label text-xs fw-semibold text-dark">Pandangan Menoleh (SUSPICIOUS_GAZE)</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control text-sm" name="proctoring_weight_suspicious_gaze"
                                                       value="<?= (int)getSettingVal($proc, 'proctoring_weight_suspicious_gaze', 15); ?>" min="1" max="100" required>
                                                <span class="input-group-text bg-light text-xs">Poin</span>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label text-xs fw-semibold text-dark">Pindah Tab Browser (TAB_SWITCH)</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control text-sm" name="proctoring_weight_tab_switch"
                                                       value="<?= (int)getSettingVal($proc, 'proctoring_weight_tab_switch', 10); ?>" min="1" max="100" required>
                                                <span class="input-group-text bg-light text-xs">Poin</span>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label text-xs fw-semibold text-dark">Pergantian Wajah Joki (FACE_CHANGED - Kritis)</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control text-sm" name="proctoring_weight_face_changed"
                                                       value="<?= (int)getSettingVal($proc, 'proctoring_weight_face_changed', 50); ?>" min="1" max="100" required>
                                                <span class="input-group-text bg-light text-xs">Poin</span>
                                            </div>
                                        </div>
                                    </div>

                                    <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom text-sm">
                                        <i class="bi bi-speedometer2 text-primary me-1"></i> Ambang Batas Klasifikasi Status Risiko (0 - 100):
                                    </h6>

                                    <div class="row g-3 mb-4">
                                        <div class="col-12 col-md-4">
                                            <label class="form-label text-xs fw-semibold text-success">
                                                <i class="bi bi-check-circle-fill me-1"></i> Normal Max
                                            </label>
                                            <input type="number" class="form-control text-sm border-success-subtle" name="proctoring_threshold_normal_max"
                                                   value="<?= (int)getSettingVal($proc, 'proctoring_threshold_normal_max', 29); ?>" min="1" max="80" required>
                                            <div class="form-text text-xs text-muted">Skor 0 - nilai ini = NORMAL</div>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <label class="form-label text-xs fw-semibold text-warning">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Warning Max
                                            </label>
                                            <input type="number" class="form-control text-sm border-warning-subtle" name="proctoring_threshold_warning_max"
                                                   value="<?= (int)getSettingVal($proc, 'proctoring_threshold_warning_max', 59); ?>" min="2" max="95" required>
                                            <div class="form-text text-xs text-muted">Skor di atas Normal = WARNING</div>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <label class="form-label text-xs fw-semibold text-danger">
                                                <i class="bi bi-x-octagon-fill me-1"></i> High Risk Min
                                            </label>
                                            <input type="number" class="form-control text-sm border-danger-subtle" name="proctoring_threshold_high_risk_min"
                                                   value="<?= (int)getSettingVal($proc, 'proctoring_threshold_high_risk_min', 60); ?>" min="3" max="100" required>
                                            <div class="form-text text-xs text-muted">Skor &ge; nilai ini = HIGH RISK</div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                                        <button type="reset" class="btn btn-light btn-sm px-3 border">Batal</button>
                                        <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">
                                            <i class="bi bi-check2-circle me-1"></i> Simpan Konfigurasi Proctoring
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-4">
                        <div class="card border-0 shadow-sm rounded-4 bg-light">
                            <div class="card-body p-4">
                                <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                                    <i class="bi bi-eye text-primary"></i> Penilaian Otomatis
                                </h6>
                                <p class="text-xs text-secondary leading-relaxed mb-3">
                                    Nilai penalti ini digunakan oleh <code>ProctoringRiskService</code> untuk mengkalkulasi skor risiko peserta saat pengerjaan ujian berlangsung.
                                </p>
                                <div class="p-3 bg-white rounded-3 border text-xs">
                                    <div class="fw-semibold text-dark mb-2">Rentang Status Risiko:</div>
                                    <div class="mb-1"><span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">NORMAL</span> Skor 0 - <?= (int)getSettingVal($proc, 'proctoring_threshold_normal_max', 29); ?></div>
                                    <div class="mb-1"><span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">WARNING</span> Skor <?= (int)getSettingVal($proc, 'proctoring_threshold_normal_max', 29) + 1; ?> - <?= (int)getSettingVal($proc, 'proctoring_threshold_warning_max', 59); ?></div>
                                    <div><span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">HIGH RISK</span> Skor &ge; <?= (int)getSettingVal($proc, 'proctoring_threshold_high_risk_min', 60); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <!-- TAB 4: AI CHATBOT -->
            <?php elseif ($activeTab === 'ai'): ?>
                <div class="row g-4">
                    <div class="col-12 col-lg-8">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                                <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                    <i class="bi bi-cpu-fill text-primary"></i> Pengaturan Model & Kredensial AI
                                </h5>
                                <p class="text-muted text-xs mb-0">Kelola model Google Gemini AI aktif dan kredensial API Key untuk asisten cerdas ExamBot.</p>
                            </div>
                            <div class="card-body p-4">
                                <form action="<?= BASE_URL; ?>/admin/updateSettings" method="POST">
                                    <?= Csrf::tokenField(); ?>
                                    <input type="hidden" name="group" value="ai">

                                    <div class="mb-4">
                                        <label for="gemini_model" class="form-label text-sm fw-semibold text-dark">
                                            Model Google Gemini AI Aktif <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select text-sm" id="gemini_model" name="gemini_model" required>
                                            <?php
                                            $currModel = getSettingVal($ai, 'gemini_model', defined('GEMINI_MODEL') ? GEMINI_MODEL : 'gemini-3.8-flash');
                                            foreach ($available_ai_models as $mKey => $mLabel):
                                            ?>
                                                <option value="<?= $mKey; ?>" <?= ($currModel === $mKey) ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($mLabel); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text text-xs text-muted">Model prioritas utama yang dipanggil saat siswa atau guru berinteraksi dengan Chatbot.</div>
                                    </div>

                                    <div class="mb-4">
                                        <label for="gemini_api_key" class="form-label text-sm fw-semibold text-dark d-flex justify-content-between align-items-center">
                                            <span>Google AI Studio API Key</span>
                                            <?php if (!empty(defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '')): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill text-xs px-2 py-1">
                                                    <i class="bi bi-shield-check me-1"></i> API Key Tersimpan
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill text-xs px-2 py-1">
                                                    <i class="bi bi-exclamation-triangle me-1"></i> Belum Dikonfigurasi
                                                </span>
                                            <?php endif; ?>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="bi bi-key-fill text-muted"></i></span>
                                            <input type="password" class="form-control text-sm" id="gemini_api_key" name="gemini_api_key"
                                                   placeholder="●●●●●●●●●●●● (Kosongkan jika tidak ingin mengubah API Key)" autocomplete="off">
                                        </div>
                                        <div class="form-text text-xs text-muted mt-1">
                                            Kredensial disimpan aman di file <code>.env</code>. Dapatkan API Key resmi gratis di
                                            <a href="https://aistudio.google.com/" target="_blank" class="text-primary text-decoration-none fw-semibold">Google AI Studio</a>.
                                        </div>
                                    </div>

                                    <div class="p-3 bg-light rounded-3 border mb-4 text-xs">
                                        <div class="fw-semibold text-dark mb-1 d-flex align-items-center gap-1">
                                            <i class="bi bi-shield-check text-primary"></i> Perlindungan Kredensial & Fallback Mandiri:
                                        </div>
                                        <p class="text-secondary mb-0">
                                            Jika koneksi ke Google Gemini terputus atau batas kuota habis, sistem secara otomatis mengalihkan respon ke
                                            <strong>Engine Scoped Database Lokal</strong> tanpa mengganggu kelancaran proses ujian siswa.
                                        </p>
                                    </div>

                                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                                        <button type="reset" class="btn btn-light btn-sm px-3 border">Batal</button>
                                        <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">
                                            <i class="bi bi-check2-circle me-1"></i> Simpan Konfigurasi AI
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-4">
                        <div class="card border-0 shadow-sm rounded-4 bg-light">
                            <div class="card-body p-4">
                                <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                                    <i class="bi bi-lightbulb text-primary"></i> Fitur Chatbot+
                                </h6>
                                <p class="text-xs text-secondary leading-relaxed mb-3">
                                    Fitur Chatbot+ menggabungkan kecerdasan buatan LLM dengan data relasional MySQL (13 tabel) untuk memberikan pembahasan soal, panduan teknis ujian, dan pengingat jadwal yang presisi.
                                </p>
                                <a href="<?= BASE_URL; ?>/student/dashboard" class="btn btn-outline-secondary btn-sm w-100 text-xs">
                                    <i class="bi bi-chat-dots me-1"></i> Uji Coba Chatbot di Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            <!-- TAB 5: EMAIL & SMTP -->
            <?php elseif ($activeTab === 'mail'): ?>
                <div class="row g-4">
                    <div class="col-12 col-lg-8">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                                <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                    <i class="bi bi-envelope-at-fill text-primary"></i> Konfigurasi Server SMTP & Notifikasi Email
                                </h5>
                                <p class="text-muted text-xs mb-0">Atur kredensial pengiriman email untuk notifikasi jadwal ujian dan hasil penilaian.</p>
                            </div>
                            <div class="card-body p-4">
                                <form action="<?= BASE_URL; ?>/admin/updateSettings" method="POST">
                                    <?= Csrf::tokenField(); ?>
                                    <input type="hidden" name="group" value="mail">

                                    <div class="row g-3 mb-3">
                                        <div class="col-12 col-md-8">
                                            <label for="mail_host" class="form-label text-sm fw-semibold text-dark">
                                                SMTP Host <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control text-sm" id="mail_host" name="mail_host"
                                                   value="<?= htmlspecialchars($smtp['host']); ?>" required placeholder="contoh: smtp.gmail.com">
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <label for="mail_port" class="form-label text-sm fw-semibold text-dark">
                                                Port <span class="text-danger">*</span>
                                            </label>
                                            <input type="number" class="form-control text-sm" id="mail_port" name="mail_port"
                                                   value="<?= (int)$smtp['port']; ?>" min="1" max="65535" required placeholder="587">
                                        </div>
                                    </div>

                                    <div class="row g-3 mb-3">
                                        <div class="col-12 col-md-6">
                                            <label for="mail_encryption" class="form-label text-sm fw-semibold text-dark">
                                                Tipe Enkripsi <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select text-sm" id="mail_encryption" name="mail_encryption" required>
                                                <option value="tls" <?= ($smtp['encryption'] === 'tls') ? 'selected' : ''; ?>>TLS (Direkomendasikan - Port 587)</option>
                                                <option value="ssl" <?= ($smtp['encryption'] === 'ssl') ? 'selected' : ''; ?>>SSL (Port 465)</option>
                                                <option value="none" <?= ($smtp['encryption'] === 'none') ? 'selected' : ''; ?>>None (Tanpa Enkripsi)</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label for="mail_username" class="form-label text-sm fw-semibold text-dark">
                                                Akun Email Pengirim (Username) <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control text-sm" id="mail_username" name="mail_username"
                                                   value="<?= htmlspecialchars($smtp['username']); ?>" required placeholder="email_anda@gmail.com">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="mail_password" class="form-label text-sm fw-semibold text-dark d-flex justify-content-between align-items-center">
                                            <span>Sandi Aplikasi / Password SMTP</span>
                                            <span class="badge bg-secondary-subtle text-dark border rounded-pill text-xs px-2 py-1">
                                                <?= htmlspecialchars($smtp['password_masked']); ?>
                                            </span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="bi bi-shield-lock-fill text-muted"></i></span>
                                            <input type="password" class="form-control text-sm" id="mail_password" name="mail_password"
                                                   placeholder="Kosongkan jika tidak ingin mengubah sandi yang sudah ada" autocomplete="off">
                                        </div>
                                        <div class="form-text text-xs text-muted mt-1">
                                            <strong class="text-dark">Penting:</strong> Untuk Google Mail / Gmail, gunakan <strong>App Password (16 karakter)</strong>, BUKAN sandi login biasa.
                                        </div>
                                    </div>

                                    <div class="row g-3 mb-4">
                                        <div class="col-12 col-md-6">
                                            <label for="mail_from_address" class="form-label text-sm fw-semibold text-dark">
                                                Alamat Email Pengirim (From Address)
                                            </label>
                                            <input type="email" class="form-control text-sm" id="mail_from_address" name="mail_from_address"
                                                   value="<?= htmlspecialchars($smtp['from_address']); ?>" placeholder="noreply@sekolah.sch.id">
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label for="mail_from_name" class="form-label text-sm fw-semibold text-dark">
                                                Nama Tampilan Pengirim (From Name)
                                            </label>
                                            <input type="text" class="form-control text-sm" id="mail_from_name" name="mail_from_name"
                                                   value="<?= htmlspecialchars($smtp['from_name']); ?>" placeholder="<?= htmlspecialchars(APP_NAME); ?>">
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                        <a href="<?= BASE_URL; ?>/admin/mailSettings" class="btn btn-outline-secondary btn-sm px-3">
                                            <i class="bi bi-clock-history me-1"></i> Buka Log Pengiriman Email
                                        </a>
                                        <div class="d-flex gap-2">
                                            <button type="reset" class="btn btn-light btn-sm px-3 border">Batal</button>
                                            <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">
                                                <i class="bi bi-check2-circle me-1"></i> Simpan Konfigurasi SMTP
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-4">
                        <div class="card border-0 shadow-sm rounded-4 bg-light">
                            <div class="card-body p-4">
                                <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                                    <i class="bi bi-send-check text-primary"></i> Status & Uji Coba
                                </h6>
                                <div class="mb-3">
                                    <span class="text-xs text-muted d-block mb-1">Status Kesiapan SMTP:</span>
                                    <?php if ($smtp['is_configured']): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 text-xs d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-check-circle-fill"></i> SMTP Siap & Terkonfigurasi
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-2 text-xs d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-exclamation-triangle-fill"></i> Perlu Melengkapi Akun / Password
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="p-3 bg-white rounded-3 border text-xs mb-3">
                                    <div class="fw-semibold text-dark mb-1">Pengirim Terdaftar:</div>
                                    <div class="text-muted"><?= htmlspecialchars($smtp['username_masked']); ?></div>
                                    <div class="text-muted mt-1">Host: <strong><?= htmlspecialchars($smtp['host']); ?>:<?= (int)$smtp['port']; ?></strong> (<?= strtoupper($smtp['encryption']); ?>)</div>
                                </div>

                                <a href="<?= BASE_URL; ?>/admin/mailSettings" class="btn btn-primary btn-sm w-100 shadow-sm text-xs">
                                    <i class="bi bi-send me-1"></i> Lakukan Uji Coba Kirim Email
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </main>

        <!-- Footer -->
        <?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
    </div>
</div>
