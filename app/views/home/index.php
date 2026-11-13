<?php
require_once __DIR__ . '/../layouts/header.php';
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
            <!-- Page Header -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1">Beranda & Pusat Ujian</h1>
                    <p class="text-muted mb-0 text-sm">Selamat datang di <strong><?= APP_NAME; ?></strong> — Platform Ujian Online Cerdas dengan Pengawasan AI.</p>
                </div>
                <div class="d-flex gap-2">
                    <?php if (!Auth::check()): ?>
                        <a href="<?= BASE_URL; ?>/auth/login" class="btn btn-outline-primary btn-sm px-3">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Masuk Akun
                        </a>
                    <?php else: ?>
                        <?php
                        $user = Auth::user();
                        $dashUrl = BASE_URL . '/' . ($user['role'] ?? 'student') . '/dashboard';
                        ?>
                        <a href="<?= $dashUrl; ?>" class="btn btn-primary btn-sm px-3 shadow-sm">
                            <i class="bi bi-speedometer2 me-1"></i> Buka Dashboard (<?= ucfirst($user['role'] ?? ''); ?>)
                        </a>
                    <?php endif; ?>
                    <button class="btn btn-primary btn-sm px-3" type="button" data-chatbot-prompt="Halo ExamBot, apa saja fitur utama di sistem ujian online ini?">
                        <i class="bi bi-robot me-1"></i> Tanya AI Assistant
                    </button>
                </div>
            </div>

            <!-- ================= AI CHATBOT HERO SHOWCASE ================= -->
            <div class="ai-assistant-hero-card">
                <div class="row align-items-center g-4">
                    <div class="col-12 col-lg-7">
                        <span class="ai-hero-badge">
                            <i class="bi bi-stars text-warning"></i> Powered by Google Gemini AI Model
                        </span>
                        <h2 class="ai-hero-title">
                            Punya Pertanyaan? Tanyakan ke ExamBot (Gemini AI)
                        </h2>
                        <p class="ai-hero-desc">
                            ExamBot ditenagai model kecerdasan buatan <strong>Google Gemini</strong> untuk menjawab mekanisme ujian online, teknologi <strong>AI Proctoring</strong> (deteksi wajah, tab switch, skor risiko), panduan guru membuat bank soal, hingga kendala izin webcam secara cerdas dan interaktif.
                        </p>

                        <!-- Quick Prompts Buttons -->
                        <div class="ai-hero-quick-prompts">
                            <button type="button" class="ai-hero-prompt-btn" data-chatbot-prompt="Apa itu AI Proctoring dan bagaimana cara kerjanya?">
                                <i class="bi bi-shield-check text-warning"></i> Apa itu AI Proctoring?
                            </button>
                            <button type="button" class="ai-hero-prompt-btn" data-chatbot-prompt="Bagaimana alur siswa saat mengikuti ujian online?">
                                <i class="bi bi-mortarboard text-info"></i> Panduan Siswa Ujian
                            </button>
                            <button type="button" class="ai-hero-prompt-btn" data-chatbot-prompt="Bagaimana cara guru membuat ujian dan memantau siswa?">
                                <i class="bi bi-journal-text text-success"></i> Panduan untuk Guru
                            </button>
                            <button type="button" class="ai-hero-prompt-btn" data-chatbot-prompt="Berikan informasi akun demo untuk admin, guru, dan siswa">
                                <i class="bi bi-key-fill text-warning"></i> Akun Demo Siap Pakai
                            </button>
                            <button type="button" class="ai-hero-prompt-btn" data-chatbot-prompt="Bagaimana solusi jika kamera webcam tidak terdeteksi saat ujian?">
                                <i class="bi bi-camera-video text-danger"></i> Solusi Kamera Error
                            </button>
                        </div>
                    </div>

                    <div class="col-12 col-lg-5">
                        <div class="ai-hero-action-box">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-success d-inline-block" style="width: 10px; height: 10px;"></div>
                                        <span class="text-xs fw-semibold text-white text-uppercase tracking-wider">ExamBot Status: Online</span>
                                    </div>
                                    <span class="badge bg-white bg-opacity-20 text-white text-xs">Versi 1.0</span>
                                </div>
                                <h5 class="fw-bold text-white mb-2">Mulai Konsultasi Interaktif</h5>
                                <p class="text-xs text-white text-opacity-75 mb-3">
                                    Klik tombol di bawah atau gunakan tombol chat melayang di pojok kanan bawah layar untuk berdialog langsung dengan AI.
                                </p>
                            </div>
                            <div class="d-flex flex-column gap-2 mt-2">
                                <button type="button" class="btn btn-light text-primary fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm py-2" data-chatbot-prompt="Halo ExamBot, jelaskan fitur anti-curang sistem ini">
                                    <i class="bi bi-chat-quote-fill"></i> Buka Percakapan dengan ExamBot
                                </button>
                                <a href="<?= BASE_URL; ?>/auth/login" class="btn btn-outline-light btn-sm text-center">
                                    <i class="bi bi-box-arrow-in-right me-1"></i> Menuju Halaman Login & Ujian
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="custom-card p-3 h-100 hover-lift">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-xs fw-semibold text-uppercase">Total Pengguna</span>
                                <h3 class="fw-bold mb-0 mt-1"><?= $stats['total_users'] ?? 0; ?></h3>
                                <span class="text-xs text-success"><i class="bi bi-arrow-up-short"></i> 3 Role Terdaftar</span>
                            </div>
                            <div class="stat-icon stat-icon-indigo">
                                <i class="bi bi-people-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="custom-card p-3 h-100 hover-lift">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-xs fw-semibold text-uppercase">Ujian Aktif</span>
                                <h3 class="fw-bold mb-0 mt-1"><?= $stats['active_exams'] ?? 0; ?></h3>
                                <span class="text-xs text-primary"><i class="bi bi-clock-history"></i> Siap Dikerjakan</span>
                            </div>
                            <div class="stat-icon stat-icon-emerald">
                                <i class="bi bi-journal-check"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="custom-card p-3 h-100 hover-lift">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-xs fw-semibold text-uppercase">Bank Soal</span>
                                <h3 class="fw-bold mb-0 mt-1"><?= $stats['question_banks'] ?? 0; ?></h3>
                                <span class="text-xs text-muted"><i class="bi bi-folder-check"></i> Dari Guru Terverifikasi</span>
                            </div>
                            <div class="stat-icon stat-icon-amber">
                                <i class="bi bi-collection-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="custom-card p-3 h-100 hover-lift">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-xs fw-semibold text-uppercase">AI Proctoring Alert</span>
                                <h3 class="fw-bold mb-0 mt-1"><?= $stats['proctoring_alerts'] ?? 0; ?></h3>
                                <span class="text-xs text-danger"><i class="bi bi-shield-exclamation"></i> Indikasi Terpantau</span>
                            </div>
                            <div class="stat-icon stat-icon-rose">
                                <i class="bi bi-camera-video-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Role Architecture Preview & Demo Accounts -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-lg-8">
                    <div class="custom-card p-4 h-100">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h2 class="h5 fw-bold text-dark mb-0">Role & Alur Sistem Ujian</h2>
                            <span class="badge bg-light text-dark border">3 Aktor Utama</span>
                        </div>
                        <p class="text-muted text-sm mb-3">
                            Aplikasi ini dirancang untuk menyajikan ekosistem penilaian sekolah yang terpadu, adil, dan transparan:
                        </p>

                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <div class="p-3 rounded-3 border bg-light h-100 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge badge-role-admin">1. Super Admin</span>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-1">Manajemen & Sistem</h6>
                                        <p class="text-xs text-muted mb-2">
                                            Pengelolaan data master: User, Guru, Siswa, Kelas, Mata Pelajaran, serta monitoring sistem secara keseluruhan.
                                        </p>
                                    </div>
                                    <div class="mt-2 pt-2 border-top">
                                        <span class="text-xs text-muted d-block">Demo Login:</span>
                                        <code class="text-xs">admin@sekolah.sch.id</code>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="p-3 rounded-3 border bg-light h-100 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge badge-role-guru">2. Guru / Penguji</span>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-1">Bank Soal & Ujian</h6>
                                        <p class="text-xs text-muted mb-2">
                                            Penyusunan butir soal, pembuatan jadwal ujian, penetapan kelas peserta, pantauan proctoring log & penilaian.
                                        </p>
                                    </div>
                                    <div class="mt-2 pt-2 border-top">
                                        <span class="text-xs text-muted d-block">Demo Login:</span>
                                        <code class="text-xs">guru_budi</code>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="p-3 rounded-3 border bg-light h-100 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge badge-role-peserta">3. Peserta Didik</span>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-1">Pengerjaan Ujian</h6>
                                        <p class="text-xs text-muted mb-2">
                                            Pengerjaan ujian berbasis timer, randomisasi, auto-save jawaban, auto-submit, dan pengawasan kamera AI.
                                        </p>
                                    </div>
                                    <div class="mt-2 pt-2 border-top">
                                        <span class="text-xs text-muted d-block">Demo Login:</span>
                                        <code class="text-xs">siswa_ani</code>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="custom-card p-4 h-100">
                        <h2 class="h5 fw-bold text-dark mb-3">Keunggulan AI Proctoring</h2>
                        <ul class="list-unstyled mb-0 d-flex flex-column gap-3">
                            <li class="d-flex align-items-start gap-2 text-sm">
                                <i class="bi bi-shield-check text-primary mt-1 fs-5"></i>
                                <div>
                                    <strong>Deteksi Wajah Real-Time:</strong>
                                    <div class="text-muted text-xs">Mendeteksi bila wajah peserta tidak berada di depan kamera atau terdapat banyak wajah.</div>
                                </div>
                            </li>
                            <li class="d-flex align-items-start gap-2 text-sm">
                                <i class="bi bi-window-fullscreen text-primary mt-1 fs-5"></i>
                                <div>
                                    <strong>Pantauan Tab & Layar Penuh:</strong>
                                    <div class="text-muted text-xs">Mencatat bila peserta berpindah tab peramban atau keluar dari mode layar penuh.</div>
                                </div>
                            </li>
                            <li class="d-flex align-items-start gap-2 text-sm">
                                <i class="bi bi-graph-up-arrow text-primary mt-1 fs-5"></i>
                                <div>
                                    <strong>Trust Index & Risk Score:</strong>
                                    <div class="text-muted text-xs">Kalkulasi tingkat integritas pengerjaan secara objektif untuk peninjauan guru.</div>
                                </div>
                            </li>
                            <li class="d-flex align-items-start gap-2 text-sm">
                                <i class="bi bi-chat-heart text-primary mt-1 fs-5"></i>
                                <div>
                                    <strong>Asisten AI 24/7 (ExamBot):</strong>
                                    <div class="text-muted text-xs">Siap memandu peserta kapan saja sebelum dan sesudah pelaksanaan ujian.</div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <?php require_once __DIR__ . '/../layouts/footer.php'; ?>
    </div>
</div>
