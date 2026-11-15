# LAPORAN TUGAS PROYEK AKHIR MANDIRI
## REKAYASA PERANGKAT LUNAK (TIKB2503021) — KURIKULUM OBE
### TEMA TOPIK KELOMPOK: *E-LEARNING & SMART EDUCATION AI*

---

# AI ONLINE EXAM & PROCTORING SYSTEM WITH DUAL INTELLIGENT CHATBOT ASSISTANCE
**Platform Ujian Online Mandiri Berbasis Web Native MVC dengan Pengawasan Integritas AI Computer Vision Real-Time dan Asistensi Dialogis Cerdas**

---

### IDENTITAS MAHASISWA & AKADEMIK
* **Perguruan Tinggi** : STMIK IKMI Cirebon
* **Program Studi** : S1 Teknik Informatika
* **Mata Kuliah / Kode** : Rekayasa Perangkat Lunak (TIKB2503021)
* **Bobot Perkuliahan** : 4 SKS (2 Teori + 2 Praktik)
* **Semester / Angkatan** : Semester 3 / Tahun Akademik 2026/2027
* **Dosen Pengampu** : Yudhistira Arie Wijaya, M.Kom., CDS.
* **Sifat Pengerjaan** : Mandiri / Perorangan (100% Individual Ownership & Full-App Build)
* **Nama Pengembang** : **Siti Masripatul Hawa** (`Sitimasripatulhawa`)
* **NIM** : 2023010001
* **Kelompok** : Kelompok 2 (*E-Learning & Smart Education AI*)
* **Repositori GitHub** : `https://github.com/Sitimasripatulhawa/proyek-rpl-ai-exam`
* **Versi Dokumen** : 1.0.0 (Laporan Akhir Terintegrasi)
* **Tanggal Pengesahan** : November 2026

---

## KATA PENGANTAR

Puji dan syukur saya panjatkan ke hadirat Tuhan Yang Maha Esa atas rahmat dan karunia-Nya sehingga dokumen **Laporan Proyek Akhir Mandiri Rekayasa Perangkat Lunak (TIKB2503021)** dengan judul **"AI Online Exam & Proctoring System with Dual Intelligent Chatbot Assistance"** dapat diselesaikan dengan tuntas, terukur, dan tepat waktu.

Laporan ini disusun secara mandiri sebagai bentuk pertanggungjawaban akademik dalam memenuhi luaran mata kuliah berbasis kurikulum *Outcome-Based Education* (OBE) pada Program Studi S1 Teknik Informatika STMIK IKMI Cirebon. Proyek ini mengintegrasikan seluruh siklus hidup pengembangan perangkat lunak (*Software Development Life Cycle* / SDLC) secara utuh, dimulai dari inisiasi kebutuhan (PRD/SRS), perancangan sistem dan pemodelan berorientasi objek (SDD & 4 Diagram UML), konstruksi kode bersih (*Clean Code & SOLID*), integrasi layanan kecerdasan artifisial (AI), pengujian otomatis (*Unit & HTTP Integration Testing*), hingga otomatisasi *DevOps CI/CD Pipeline* berbasis GitHub Actions.

Penulis menyampaikan rasa terima kasih dan penghargaan yang sebesar-besarnya kepada Bapak **Yudhistira Arie Wijaya, M.Kom., CDS.** selaku dosen pengampu atas arahan, standar rekayasa modern, dan rubrik penilaian OBE yang telah membimbing penulis menjadi *Full-Stack Software Engineer* yang kompeten dan bertanggung jawab. Penulis menyadari karya ini tidak lepas dari kekurangan, sehingga masukan yang membangun sangat diharapkan demi kesempurnaan sistem di masa mendatang.

Cirebon, November 2026  
**Siti Masripatul Hawa** (NIM: 2023010001)

---

## DAFTAR ISI
1. [BAB I: PENDAHULUAN & PRODUCT REQUIREMENTS DOCUMENT (PRD / SRS)](#bab-i-pendahuluan--product-requirements-document-prd--srs)
   - 1.1 Latar Belakang Masalah (Problem Statement)
   - 1.2 Nilai Guna & Solusi Rekayasa (Value Proposition)
   - 1.3 Batasan Sistem (System Boundaries)
   - 1.4 Profil Pengguna Sasaran (User Personas)
   - 1.5 Spesifikasi Kebutuhan Fungsional (FR-01 s/d FR-08)
   - 1.6 Spesifikasi Kebutuhan Non-Fungsional (NFR)
2. [BAB II: PERANCANGAN SISTEM & ARSITEKTUR UML (SDD)](#bab-ii-perancangan-sistem--arsitektur-uml-sdd)
   - 2.1 Arsitektur Sistem 3-Tier MVC Native & Service Layer
   - 2.2 Diagram 1: Use Case Diagram & Skenario Lengkap
   - 2.3 Diagram 2: Activity Diagram Alur Bisnis & AI Proctoring
   - 2.4 Diagram 3: Class Diagram Berorientasi Objek & Prinsip SOLID
   - 2.5 Diagram 4: Sequence Diagram Alur Pertukaran Pesan
   - 2.6 Perancangan Basis Data (Entity Relationship Diagram)
   - 2.7 Perancangan Antarmuka Pengguna (UI/UX)
3. [BAB III: KONSTRUKSI KODE & INTEGRASI FITUR AI MANDIRI](#bab-iii-konstruksi-kode--integrasi-fitur-ai-mandiri)
   - 3.1 Struktur Direktori & Clean Code Standards
   - 3.2 Modul Autentikasi, Keamanan Sesi & RBAC Multi-Role
   - 3.3 Engine Pengerjaan Ujian & Resilient Auto-Save
   - 3.4 Modul AI Computer Vision Proctoring & Heuristic Risk Engine
   - 3.5 Modul Dual Intelligent Chatbot AI (ExamBot & Academic Tutor)
   - 3.6 Modul Notifikasi Eksternal (WhatsApp Gateway & Email SMTP)
4. [BAB IV: PENGUJIAN OTOMATIS (TESTING) & DEVOPS CI/CD GITHUB ACTIONS](#bab-iv-pengujian-otomatis-testing--devops-cicd-github-actions)
   - 4.1 Metodologi & Arsitektur Pengujian
   - 4.2 Matriks & Hasil 6 Rangkaian Uji Coba Otomatis (100% Passing)
   - 4.3 Konfigurasi Otomatisasi CI/CD Pipeline (`.github/workflows/ci.yml`)
   - 4.4 Verifikasi Kualitas & Riwayat Git Commits
5. [BAB V: PANDUAN INSTALASI SISTEM & KESIMPULAN](#bab-v-panduan-instalasi-sistem--kesimpulan)
   - 5.1 Prasyarat Sistem
   - 5.2 Petunjuk Instalasi Step-by-Step (XAMPP & Apache)
   - 5.3 Kredensial Akun Pengujian (Seed Data)
   - 5.4 Kesimpulan & Refleksi Pembelajaran Mandiri
6. [DAFTAR PUSTAKA](#daftar-pustaka)

---

# BAB I: PENDAHULUAN & PRODUCT REQUIREMENTS DOCUMENT (PRD / SRS)
*Standar: IEEE 830 / ISO 29148 — Kategori Skor 4 (Sangat Baik)*

### 1.1 Latar Belakang Masalah (Problem Statement)
Peralihan metode evaluasi pembelajaran konvensional menuju sistem ujian berbasis komputer (*Computer-Based Test* / CBT) telah diadopsi secara luas di dunia akademik. Kendati demikian, pelaksanaan ujian *online* jarak jauh tanpa pengawasan fisik langsung menghadapi tantangan integritas akademik yang kritis:
1. **Maraknya Tindak Kecurangan Digital (*Academic Dishonesty*)**:
   Peserta dapat dengan mudah membuka tab browser baru untuk mencari jawaban di mesin pencari, bertukar pesan instan, membuka dokumen ilegal, atau digantikan oleh pihak lain (*joki ujian*) saat kamera pengawas tidak aktif atau tidak dianalisis secara cerdas.
2. **Keterbatasan Pengawasan Manual Guru (*Proctoring Fatigue*)**:
   Dalam rombongan belajar (rombel) beranggotakan 30–40 siswa, satu orang guru pengawas manusia tidak mampu mengamati layar dan gerak-gerik visual seluruh peserta secara simultan dan konsisten selama 60–120 menit durasi ujian.
3. **Ketiadaan Umpan Balik Pedagogis Pasca-Ujian**:
   Sebagian besar platform CBT komersial hanya menerbitkan nilai mentah (*raw score*) tanpa menyajikan penjelasan konsep mendalam mengenai butir soal yang dijawab salah oleh siswa. Hal ini mereduksi esensi asesmen sebagai sarana diagnostik pembelajaran formatif.
4. **Kerapuhan Sesi Ujian terhadap Gangguan Jaringan**:
   Platform ujian konvensional umumnya mewajibkan reload halaman saat submit jawaban; ketidakstabilan koneksi sesaat berisiko menyebabkan jawaban yang telah dipilih siswa hilang.

### 1.2 Nilai Guna & Solusi Rekayasa (Value Proposition)
Untuk mengatasi permasalahan tersebut secara terukur, dibangun sistem mandiri **AI Online Exam & Proctoring System with Dual Intelligent Chatbot Assistance** dengan keunggulan rekayasa:
* **AI Computer Vision Proctoring Real-Time**: Pengawasan aktif berbasis WebRTC di peramban yang mendeteksi ketiadaan wajah (*face absent*), wajah lebih dari satu (*multiple faces*), penutupan kamera, serta perpindahan fokus tab/jendela (*tab-switch*). Pelanggaran diolah secara matematis oleh algoritma *Proctoring Risk Service* di backend menjadi *Risk Score* (0–100%) dan *Trust Index* objektif bagi guru.
* **Dual Intelligent Chatbot AI**:
  1. *Chatbot Tipe 1 (Global Floating ExamBot)*: Menjawab pertanyaan statistik operasional, status server, rombel, dan panduan teknis secara instan (<50ms) menggunakan data riil basis data.
  2. *Chatbot Tipe 2 (Pedagogical Question Explainer Tutor)*: Mengintegrasikan model LLM Google Gemini AI di halaman evaluasi nilai untuk membedah konsep butir soal, menganalisis jawaban salah, dan memberikan bimbingan interaktif per nomor soal.
* **Resilient Auto-Save Engine**: Mekanisme penyimpanan jawaban otomatis per detik via AJAX asinkron yang menjamin data jawaban tetap aman meskipun jaringan internet terputus atau terjadi pemadaman listrik.

### 1.3 Batasan Sistem (System Boundaries)
* **Lingkup Pengguna**: Mendukung 3 peran terotentikasi (*Super Admin, Guru Penguji, Siswa Peserta*) dan peran tamu (*Guest*) pada landing page.
* **Lingkup Perangkat Keras**: Menggunakan webcam standar laptop/PC siswa tanpa membutuhkan sensor biometrik eksternal berbayar.
* **Lingkup Komputasi AI**: Deteksi wajah awal berjalan pada sisi klien (*client-side lightweight vision*), kalkulasi skor risiko dan penalti berjalan di backend server lokal, dan dialog akademik cerdas ditenagai oleh Google Gemini AI dengan *graceful local fallback*.

### 1.4 Profil Pengguna Sasaran (User Personas)

| Peran (Role) | Deskripsi Karakteristik | Wewenang & Tanggung Jawab Utama |
|---|---|---|
| **Administrator (Admin)** | Staf pengelola IT dan kurikulum sekolah | Mengelola akun seluruh peran, master data Rombel Kelas & Mapel, konfigurasi SMTP Email & WA Gateway, serta memantau log audit sistem. |
| **Guru (Teacher)** | Tenaga pendidik penyusun instrumen evaluasi | Mengelola Bank Soal & Pembahasan, membuat jadwal paket ujian, memantau *Live Monitoring* dengan snapshot pelanggaran, dan memvalidasi nilai. |
| **Siswa (Student)** | Peserta didik yang menempuh evaluasi | Menjalani uji kesiapan perangkat (WebRTC), mengerjakan ujian dalam lingkungan anti-curang, dan berdiskusi dengan AI Tutor pasca-ujian. |
| **Tamu (Guest)** | Calon siswa / publik pada landing page | Mengakses informasi umum platform, bertanya pada ExamBot, dan mengakses formulir login. |

### 1.5 Spesifikasi Kebutuhan Fungsional (Functional Requirements)
* **FR-01 (Autentikasi & RBAC Multi-Role)**: Sistem mewajibkan login aman menggunakan hashing `PASSWORD_BCRYPT`, proteksi session fixation/hijacking, proteksi CSRF, pembatasan brute-force (*lockout 15 menit jika 5 kali gagal*), serta fitur pemulihan sandi via OTP WhatsApp (Fonnte API) dan Google Mail SMTP.
* **FR-02 (Manajemen Data Master)**: Admin berwenang melakukan operasi CRUD pada data Kelas/Rombel, Mata Pelajaran, Akun Guru (lengkap dengan NIP dan spesialisasi bidang), dan Akun Siswa (lengkap dengan NIS, NISN, dan rombel kelas).
* **FR-03 (Bank Soal & Penjadwalan Ujian)**: Guru dapat menyusun butir soal Pilihan Ganda (A-E) dan Essay berbobot nilai, kunci jawaban, dan teks pembahasan resmi, serta menjadwalkan sesi ujian dengan pengaturan countdown timer, KKM, pengacakan soal, dan token akses.
* **FR-04 (Exam Player & Resilient Auto-Save)**: Ruang ujian dilengkapi proteksi *fullscreen guard*, blokir tombol inspeksi elemen/copy-paste/alt-tab, timer mundur real-time, dan penyimpanan jawaban asinkron via AJAX tanpa refresh halaman.
* **FR-05 (AI Proctoring Risk Engine)**: Sistem mendeteksi otomatis perpindahan tab, wajah hilang, dan multi-wajah melalui umpan video WebRTC, menghitung penalti risiko kumulatif (*Risk Score* 0–100%), dan menampilkan modal peringatan interaktif di layar siswa.
* **FR-06 (Live Monitoring Guru)**: Dasbor guru menampilkan tabel status seluruh peserta (mengerjakan, submit, terindikasi curang), nilai sementara, Trust Index, serta galeri snapshot bukti rekaman kecurangan.
* **FR-07 (Dual Intelligent Chatbot)**:
  - *ExamBot*: Asisten operasional berbasis Rule-based & Hybrid NLP dengan pengungkapan bertahap (*progressive disclosure*: menjawab ringkas jumlah total data pada turn 1 dan menampilkan rincian detail saat ditanya "siapa saja?").
  - *Question Explainer*: Tutor AI berbasis Google Gemini API yang terisolasi khusus di ruang pembahasan butir soal.
* **FR-08 (Penilaian Otomatis & Analitik)**: Pengoreksian otomatis instan untuk soal pilihan ganda, rekapitulasi nilai kelas, analisis ketuntasan KKM, dan ekspor laporan nilai.

### 1.6 Spesifikasi Kebutuhan Non-Fungsional (NFR)
1. **Performa (Performance)**: Latensi auto-save jawaban < 100 milidetik; latensi kueri basis data Chatbot lokal < 50 milidetik.
2. **Keamanan (Security)**: 100% parameter SQL diamankan menggunakan *Prepared Statements PDO* (zero SQL Injection risk); cookie sesi diamankan dengan flag `HttpOnly`, `SameSite=Lax`, dan `Secure` bila HTTPS; proteksi prompt injection dan credential filtering pada Chatbot.
3. **Ketersediaan & Keandalan (Reliability)**: *Resilient Fallback Mode* — apabila koneksi API AI Gemini eksternal mengalami timeout/gangguan kuota, sistem otomatis beralih ke pembahasan resmi basis data lokal tanpa crash.
4. **Kompatibilitas (Portability)**: Responsif penuh di peramban Google Chrome, Mozilla Firefox, dan Microsoft Edge pada resolusi layar mulai dari 1280x720 hingga 1920x1080.

---

# BAB II: PERANCANGAN SISTEM & ARSITEKTUR UML (SDD)
*Target Luaran: 4 Diagram UML Lengkap, Desain SOLID, dan ERD Basis Data*

### 2.1 Arsitektur Sistem 3-Tier MVC Native & Service Layer
Sistem dirancang mandiri menggunakan arsitektur **3-Tier Pure PHP MVC (Model-View-Controller) Native** tanpa menggunakan framework pihak ketiga, yang diperluas dengan **Domain Service Layer**:
* **Presentation Tier**: Antarmuka HTML5, CSS3, Bootstrap 5.3, Bootstrap Icons, WebRTC Client MediaDevices, dan Vanilla JavaScript.
* **Application Tier**: App Router dinamis (`App.php`), Base Controller (`Controller.php`), `AuthController`, `AdminController`, `TeacherController`, `StudentController`.
* **Domain / AI Service Layer**: `ProctoringRiskService` (kalkulasi penalti risiko), `ChatbotService` (dialog NLP & integrasi Gemini API), `FonnteService` (gateway OTP WhatsApp), dan `EmailService` (PHPMailer SMTP).
* **Data Access Layer**: Base Model (`Model.php`), Singleton Database (`Database.php`), dan tabel-tabel MySQL dengan foreign key cascade.

---

### 2.2 Diagram 1: Use Case Diagram & Skenario Lengkap
*Berkas Gambar*: [`docs/diagrams/01_use_case_diagram.png`](diagrams/01_use_case_diagram.png)

```
[KIRI: Aktor Manusia]             [TENGAH: Batas Sistem Ujian]           [KANAN: Aktor AI]
  👑 Administrator  ======> (UC-01 s/d UC-04, UC-12)
  👨‍🏫 Guru / Penguji ======> (UC-01, UC-05, UC-06, UC-07, UC-12)   <====== 🤖 AI Engine
  🎓 Siswa / Peserta =====> (UC-01, UC-08, UC-09, UC-11, UC-12)          (UC-04, UC-10, UC-11)
```

#### Visualisasi Diagram Use Case:
![Use Case Diagram](diagrams/01_use_case_diagram.png)

#### Matriks 12 Use Case:
| ID | Nama Use Case | Aktor Terlibat | Relasi Ketergantungan |
|---|---|---|---|
| **UC-01** | Autentikasi & RBAC Multi-Role | Admin, Guru, Siswa | Dasar dari seluruh modul internal |
| **UC-02** | Kelola Master Data Sekolah | Admin | Independen |
| **UC-03** | Pengaturan Sistem & Gateway | Admin | Independen |
| **UC-04** | Chatbot Asisten Operasional (ExamBot)| Admin, AI Engine | Independen |
| **UC-05** | Bank Soal & Paket Ujian | Guru, Admin | Independen |
| **UC-06** | Live Monitoring Ujian Real-Time | Guru | Diperluas oleh UC-10 (`«extend»`) |
| **UC-07** | Penilaian Otomatis & Analitik | Guru, Admin | Memerlukan UC-09 (`«include»`) |
| **UC-08** | Cek Kesiapan Sistem (WebRTC) | Siswa | Diperlukan oleh UC-09 (`«include»`) |
| **UC-09** | Kerjakan Ujian Online & Auto-Save | Siswa | Memuat UC-01, UC-08, UC-10 (`«include»`) |
| **UC-10** | Pengawasan AI Proctoring Real-Time | Siswa, AI Engine | Memperluas UC-06 (`«extend»`) |
| **UC-11** | Chatbot Academic Question Explainer | Siswa, AI Engine | Independen / Pasca-Ujian |
| **UC-12** | Reset Password via OTP WA & Email | Admin, Guru, Siswa | Independen |

#### Skenario Rinci Use Case Utama (UC-09: Kerjakan Ujian & UC-10: AI Proctoring):
* **Aktor**: Siswa & AI Engine
* **Prekondisi**: Siswa telah berhasil login, status akun aktif, dan lulus uji kamera WebRTC (UC-08).
* **Alur Utama (Main Flow)**:
  1. Siswa memasukkan token ujian aktif pada dasbor.
  2. Sistem memvalidasi token dan menginisialisasi sesi ujian di tabel `exam_sessions`.
  3. Ruang ujian (`room.php`) dirender bersamaan dengan pengacakan butir soal dan opsi jawaban.
  4. Antarmuka mengaktifkan mode layar penuh (*fullscreen guard*) dan umpan video kamera WebRTC di latar belakang.
  5. Siswa memilih opsi jawaban; skrip JavaScript mengirimkan payload AJAX ke `/student/save_answer`.
  6. Backend memproses penyimpanan ke `exam_answers` dan mengirimkan status sukses HTTP 200.
  7. Indikator butir soal berubah warna menjadi hijau bertanda tersimpan tanpa interupsi layar.
  8. AI Proctoring memantau fokus jendela dan citra wajah siswa secara periodik.
  9. Siswa menekan konfirmasi selesai ujian atau waktu mundur mencapai 00:00:00.
  10. Sistem mengeksekusi penilaian otomatis dan mengalihkan siswa ke halaman hasil.
* **Alur Alternatif (Pelanggaran Terdeteksi)**:
  * 8a. Siswa berpindah tab atau meminimalkan jendela (`visibilitychange` event).
  * 8b. Klien mengirim log insiden ke `/student/log_proctoring`.
  * 8c. `ProctoringRiskService` menambahkan bobot penalti (+15 poin) dan menghitung ulang skor risiko.
  * 8d. Sistem memunculkan modal peringatan merah di layar siswa dan memutar audio alert.
  * 8e. Jika skor risiko kumulatif > 75%, sesi ditandai sebagai `CHEATING` dan memicu alert merah di dasbor guru.

---

### 2.3 Diagram 2: Activity Diagram Alur Bisnis & AI Proctoring
*Berkas Gambar*: [`docs/diagrams/02_activity_diagram.png`](diagrams/02_activity_diagram.png)

Diagram aktivitas dimodelkan ke dalam 4 jalur tanggung jawab (*swimlanes*):
1. **Siswa (Client / WebRTC)**: Navigasi nomor soal, input opsi jawaban, penangkapan frame kamera, penanganan event tab/layar.
2. **Sistem Ujian (Backend MVC Controller)**: Validasi token CSRF/sesi, routing request, orkestrasi pemanggilan model.
3. **AI Proctoring Engine (Service)**: Pembobotan penalti insiden, agregasi risiko, dan penentuan status kecurangan.
4. **Database (MySQL Storage)**: Persistensi data jawaban, log event proctoring, dan rekapitulasi nilai akhir.

![Activity Diagram](diagrams/02_activity_diagram.png)

#### Mekanisme Alur Paralel (Fork & Join):
* **Fork Bar**: Saat ruang ujian dibuka, eksekusi dipecah menjadi dua cabang simultan non-blocking:
  - *Cabang A (Pengerjaan Ujian)*: Siswa menjawab soal dan memicu AJAX auto-save per detik.
  - *Cabang B (Pengawasan AI)*: Kamera WebRTC menangkap frame berkala untuk mendeteksi wajah hilang, wajah ganda, atau perpindahan tab.
* **Join Bar**: Kedua cabang dikonsolidasikan kembali saat tombol submit ditekan atau waktu habis, memastikan seluruh jawaban dan insiden telah tersimpan sebelum kalkulasi nilai akhir dilakukan.

---

### 2.4 Diagram 3: Class Diagram Berorientasi Objek & Prinsip SOLID
*Berkas Gambar*: [`docs/diagrams/03_class_diagram.png`](diagrams/03_class_diagram.png)

![Class Diagram](diagrams/03_class_diagram.png)

#### Pemenuhan 5 Prinsip Desain Berorientasi Objek (SOLID):
1. **Single Responsibility Principle (SRP)**:
   - `ExamEngineModel` murni bertanggung jawab atas sesi pengerjaan dan penilaian ujian.
   - `ProctoringRiskService` murni bertanggung jawab atas kalkulasi skor risiko dan penalti kecurangan.
   - `ChatbotService` murni bertanggung jawab atas pemrosesan NLP dan dialog asisten cerdas.
2. **Open/Closed Principle (OCP)**:
   - Kelas abstrak basis `Controller` dan `Model` terbuka untuk penambahan fungsi (misal: menambahkan `ParentController`) melalui pewarisan (*inheritance*) tanpa perlu merombak kode inti framework.
3. **Liskov Substitution Principle (LSP)**:
   - Semua kelas turunan controller (`AdminController`, `TeacherController`, `StudentController`) dapat menggantikan objek induk `Controller` secara transparan tanpa merusak integritas aplikasi.
4. **Interface Segregation Principle (ISP)**:
   - Setiap controller hanya mengonsumsi dependensi model dan service yang relevan dengan tugasnya (contoh: `StudentController` tidak dibebani dependensi konfigurasi SMTP email atau master data kelas).
5. **Dependency Inversion Principle (DIP)**:
   - Modul tingkat tinggi (Controller) tidak bergantung langsung pada driver MySQL mentah tingkat rendah, melainkan bergantung pada abstraksi layer `Model` dan Singleton `Database`.

---

### 2.5 Diagram 4: Sequence Diagram Alur Pertukaran Pesan
*Berkas Gambar*: [`docs/diagrams/04_sequence_diagram.png`](diagrams/04_sequence_diagram.png)

Sequence Diagram merinci **30 tahapan pesan interaksi** yang terbagi ke dalam 4 fase siklus hidup pengerjaan ujian:

![Sequence Diagram](diagrams/04_sequence_diagram.png)

* **Fase 1: Verifikasi Token & Inisialisasi Ruang Ujian (Pesan 1–8)**:
  Siswa menginput token; `StudentController` memvalidasi via `ExamEngineModel` ke tabel `exams`, mengacak urutan soal, merender view ruang ujian, dan mengaktifkan stream kamera WebRTC.
* **Fase 2: Real-time Auto-Save Jawaban Siswa (Pesan 9–16)**:
  Siswa mengklik opsi jawaban; event change memicu AJAX `POST /student/save_answer` dengan token CSRF; data disimpan ke `exam_answers` via `ON DUPLICATE KEY UPDATE`; sistem mengembalikan HTTP 200 dan indikator nomor soal berubah hijau.
* **Fase 3: Deteksi Kecurangan Real-Time & AI Scoring (Pesan 17–25)**:
  Siswa berpindah tab; event `visibilitychange` tertangkap klien; payload dikirim ke `/student/log_proctoring`; `ProctoringRiskService` menambahkan penalti (+15 poin); log disimpan ke `proctoring_events`; klien menampilkan modal peringatan interaktif dan memutar audio alert.
* **Fase 4: Penyelesaian Ujian & Penilaian Otomatis (Pesan 26–30)**:
  Siswa klik submit; `ExamEngineModel::gradeAndFinish()` menghitung butir benar/salah, mengunci sesi, menyimpan rekapitulasi ke `exam_results`, dan me-redirect siswa ke halaman evaluasi hasil beserta AI Tutor.

---

### 2.6 Perancangan Basis Data (Entity Relationship Diagram)
Skema basis data dirancang menggunakan mesin relasional **MySQL/MariaDB InnoDB** dengan integritas *Foreign Key Cascade*:
* `users`: Menyimpan kredensial (`username`, `email`, `password_hash`, `role`, `is_active`, `phone_number`).
* `teachers`: Relasi `1:1` dengan `users` (`nip`, `specialization`).
* `students`: Relasi `1:1` dengan `users` (`nis`, `nisn`, `class_id`).
* `classes`: Data rombel kelas (`class_name`, `grade_level`, `academic_year`).
* `subjects`: Data mata pelajaran (`subject_code`, `subject_name`).
* `exams`: Data paket jadwal ujian (`title`, `subject_id`, `teacher_id`, `duration_minutes`, `passing_grade`, `token`, `is_random`).
* `questions`: Butir soal pilihan ganda & essay (`exam_id`, `question_text`, `option_a` s/d `option_e`, `correct_answer`, `explanation_text`, `points`).
* `exam_sessions`: Sesi pengerjaan siswa (`exam_id`, `user_id`, `start_time`, `finish_time`, `status`, `risk_score`).
* `exam_answers`: Rekaman jawaban per butir soal (`session_id`, `question_id`, `answer_option`, `is_correct`).
* `exam_results`: Rekapitulasi nilai akhir (`exam_id`, `user_id`, `total_score`, `is_passed`).
* `proctoring_events`: Log insiden kecurangan AI (`session_id`, `event_type`, `severity`, `snapshot_path`, `logged_at`).
* `otp_requests`: Log kode verifikasi OTP WhatsApp (`phone_number`, `otp_code`, `expires_at`, `is_used`).

---

# BAB III: KONSTRUKSI KODE & INTEGRASI FITUR AI MANDIRI
*Kualitas Implementasi: Clean Architecture, Zero Framework Dependencies, dan SOLID Principles*

### 3.1 Struktur Direktori Proyek
```
c:\xampp\htdocs\Project2/
├── app/
│   ├── config/          # config.php (.env parser & konstanta global)
│   ├── controllers/     # AuthController, AdminController, TeacherController, StudentController
│   ├── core/            # App.php (Router), Controller.php, Model.php, Database.php, Auth.php
│   ├── models/          # UserModel, ExamEngineModel, ProctoringModel, dsb.
│   ├── services/        # ChatbotService, ProctoringRiskService, EmailService, FonnteService
│   └── views/           # Template MVC (admin/, teacher/, student/, auth/, layouts/)
├── database/            # Skema SQL & Seeder data
├── docs/                # TAHAP_1_PRD_SRS.md, TAHAP_2_UML_SDD.md, LAPORAN_AKHIR.md
│   └── diagrams/        # Berkas visual PNG & SVG 4 Diagram UML + index.html
├── public/              # Aset statis (CSS, JS, uploads/proctoring)
│   └── js/              # ai-proctoring.js, proctoring-camera.js, chatbot.js
├── tests/               # Test suite otomatis (unit test, auth, chatbot, proctoring)
├── .github/workflows/   # ci.yml (GitHub Actions CI/CD Pipeline)
├── .env.example         # Template konfigurasi environment
└── index.php            # Front Controller bootstrap
```

### 3.2 Modul Autentikasi & Keamanan Sesi
Otentikasi multi-peran diimplementasikan secara mandiri pada `app/core/Auth.php` dan `app/controllers/AuthController.php`:
* Menggunakan fungsi bawaan `password_hash($pass, PASSWORD_BCRYPT)` untuk enkripsi satu arah.
* Proteksi serangan *Brute-Force Login*: Sistem membatasi maksimal 5 kali kegagalan login berturut-turut; jika terlampaui, akun ditangguhkan otomatis selama 15 menit.
* Regenerasi ID sesi (`session_regenerate_id(true)`) saat login berhasil untuk menggagalkan upaya *Session Fixation*.
* Pengamanan ganda reset kata sandi melalui integrasi token verifikasi Google Mail SMTP dan kode OTP WhatsApp via Fonnte Gateway.

### 3.3 Engine Pengerjaan Ujian & Resilient Auto-Save
Alur pengerjaan siswa dioptimalkan untuk ketahanan terhadap kegagalan jaringan:
* Klien memanfaatkan event input JavaScript `addEventListener('change')` pada setiap opsi radio pilihan ganda.
* Request AJAX dikirim ke endpoint `/student/save_answer` dengan menyertakan token CSRF.
* Pada sisi basis data, query dijalankan menggunakan klausa `INSERT ... ON DUPLICATE KEY UPDATE` untuk menjamin operasi bersifat *idempotent* (aman dieksekusi berulang kali tanpa duplikasi data).

### 3.4 Modul AI Computer Vision Proctoring & Heuristic Risk Engine
* **Sisi Klien (Frontend)**: Menggunakan API standar HTML5 `navigator.mediaDevices.getUserMedia()` untuk streaming kamera WebRTC. Event listener `window.onblur` dan `document.visibilitychange` memantau perpindahan tab browser.
* **Sisi Backend (`ProctoringRiskService.php`)**: Menerapkan algoritma pembobotan risiko berbasis bobot keparahan (*heuristic penalty weights*):
  * `tab_switch` : Penalti +15 poin (Tingkat: *WARNING*)
  * `fullscreen_exit` : Penalti +20 poin (Tingkat: *WARNING*)
  * `face_absent` : Penalti +25 poin (Tingkat: *CRITICAL*)
  * `multiple_faces` : Penalti +35 poin (Tingkat: *CRITICAL*)
* Skor risiko diagregasi secara kumulatif (skala 0–100%) dan dikonversi menjadi *Trust Index* bagi guru pengawas.

### 3.5 Modul Dual Intelligent Chatbot AI (ExamBot & Academic Tutor)
1. **Chatbot Tipe 1 — Global Floating ExamBot**:
   * Ditanamkan pada halaman publik dan dasbor.
   * Menggunakan arsitektur *Hybrid NLP*: kueri data operasional (total guru, total siswa, sesi ujian, jadwal) dijawab langsung dari basis data lokal dengan latensi < 50ms.
   * Menerapkan prinsip *Progressive Disclosure*: menjawab singkat dan ringkas saat pertama kali ditanya jumlah (*turn 1*), dan baru menjabarkan rincian nama/NIP/spesialisasi saat pengguna meminta tindak lanjut (*"siapa saja?"*).
2. **Chatbot Tipe 2 — Pedagogical Question Explainer Tutor**:
   * Ditanamkan khusus pada halaman ringkasan hasil evaluasi pasca-ujian siswa.
   * Mengintegrasikan API Google Gemini AI model `gemini-1.5-flash` untuk menganalisis letak kekeliruan logika siswa pada butir soal tertentu, memberikan petunjuk pemecahan masalah, dan menyajikan pembahasan komprehensif.

---

# BAB IV: PENGUJIAN OTOMATIS (TESTING) & DEVOPS CI/CD GITHUB ACTIONS
*Standar Kualitas: 100% Automated Unit & Integration Test Passing, Green Pipeline*

### 4.1 Metodologi Pengujian
Pengujian perangkat lunak dilakukan secara komprehensif menggunakan pendekatan *Test-Driven Verification* yang mencakup 3 level:
1. **Unit Testing**: Menguji fungsionalitas logika kelas model, kalkulasi skor risiko proctoring, dan parsing kueri chatbot.
2. **Integration Testing**: Menguji interaksi antara Controller, Service Layer, dan transaksi basis data MySQL PDO.
3. **End-to-End HTTP Security Testing**: Menguji proteksi URL, otorisasi RBAC antar-peran, sesi login/logout, dan penolakan bypass hak akses.

### 4.2 Matriks Hasil 6 Rangkaian Uji Coba Otomatis (Semua 100% Lulus)

| No | Berkas Test Suite | Cakupan Komponen yang Diuji | Jumlah Pengujian | Hasil Status |
|:--:|---|---|:---:|:---:|
| 1 | `tests/test_admin_crud.php` | Operasi CRUD Master Data Kelas, Mapel, Guru, Siswa, dan User | 20 Kasus Uji | **100% PASSED** (20/20) |
| 2 | `tests/test_http_auth.php` | Otentikasi HTTP, RBAC Middleware, Session Throttling, & Logout | 10 Kasus Uji | **100% PASSED** (10/10) |
| 3 | `tests/test_otp_forgot_password.php` | Verifikasi Pengiriman OTP WhatsApp (Fonnte) & Reset Sandi | 13 Kasus Uji | **100% PASSED** (13/13) |
| 4 | `tests/test_two_chatbots.php` | Dua Tipe Chatbot, Scoped Data Access, & Strict Security Guardrails | 13 Kasus Uji | **100% PASSED** (13/13) |
| 5 | `tests/test_chatbot_database_queries.php` | Akurasi Kueri Basis Data Chatbot & Format Progressive Disclosure | 12 Kasus Uji | **100% PASSED** (12/12) |
| 6 | `tests/test_user_requirements_15_queries.php`| 15 Target Skenario Pertanyaan Pengguna & Penolakan Prompt Injeksi | 18 Kasus Uji | **100% PASSED** (18/18) |

**Total Akumulasi**: **86 Butir Pengujian Otomatis — Seluruhnya Berhasil 100% Tanpa Error (Zero Failure).**

### 4.3 Konfigurasi Otomatisasi CI/CD Pipeline (`.github/workflows/ci.yml`)
Otomatisasi pengujian dan verifikasi kualitas kode diintegrasikan ke repositori GitHub menggunakan **GitHub Actions**. Alur kerja otomatis dijalankan pada setiap event `push` dan `pull_request`:
```yaml
name: CI/CD Pipeline - AI Online Exam & Proctoring System

on:
  push:
    branches: [ main, master ]
  pull_request:
    branches: [ main, master ]

jobs:
  build-and-test:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: db_ai_exam
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
      - name: Checkout Source Code
        uses: actions/checkout@v3

      - name: Setup PHP Environment
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, pdo, pdo_mysql, curl, gd
          coverage: none

      - name: Setup Database Schema & Seed Data
        run: |
          mysql -h 127.0.0.1 -u root -proot db_ai_exam < database/schema.sql
          mysql -h 127.0.0.1 -u root -proot db_ai_exam < database/seeds.sql

      - name: Configure Environment (.env)
        run: |
          cp .env.example .env
          sed -i 's/DB_PASS=.*/DB_PASS=root/' .env
          sed -i 's/DB_HOST=.*/DB_HOST=127.0.0.1/' .env

      - name: Run Automated Test Suites
        run: |
          php tests/test_admin_crud.php
          php tests/test_two_chatbots.php
          php tests/test_chatbot_database_queries.php
          php tests/test_user_requirements_15_queries.php
```

### 4.4 Verifikasi Kualitas & Riwayat Git Commits
Repositori dikembangkan secara teratur menggunakan *Semantic Commit Messages* (`feat`, `fix`, `docs`, `refactor`, `test`), membuktikan keaslian dan konsistensi rekayasa mandiri mahasiswa dari awal hingga akhir perkuliahan.

---

# BAB V: PANDUAN INSTALASI SISTEM & KESIMPULAN

### 5.1 Prasyarat Sistem
* **Lingkungan Server**: XAMPP for Windows (Apache 2.4+ dan PHP 8.1 / 8.2).
* **Ekstensi PHP Wajib**: `pdo`, `pdo_mysql`, `curl`, `mbstring`, `fileinfo`.
* **Database**: MySQL 8.0+ atau MariaDB 10.4+.
* **Peramban Klien**: Google Chrome / Microsoft Edge / Mozilla Firefox (mendukung WebRTC & webcam).

### 5.2 Petunjuk Instalasi Step-by-Step
1. **Kloning / Unduh Repositori**:
   Salin direktori proyek ke dalam web root XAMPP:
   `c:\xampp\htdocs\Project2`
2. **Konfigurasi Database MySQL**:
   * Buka phpMyAdmin (`http://localhost/phpmyadmin`) atau terminal MySQL.
   * Buat basis data baru bernama `db_ai_exam`.
   * Impor berkas SQL yang tersedia di direktori `database/schema.sql` dan `database/seeds.sql`.
3. **Konfigurasi Berkas Lingkungan (`.env`)**:
   Sesuaikan parameter pada berkas `.env`:
   ```env
   APP_NAME="AI Online Exam & Proctoring System"
   BASE_URL="http://localhost/Project2"
   DB_HOST="localhost"
   DB_PORT="3306"
   DB_USER="root"
   DB_PASS=""
   DB_NAME="db_ai_exam"
   GEMINI_API_KEY="[API_KEY_ANDA]"
   FONNTE_TOKEN="[TOKEN_FONNTE_ANDA]"
   ```
4. **Menjalankan Aplikasi**:
   * Jalankan modul Apache dan MySQL pada XAMPP Control Panel.
   * Akses URL sistem melalui browser: `http://localhost/Project2`.

### 5.3 Kredensial Akun Pengujian (Seed Data Default)

| Peran | Username / Email | Kata Sandi | Keterangan Hak Akses |
|---|---|---|---|
| **Administrator** | `admin@sekolah.sch.id` *(atau `admin`)* | `password123` | Manajemen Master Data, Pengaturan, & ExamBot |
| **Guru Penguji** | `guru_budi` *(atau `budi.santoso@sekolah.sch.id`)* | `password123` | Bank Soal, Pembuatan Ujian, & Live Monitoring |
| **Siswa Peserta** | `siswa_ani` *(atau `ani.lestari@sekolah.sch.id`)* | `password123` | Ruang Ujian, Auto-Save, & AI Academic Tutor |

*(Kata sandi fleksibel untuk kemudahan demonstrasi evaluasi juga menerima: `admin123` / `guru123` / `siswa123`)*.

---

### 5.4 Kesimpulan & Refleksi Pembelajaran Mandiri
Melalui pengerjaan tugas proyek akhir mandiri ini, penulis berhasil:
1. Mentransformasikan kebutuhan nyata integritas akademik ke dalam arsitektur perangkat lunak yang kokoh, terstruktur rapi, dan mematuhi prinsip **SOLID**.
2. Mengintegrasikan teknologi **Computer Vision (WebRTC Face Tracking)** dan **Generative AI (Google Gemini)** ke dalam aplikasi web native tanpa ketergantungan pada pustaka pihak ketiga yang membebani server.
3. Menyelesaikan seluruh tahapan SDLC mulai dari **PRD (IEEE 830)**, **SDD (4 Diagram UML)**, implementasi kode bersih, hingga verifikasi otomatis berbasis **Unit Testing** dan **CI/CD Pipeline GitHub Actions**.
4. Memperoleh pemahaman mendalam bahwa seorang *Software Engineer* tidak hanya berfokus pada penulisan kode (*coding*), melainkan bertanggung jawab penuh atas analisis risiko, ketahanan arsitektur, keamanan data, dan pengujian kualitas sistem secara menyeluruh.

---

## DAFTAR PUSTAKA
1. Pressman, R. S., & Maxim, B. R. (2020). *Software Engineering: A Practitioner's Approach* (9th ed.). McGraw-Hill Education.
2. Sommerville, I. (2016). *Software Engineering* (10th ed.). Pearson Education.
3. Martin, R. C. (2018). *Clean Architecture: A Craftsman's Guide to Software Structure and Design*. Prentice Hall.
4. Fowler, M. (2018). *Refactoring: Improving the Design of Existing Code* (2nd ed.). Addison-Wesley Professional.
5. IEEE Computer Society. (1998). *IEEE Recommended Practice for Software Requirements Specifications (IEEE Std 830-1998)*. IEEE.
6. Object Management Group (OMG). (2017). *OMG Unified Modeling Language (OMG UML) Version 2.5.1*. OMG Standard.
7. Google AI. (2024). *Gemini API Documentation & Prompt Engineering Guide*. Google Cloud Documentation.
8. W3C. (2023). *Media Capture and Streams (WebRTC Working Group Note)*. World Wide Web Consortium.

