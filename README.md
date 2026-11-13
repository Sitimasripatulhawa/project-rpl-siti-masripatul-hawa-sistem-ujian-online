# AI Online Exam & Proctoring System with Dual Intelligent Chatbot Assistance

> **Proyek Akhir Rekayasa Perangkat Lunak (TIKB2503021)**  
> **Program Studi Teknik Informatika / Rekayasa Perangkat Lunak — STMIK IKMI Cirebon**  
> **Tema:** *E-Learning & Smart Education AI*  
> **Pengembang:** Siti Masripatul Hawa (`Sitimasripatulhawa`)

---

## 📌 Ringkasan Proyek

**AI Online Exam & Proctoring System** adalah platform ujian berbasis web (*Computer Based Test / CBT*) modern yang dibangun secara mandiri menggunakan arsitektur **Pure PHP MVC Native**, dirancang untuk menjawab tantangan integritas akademik dan otomatisasi operasional pada pelaksanaan evaluasi pembelajaran digital.

Sistem ini dilengkapi dengan:
1. **AI Proctoring Engine**: Deteksi kecurangan berbasis visual (kamera pengawas WebRTC, deteksi perpindahan tab/jendela, status layar penuh).
2. **Dual Intelligent Chatbot Assistance**:
   - **Admin Operational Assistant**: Asisten operasional berbasis Rule-based NLP untuk menjawab metrik sistem secara instan (<50ms) seperti total peserta, total ujian aktif, kelas, dan status server.
   - **Student Academic Assistant**: Asisten cerdas pemandu teknis siswa selama persiapan dan pengerjaan ujian online.
3. **Enterprise Multi-Role RBAC**: Hak akses berjenjang ketat untuk **Administrator**, **Guru/Penguji**, dan **Siswa/Peserta**.

---

## 🚀 Fitur Unggulan Sistem

| Modul | Deskripsi Fitur | Role Akses |
|---|---|---|
| **Modul 1: Autentikasi & RBAC** | Login aman BCRYPT, Session Hardening (SameSite/HttpOnly), CSRF Protection, Throttling Brute-Force. | Publik / Semua Role |
| **Modul 2: Master Data Admin** | CRUD Data Pengguna, Guru (NIP/Spesialisasi), Siswa (NIS/NISN/Kelas), Kelas/Rombel, dan Mata Pelajaran. | Administrator |
| **Modul 3: Bank Soal & Ujian** | Manajemen Paket Ujian, Bank Soal (Pilihan Ganda & Bobot Nilai), Pengacakan Soal/Opsi, Token Ujian. | Guru, Admin |
| **Modul 4: Exam Player** | Antarmuka ujian responsif, Auto-save jawaban per detik, Navigasi nomor soal, Indikator sisa waktu. | Siswa |
| **Modul 5: AI Proctoring** | Pengawasan visual via WebCam, Deteksi Blur/Tab Switch, Log Pelanggaran Real-time (*Cheat Incident Report*). | Siswa, Guru |
| **Modul 6: Dual Chatbot AI** | Asisten cerdas real-time pada dashboard Admin (kueri cepat peserta & ujian) dan Exam Room Siswa. | Admin, Siswa |
| **Modul 7: Analisis & Laporan** | Koreksi instan otomatis, Rekapitulasi nilai, Statistik butir soal, Ekspor data ujian. | Guru, Admin |
| **Modul 8: Notifikasi SMTP** | Pengiriman Token Ujian & Pemulihan Kata Sandi aman via Google Mail SMTP. | Sistem |

---

## 🛠️ Tech Stack & Arsitektur

- **Backend**: PHP 8.2+ (Design Pattern MVC - Model View Controller Native tanpa framework luar)
- **Database**: MySQL / MariaDB (PDO Prepared Statements, InnoDB, Foreign Key Cascade)
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla ES6+), Bootstrap 5.3, Bootstrap Icons
- **Keamanan**:
  - Sandi dienkripsi dengan standar industri `PASSWORD_BCRYPT`
  - Proteksi Cross-Site Request Forgery (CSRF) pada seluruh formulir mutasi
  - Proteksi SQL Injection 100% menggunakan PDO Parameterized Query
  - Session Fixation & Hijacking Prevention (Auto-Regenerate ID)
- **Testing**: Test suite integrasi mandiri (`tests/test_admin_crud.php` & `tests/test_http_admin.php`)

---

## 📋 Kredensial Akun Demo (Seed Data)

Untuk kemudahan pengujian dan penilaian, sistem telah dilengkapi dengan data *seed* default:

| Role | Username / Email | Kata Sandi | Deskripsi |
|---|---|---|---|
| **Admin** | `admin@sekolah.sch.id` *(atau `admin`)* | `password123` | Akses penuh dashboard analitik & master data |
| **Guru** | `budi.santoso@sekolah.sch.id` | `password123` | Akses bank soal, pembuatan ujian & penilaian |
| **Siswa** | `ani.lestari@sekolah.sch.id` | `password123` | Akses ruang ujian & riwayat nilai |

*(Kata sandi fleksibel untuk pengujian demo juga menerima: `admin123` / `guru123` / `siswa123`)*

---

## 💻 Panduan Instalasi & Menjalankan di Lokal (XAMPP)

### 1. Prasyarat Sistem
- **XAMPP** dengan PHP 8.1+ dan MySQL running (port default 3306).
- Web browser modern (Google Chrome / Mozilla Firefox / Microsoft Edge).

### 2. Penempatan Folder
Letakkan folder proyek di direktori htdocs XAMPP:
```
C:\xampp\htdocs\Project2
```

### 3. Konfigurasi Database
1. Buka **phpMyAdmin** (`http://localhost/phpmyadmin`)
2. Buat database baru bernama: `db_ai_exam`
3. Impor berkas struktur tabel: `database/schema.sql`
4. Impor berkas data awal: `database/seeds.sql`

### 4. Konfigurasi Lingkungan (`.env`)
Salin file `.env.example` menjadi `.env`:
```bash
cp .env.example .env
```
Sesuaikan konfigurasi email SMTP jika ingin menguji fitur kirim email.

### 5. Menjalankan Aplikasi
Buka peramban (browser) dan akses alamat:
```
http://localhost/Project2/
```
atau langsung ke halaman login:
```
http://localhost/Project2/auth/login
```

---

## 🧪 Menjalankan Pengujian Otomatis (Test Suite)

Pengujian unit dan end-to-end terintegrasi telah disediakan:

```bash
# Menjalankan integrasi CRUD Admin
C:\xampp\php\php.exe tests/test_admin_crud.php

# Menjalankan HTTP End-to-End Test
C:\xampp\php\php.exe tests/test_http_admin.php
```

---

## 📑 Dokumentasi Tahapan Pengembangan

Laporan resmi rancang bangun perangkat lunak tersedia pada folder `docs/`:
- **[Tahap 1: Inisiasi, PRD & SRS (IEEE 830 / ISO 29148)](docs/TAHAP_1_PRD_SRS.md)**

---

## 📄 Hak Cipta & Lisensi

Dibuat untuk memenuhi luaran **Proyek Akhir Rekayasa Perangkat Lunak**  
**Siti Masripatul Hawa** &copy; 2026. Hak Cipta Dilindungi Undang-Undang.
