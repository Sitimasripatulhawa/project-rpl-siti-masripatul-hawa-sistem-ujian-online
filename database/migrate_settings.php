<?php
/**
 * Migration: System Settings Table
 * AI Online Exam & Proctoring System
 * Safe, idempotent migration that creates system_settings table and seeds initial defaults.
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';

echo "=== Running Migration: System Settings ===\n";

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // 1. Create system_settings table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS system_settings (
            setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
            setting_value TEXT NULL,
            setting_group VARCHAR(50) NOT NULL DEFAULT 'general',
            label VARCHAR(150) NOT NULL,
            description TEXT NULL,
            field_type VARCHAR(30) NOT NULL DEFAULT 'text',
            is_secret TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_settings_group (setting_group)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo " [+] Verified/Created table 'system_settings'\n";

    // 2. Define default configurations
    $defaultSettings = [
        // Identitas & Umum
        [
            'key' => 'app_name',
            'value' => defined('APP_NAME') ? APP_NAME : 'AI Online Exam & Proctoring System',
            'group' => 'general',
            'label' => 'Nama Aplikasi',
            'desc' => 'Nama lengkap aplikasi yang ditampilkan pada judul halaman, header, dan footer.',
            'type' => 'text',
            'secret' => 0
        ],
        [
            'key' => 'app_short_name',
            'value' => defined('APP_SHORT_NAME') ? APP_SHORT_NAME : 'AI-Exam',
            'group' => 'general',
            'label' => 'Nama Singkat (Brand)',
            'desc' => 'Singkatan nama aplikasi untuk sidebar dan badge.',
            'type' => 'text',
            'secret' => 0
        ],
        [
            'key' => 'app_institution',
            'value' => 'SMA / SMK Digital Indonesia',
            'group' => 'general',
            'label' => 'Nama Institusi / Sekolah',
            'desc' => 'Nama sekolah atau penyelenggara ujian.',
            'type' => 'text',
            'secret' => 0
        ],
        [
            'key' => 'app_timezone',
            'value' => 'Asia/Jakarta',
            'group' => 'general',
            'label' => 'Zona Waktu Default',
            'desc' => 'Zona waktu server aplikasi (contoh: Asia/Jakarta, Asia/Makassar, Asia/Jayapura).',
            'type' => 'select',
            'secret' => 0
        ],
        [
            'key' => 'app_header_tagline',
            'value' => 'Platform Ujian Online Cerdas dengan Pengawasan AI',
            'group' => 'general',
            'label' => 'Tagline / Slogan Sistem',
            'desc' => 'Deskripsi singkat yang tampil di halaman beranda dan dashboard.',
            'type' => 'text',
            'secret' => 0
        ],

        // Pengaturan Default Sesi Ujian
        [
            'key' => 'exam_default_duration',
            'value' => '90',
            'group' => 'exam',
            'label' => 'Durasi Default Ujian (Menit)',
            'desc' => 'Waktu pengerjaan awal saat guru membuat sesi ujian baru.',
            'type' => 'number',
            'secret' => 0
        ],
        [
            'key' => 'exam_default_passing_score',
            'value' => '75.00',
            'group' => 'exam',
            'label' => 'Nilai Kelulusan Default (KKM)',
            'desc' => 'Standar nilai batas kelulusan minimal siswa.',
            'type' => 'number',
            'secret' => 0
        ],
        [
            'key' => 'exam_default_random_question',
            'value' => '1',
            'group' => 'exam',
            'label' => 'Acak Urutan Soal',
            'desc' => 'Default status pengacakan butir soal pada ujian baru.',
            'type' => 'boolean',
            'secret' => 0
        ],
        [
            'key' => 'exam_default_random_option',
            'value' => '1',
            'group' => 'exam',
            'label' => 'Acak Opsi Pilihan Ganda',
            'desc' => 'Default status pengacakan pilihan jawaban A-E.',
            'type' => 'boolean',
            'secret' => 0
        ],
        [
            'key' => 'exam_default_enable_proctoring',
            'value' => '1',
            'group' => 'exam',
            'label' => 'Aktifkan AI Proctoring',
            'desc' => 'Default status pengaktifan pengawasan kamera & anti kecurangan.',
            'type' => 'boolean',
            'secret' => 0
        ],
        [
            'key' => 'exam_default_show_result',
            'value' => '1',
            'group' => 'exam',
            'label' => 'Tampilkan Nilai ke Siswa',
            'desc' => 'Default izin siswa melihat skor hasil ujian setelah selesai submit.',
            'type' => 'boolean',
            'secret' => 0
        ],

        // Pengaturan Sensitivitas & Bobot Proctoring
        [
            'key' => 'proctoring_cooldown_seconds',
            'value' => '10',
            'group' => 'proctoring',
            'label' => 'Debounce Cooldown (Detik)',
            'desc' => 'Jeda waktu minimal antar pencatatan event pelanggaran sejenis untuk mencegah spam event.',
            'type' => 'number',
            'secret' => 0
        ],
        [
            'key' => 'proctoring_weight_face_not_detected',
            'value' => '10',
            'group' => 'proctoring',
            'label' => 'Bobot: Wajah Tidak Terdeteksi',
            'desc' => 'Poin risiko saat wajah siswa tidak tampak di kamera.',
            'type' => 'number',
            'secret' => 0
        ],
        [
            'key' => 'proctoring_weight_multiple_face',
            'value' => '40',
            'group' => 'proctoring',
            'label' => 'Bobot: Lebih dari 1 Wajah',
            'desc' => 'Poin risiko saat terdeteksi beberapa orang di layar kamera.',
            'type' => 'number',
            'secret' => 0
        ],
        [
            'key' => 'proctoring_weight_camera_disconnected',
            'value' => '20',
            'group' => 'proctoring',
            'label' => 'Bobot: Kamera Terputus',
            'desc' => 'Poin risiko saat stream video webcam mati / dicabut.',
            'type' => 'number',
            'secret' => 0
        ],
        [
            'key' => 'proctoring_weight_camera_blocked',
            'value' => '15',
            'group' => 'proctoring',
            'label' => 'Bobot: Kamera Tertutup / Gelap',
            'desc' => 'Poin risiko saat lensa kamera sengaja ditutupi.',
            'type' => 'number',
            'secret' => 0
        ],
        [
            'key' => 'proctoring_weight_suspicious_gaze',
            'value' => '15',
            'group' => 'proctoring',
            'label' => 'Bobot: Pandangan Mencurigakan',
            'desc' => 'Poin risiko saat siswa menoleh ke samping / keluar layar berulang kali.',
            'type' => 'number',
            'secret' => 0
        ],
        [
            'key' => 'proctoring_weight_tab_switch',
            'value' => '10',
            'group' => 'proctoring',
            'label' => 'Bobot: Pindah Tab Browser',
            'desc' => 'Poin risiko saat siswa berpindah jendela / tab browser.',
            'type' => 'number',
            'secret' => 0
        ],
        [
            'key' => 'proctoring_weight_face_changed',
            'value' => '50',
            'group' => 'proctoring',
            'label' => 'Bobot: Pergantian Wajah (Joki)',
            'desc' => 'Poin risiko tinggi jika wajah berganti dengan orang lain.',
            'type' => 'number',
            'secret' => 0
        ],
        [
            'key' => 'proctoring_threshold_normal_max',
            'value' => '29',
            'group' => 'proctoring',
            'label' => 'Batas Skor Maksimal Status Normal',
            'desc' => 'Skor risiko 0 sampai batas ini dinyatakan Normal / Aman.',
            'type' => 'number',
            'secret' => 0
        ],
        [
            'key' => 'proctoring_threshold_warning_max',
            'value' => '59',
            'group' => 'proctoring',
            'label' => 'Batas Skor Maksimal Status Peringatan (Warning)',
            'desc' => 'Skor di atas Normal sampai batas ini dinyatakan Mencurigakan (Warning).',
            'type' => 'number',
            'secret' => 0
        ],
        [
            'key' => 'proctoring_threshold_high_risk_min',
            'value' => '60',
            'group' => 'proctoring',
            'label' => 'Batas Minimal Status Berisiko Tinggi (High Risk)',
            'desc' => 'Skor di atas nilai ini langsung dikategorikan Pelanggaran Berat (High Risk).',
            'type' => 'number',
            'secret' => 0
        ],

        // Pengaturan Chatbot AI
        [
            'key' => 'gemini_model',
            'value' => defined('GEMINI_MODEL') ? GEMINI_MODEL : 'gemini-3.8-flash',
            'group' => 'ai',
            'label' => 'Model Google Gemini AI',
            'desc' => 'Model kecerdasan buatan aktif untuk asisten cerdas dan pembahasan soal.',
            'type' => 'select',
            'secret' => 0
        ]
    ];

    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM system_settings WHERE setting_key = :k");
    $stmtInsert = $pdo->prepare("
        INSERT INTO system_settings (setting_key, setting_value, setting_group, label, description, field_type, is_secret)
        VALUES (:k, :v, :g, :l, :d, :t, :s)
    ");

    $insertedCount = 0;
    foreach ($defaultSettings as $s) {
        $stmtCheck->execute([':k' => $s['key']]);
        if ($stmtCheck->fetchColumn() == 0) {
            $stmtInsert->execute([
                ':k' => $s['key'],
                ':v' => $s['value'],
                ':g' => $s['group'],
                ':l' => $s['label'],
                ':d' => $s['desc'],
                ':t' => $s['type'],
                ':s' => $s['secret']
            ]);
            $insertedCount++;
        }
    }

    echo " [+] Seeded {$insertedCount} default system settings (idempotent)\n";
    echo "=== Migration System Settings Finished Successfully! ===\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
