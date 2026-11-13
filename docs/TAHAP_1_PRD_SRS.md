# DOKUMEN TAHAP 1: INISIASI & PRODUCT REQUIREMENTS DOCUMENT (PRD) / SOFTWARE REQUIREMENTS SPECIFICATION (SRS)
## STANDAR IEEE 830 / ISO 29148 — KURIKULUM OUTCOME-BASED EDUCATION (OBE)

---

### IDENTITAS PROYEK & AKADEMIK
* **Perguruan Tinggi**: STMIK IKMI Cirebon
* **Program Studi**: S1 Teknik Informatika
* **Mata Kuliah / Kode**: Rekayasa Perangkat Lunak (TIKB2503021) / 4 SKS (Semester 3)
* **Dosen Pengampu**: Yudhistira Arie Wijaya, M.Kom., CDS.
* **Tema Topik Kelompok**: *E-Learning & Smart Education AI*
* **Judul Proyek Mandiri**: **AI Online Exam & Proctoring System with Dual Intelligent Chatbot Assistance**
* **Sifat Pengerjaan**: Mandiri / Perorangan (100% Individual Ownership & Full End-to-End Build)
* **Nama Mahasiswa**: [Nama Mahasiswa]
* **NIM**: [NIM Mahasiswa]
* **Kelompok**: [Nomor/Nama Kelompok]
* **Versi Dokumen**: 1.0.0 (Tahap 1 - Inisiasi & Analisis Kebutuhan)
* **Tanggal Penyusunan**: November 2026

---

## 1. PENDAHULUAN & LATAR BELAKANG MASALAH

### 1.1 Latar Belakang Masalah (Problem Statement)
Transformasi digital dalam dunia pendidikan telah mendorong adopsi sistem ujian berbasis komputer (*Computer-Based Test* / CBT) secara luas di berbagai jenjang sekolah dan perguruan tinggi. Namun, transisi dari ujian tatap muka konvensional ke platform ujian *online* tanpa pengawasan fisik langsung memunculkan krisis integritas akademik yang signifikan:

1. **Tingginya Potensi Kecurangan (*Academic Dishonesty*)**:
   Peserta ujian dengan mudah dapat membuka tab/jendela lain untuk mencari jawaban di mesin pencari, bertukar pesan instan, atau bahkan digantikan oleh pihak lain (*joki ujian*) saat kamera tidak dipantau secara ketat.
2. **Keterbatasan Pengawasan Manual Guru (*Proctoring Fatigue*)**:
   Dalam kelas berukuran besar (30–40 siswa), pengawas ujian manusia tidak mampu mengamati layar dan gerak-gerik seluruh peserta secara simultan dan konsisten selama 60–120 menit durasi ujian.
3. **Ketiadaan Umpan Balik Pedagogis Pasca-Ujian**:
   Sebagian besar platform ujian konvensional hanya menampilkan nilai mentah (*raw score*) tanpa memberikan bimbingan atau penjelasan konsep mendalam mengenai butir soal yang dijawab salah oleh siswa. Hal ini menurunkan nilai edukasi dari proses asesmen itu sendiri.
4. **Resistensi terhadap Gangguan Jaringan & Latensi**:
   Banyak sistem CBT bergantung penuh pada koneksi stabil; jika jaringan mengalami degradasi sesaat atau layanan AI eksternal *timeout*, sesi ujian siswa terganggu dan berisiko kehilangan data jawaban.

### 1.2 Pernyataan Nilai & Solusi (Value Proposition)
Untuk menyelesaikan permasalahan di atas, proyek mandiri ini merancang dan membangun **AI Online Exam & Proctoring System**, yaitu platform evaluasi pembelajaran komprehensif berbasis web native (arsitektur Model-View-Controller) yang menanamkan dua pilar Kecerdasan Artifisial (AI) terpadu:

1. **AI Computer Vision Proctoring & Risk Engine Real-Time**:
   Pengawasan aktif melalui *webcam* menggunakan model *computer vision* di sisi klien (*client-side face tracking*) yang mendeteksi wajah hilang, wajah ganda, indikasi wajah berpaling, penutupan kamera, serta *tab-switch listener*. Pelanggaran diagregasi secara matematis oleh algoritma *Proctoring Risk Service* di sisi *backend* menjadi *Risk Score* (0–100%) dan *Trust Index* objektif untuk guru.
2. **Dual Intelligent AI Chatbot (ExamBot & Academic Tutor)**:
   * **Chatbot Tipe 1 (Global Floating ExamBot)**: Asisten interaktif 24/7 di beranda dan dasbor untuk menjawab pertanyaan statistik operasional (jadwal ujian, jumlah peserta, rombel kelas, aturan proctoring, tata tertib) berbasis data nyata (*database of record*).
   * **Chatbot Tipe 2 (Pedagogical Question Explainer Tutor)**: Tutor cerdas di halaman evaluasi pasca-ujian yang membedah konsep butir soal, menganalisis kesalahan opsi yang dipilih siswa, serta memberikan langkah penyelesaian edukatif sesuai kurikulum.

---

## 2. TUJUAN & RUANG LINGKUP PRODUK (PRODUCT SCOPE)

### 2.1 Tujuan Produk
1. Membangun sistem ujian *online* multi-peran (Super Admin, Guru Penguji, dan Siswa) yang aman, responsif, dan mematuhi prinsip rekayasa perangkat lunak modern (*Clean Code & SOLID*).
2. Mengotomatisasi pemantauan integritas ujian menggunakan algoritma AI Proctoring dan *Heuristic Risk Scoring*.
3. Menyediakan dasbor *Live Monitoring* terpusat bagi guru untuk meninjau status pengerjaan, skor risiko, dan *snapshot* bukti pelanggaran secara *real-time*.
4. Mengintegrasikan asisten AI dialogis yang berwawasan data akademik dan memberikan umpan balik evaluasi formatif kepada siswa.
5. Memenuhi seluruh rubrik penilaian *Outcome-Based Education* (OBE) mata kuliah Rekayasa Perangkat Lunak melalui siklus lengkap SDLC (PRD → UML SDD → Kode Bersih → Unit Testing Otomatis → CI/CD Pipeline GitHub Actions).

### 2.2 Batasan Ruang Lingkup Sistem (System Boundaries)
* **Lingkup Pengguna**: Aplikasi dirancang untuk institusi pendidikan tingkat menengah/atas dengan 3 peran pengguna utama terotentikasi (*Admin, Teacher, Student*) serta peran tamu (*Guest*) pada landing page.
* **Lingkup Pelaksanaan Ujian**: Ujian diselenggarakan dalam lingkungan peramban modern (*browser-based*) dengan syarat izin akses kamera (*webcam permission*) dan mode layar penuh (*fullscreen mode*).
* **Lingkup Komputasi AI**: Deteksi wajah awal diproses langsung pada peramban siswa melalui WebRTC dan inferensi lokal (*lightweight computer vision*), sedangkan kalkulasi indeks integritas dan manajemen percakapan cerdas dikelola oleh *backend engine* dengan *fallback* lokal instan.

---

## 3. PENGGUNA SASARAN & USER PERSONA

| Peran (Role) | Karakteristik & Deskripsi Pengguna | Tanggung Jawab Utama dalam Sistem |
| :--- | :--- | :--- |
| **Super Administrator (Admin)** | Staf pengelola IT dan kurikulum sekolah yang menguasai manajemen data master dan konfigurasi server. | • Mengelola akun pengguna (Admin, Guru, Siswa)<br>• Mengelola master data Rombel Kelas & Mata Pelajaran<br>• Mengonfigurasi ambang batas (*threshold*) bobot AI Proctoring<br>• Memantau log audit keamanan dan sesi pengguna |
| **Guru Penguji (Teacher)** | Tenaga pendidik yang menyusun instrumen evaluasi akademik dan mengawasi jalannya asesmen. | • Menyusun Bank Soal (Pilihan Ganda & Essay beserta kunci dan pembahasan)<br>• Membuat dan menjadwalkan sesi ujian (durasi, KKM, acak soal, toggle AI Proctoring)<br>• Mengawasi *Live Monitoring Dashboard* peserta secara *real-time*<br>• Menilai jawaban essay dan menerbitkan rekapitulasi nilai |
| **Siswa / Peserta Didik (Student)** | Peserta ujian yang mengerjakan asesmen secara mandiri sesuai jadwal yang ditentukan. | • Menjalani verifikasi kesiapan sistem dan webcam sebelum ujian<br>• Mengerjakan soal ujian dalam antarmuka terproteksi anti-curang<br>• Melakukan submit penugasan ujian<br>• Membuka riwayat nilai dan berdiskusi dengan AI Tutor untuk pembahasan butir soal |
| **Tamu / Publik (Guest)** | Calon siswa, orang tua, atau penguji yang mengakses portal publik landing page. | • Mengakses informasi ringkasan sistem dan statistik platform<br>• Berinteraksi dengan ExamBot untuk eksplorasi jadwal umum dan panduan proctoring<br>• Mengakses form login menuju portal masing-masing peran |

---

## 4. SPESIFIKASI KEBUTUHAN FUNGSIONAL (FUNCTIONAL REQUIREMENTS)

Kebutuhan fungsional disusun menggunakan format **User Story** (*As a... I want to... So that...*) dan **Acceptance Criteria** berbasis skenario *Gherkin* (*Given - When - Then*).

### Modul 1: Autentikasi, Manajemen Sesi & Kontrol Akses Berbasis Peran (RBAC)

#### US-01: Autentikasi Aman Multi-Peran
* **User Story**: *Sebagai Pengguna (Admin, Guru, Siswa), saya ingin masuk ke dalam sistem menggunakan identifier (email/username) dan kata sandi terenkripsi, agar saya dapat mengakses portal dasbor sesuai peran saya secara aman.*
* **Acceptance Criteria**:
  * **Skenario 1.1 (Login Berhasil)**:
    * *Given* akun pengguna terdaftar aktif (`is_active = 1`) di tabel `users` dengan hash BCRYPT yang valid.
    * *When* pengguna memasukkan kredensial yang cocok dan mengirimkan formulir login dengan CSRF token valid.
    * *Then* sesi login disimpan (`$_SESSION['ai_exam_user']`), ID sesi diperbarui (`session_regenerate_id(true)`), dan pengguna dialihkan ke dasbor perannya masing-masing (Admin ke `/admin/dashboard`, Guru ke `/teacher/dashboard`, Siswa ke `/student/dashboard`).
  * **Skenario 1.2 (Database Offline / Down)**:
    * *Given* modul database MySQL di server terputus atau tidak aktif.
    * *When* pengguna mengakses atau mengirim formulir login.
    * *Then* sistem menampilkan pesan peringatan spesifik: *"Gagal terhubung ke database. Pastikan modul MySQL di XAMPP Control Panel sudah dalam status running (START)"*, bukan menyalahkan username/password pengguna.
  * **Skenario 1.3 (Perlindungan Brute-Force Throttling)**:
    * *Given* pengguna gagal memasukkan kata sandi sebanyak 5 kali berturut-turut pada akun yang sama.
    * *When* pengguna mencoba login kembali untuk ke-6 kalinya.
    * *Then* sistem menolak percobaan login dan mengunci sementara akses identifier tersebut selama 15 menit dengan pemberitahuan sisa durasi penguncian.

#### US-02: Isolasi Hak Akses Halaman (Role-Based Access Control / RBAC Guard)
* **User Story**: *Sebagai Arsitek Keamanan Sistem, saya ingin membatasi setiap URL controller agar hanya dapat diakses oleh peran yang berwenang, sehingga integritas data terjamin.*
* **Acceptance Criteria**:
  * **Skenario 2.1 (Siswa Mengakses Rute Admin/Guru)**:
    * *Given* pengguna terotentikasi dengan peran `student`.
    * *When* siswa mencoba mengakses URL terproteksi seperti `/admin/teachers` atau `/teacher/examCreate`.
    * *Then* sistem mencegat permintaan (*middleware guard*), menghentikan eksekusi, memicu pesan kilat penolakan hak akses (*flash warning*), dan mengalihkan siswa kembali ke `/student/dashboard`.

---

### Modul 2: Pengelolaan Master Data Akademik (Admin Module)

#### US-03: Manajemen CRUD Rombel Kelas & Siswa
* **User Story**: *Sebagai Administrator, saya ingin membuat, memperbarui, mencari, dan menghapus data kelas dan siswa secara terintegrasi, agar data peserta ujian terkelola rapi.*
* **Acceptance Criteria**:
  * *Given* data nama kelas, tingkat (*level*), dan tahun ajaran valid.
  * *When* Admin menyimpan data kelas baru melalui `/admin/classes/store`.
  * *Then* data tersimpan di tabel `classes` dan dapat diasosiasikan dengan akun siswa melalui relasi `foreign key` (`students.class_id`).

#### US-04: Manajemen Mata Pelajaran & Penugasan Guru
* **User Story**: *Sebagai Administrator, saya ingin mengelola data mata pelajaran dengan kode unik, agar guru dapat mengampu ujian sesuai kompetensinya.*
* **Acceptance Criteria**:
  * *Given* kode mata pelajaran bersifat unik di tabel `subjects`.
  * *When* Admin memasukkan kode mata pelajaran yang telah ada sebelumnya.
  * *Then* sistem memvalidasi dan menolak entri duplikat dengan pesan validasi kode mata pelajaran sudah digunakan.

---

### Modul 3: Bank Soal & Manajemen Sesi Ujian (Teacher Module)

#### US-05: Pembuatan Bank Soal Pilihan Ganda & Essay
* **User Story**: *Sebagai Guru Penguji, saya ingin menyusun butir soal pilihan ganda (dengan pilihan A–E) dan essay, menyertakan kunci jawaban resmi, dan menuliskan pembahasan konsep mendalam.*
* **Acceptance Criteria**:
  * *Given* Guru mengisi teks soal, tipe soal, tingkat kesulitan (*easy/medium/hard*), kunci jawaban, dan teks pembahasan konsep.
  * *When* soal disimpan ke tabel `questions` dan `question_options`.
  * *Then* soal tersimpan dengan referensi `teacher_id` pengampu dan siap dikelompokkan ke dalam paket ujian.

#### US-06: Penjadwalan & Konfigurasi Ujian Online
* **User Story**: *Sebagai Guru Penguji, saya ingin membuat sesi ujian dengan mengatur judul, mata pelajaran, waktu mulai, durasi pengerjaan, nilai KKM, opsi pengacakan urutan soal, serta mengaktifkan mode AI Proctoring.*
* **Acceptance Criteria**:
  * *Given* parameter sesi ujian diisi lengkap di `/teacher/examCreate`.
  * *When* status ujian diset ke `published`.
  * *Then* ujian terdaftar di tabel `exams`, peserta di rombel kelas terkait otomatis ditugaskan ke tabel `exam_participants`, dan sesi ujian muncul pada jadwal siswa.

---

### Modul 4: Mesin Pengerjaan Ujian Siswa (Student Exam Engine)

#### US-07: Verifikasi Kamera & Ruang Ujian Terproteksi
* **User Story**: *Sebagai Siswa, saya ingin menjalani proses pre-check kamera dan masuk ke ruang ujian layar penuh yang bersih dari distraksi, agar ujian berjalan adil.*
* **Acceptance Criteria**:
  * *Given* siswa membuka ujian yang berstatus proctored.
  * *When* siswa mengklik tombol "Mulai Ujian".
  * *Then* sistem meminta izin akses *webcam*, memaksa peramban masuk ke mode *fullscreen*, menyalakan *countdown timer* pengerjaan, dan menampilkan *widget webcam preview* di pojok layar siswa.

#### US-08: Penyimpanan Jawaban Otomatis (*Auto-Save*) & Navigasi Soal
* **User Story**: *Sebagai Siswa, saya ingin jawaban saya tersimpan secara otomatis setiap kali saya memilih opsi, agar jawaban tidak hilang jika koneksi internet terputus.*
* **Acceptance Criteria**:
  * *Given* siswa sedang mengerjakan butir soal pilihan ganda atau essay.
  * *When* siswa memilih radio button jawaban atau mengetik pada textarea essay.
  * *Then* skrip mengirimkan request *asynchronous* (AJAX POST) ke `/student/saveAnswer` untuk mencatat jawaban ke tabel `exam_answers` secara instan, dan nomor soal pada palet navigasi berubah warna menjadi hijau (terjawab).

---

### Modul 5: AI Computer Vision Proctoring & Risk Calculation Engine

#### US-09: Deteksi Pelanggaran Real-Time Sisi Klien
* **User Story**: *Sebagai Sistem Pengawas AI, saya ingin mendeteksi indikasi kecurangan siswa selama ujian berlangsung secara otomatis melalui kamera dan status jendela browser.*
* **Acceptance Criteria**:
  * **Skenario 9.1 (Wajah Tidak Terdeteksi / Wajah Hilang)**:
    * *Given* siswa meninggalkan area tangkapan webcam selama > 5 detik.
    * *When* modul *face-detector* tidak menemukan kontur wajah.
    * *Then* sistem mencatat event pelanggaran `FACE_NOT_DETECTED` ke tabel `proctoring_events` disertai *snapshot base64*.
  * **Skenario 9.2 (Wajah Ganda / Ada Orang Lain)**:
    * *Given* terdapat lebih dari 1 wajah terdeteksi pada *frame* video.
    * *When* algoritma mengidentifikasi 2 atau lebih *bounding boxes* wajah.
    * *Then* event `MULTIPLE_FACE` dicatat dengan tingkat keparahan tinggi (*CRITICAL*).
  * **Skenario 9.3 (Berpindah Tab / Membuka Aplikasi Lain)**:
    * *Given* siswa meminimalkan peramban atau berpindah tab browser.
    * *When* event JavaScript `document.visibilitychange` bernilai `hidden` atau `window.blur` terpicu.
    * *Then* event `TAB_SWITCH` langsung dicatat ke server disertai peringatan dialog pada layar siswa.

#### US-10: Perhitungan Skor Risiko Integritas (*Proctoring Risk Service*)
* **User Story**: *Sebagai Guru Penguji, saya ingin sistem menghitung agregasi nilai risiko (*Risk Score*) dan indeks kepercayaan (*Trust Index*) secara objektif untuk setiap peserta ujian.*
* **Acceptance Criteria**:
  * *Given* seluruh pelanggaran ujian tercatat di tabel `proctoring_events` dengan bobot yang dapat dikonfigurasi:
    * `FACE_CHANGED`: 50 poin
    * `MULTIPLE_FACE`: 40 poin
    * `CAMERA_DISCONNECTED`: 20 poin
    * `SUSPICIOUS_GAZE`: 15 poin
    * `CAMERA_BLOCKED`: 15 poin
    * `TAB_SWITCH`: 10 poin
    * `FACE_NOT_DETECTED`: 10 poin
  * *When* fungsi `ProctoringRiskService::calculateRisk($examId, $studentId)` dieksekusi.
  * *Then* skor diakumulasikan (dibatasi rentang 0–100), status tingkat risiko dikelompokkan ke dalam kategori:
    * **0 – 29**: `NORMAL` (Hijau)
    * **30 – 59**: `WARNING` (Kuning)
    * **60 – 100**: `HIGH RISK` (Merah)
    Dan nilai *Trust Index* dihitung otomatis sebagai: $\text{Trust Index} = 100 - \text{Risk Score}$.

---

### Modul 6: Dasbor Live Monitoring Guru (Teacher Monitoring Module)

#### US-11: Pemantauan Peserta Ujian Real-Time
* **User Story**: *Sebagai Guru Penguji, saya ingin melihat kisi-kisi pemantauan kartu peserta ujian yang memperlihatkan status pengerjaan, skor risiko, sisa waktu, dan log pelanggaran terkini.*
* **Acceptance Criteria**:
  * *Given* sesi ujian sedang berlangsung (*running*).
  * *When* guru membuka `/teacher/monitoring/{exam_id}`.
  * *Then* sistem memperbarui data pemantauan setiap beberapa detik via AJAX polling, menampilkan lencana warna risiko masing-masing siswa, dan mengizinkan guru mengklik *modal audit trail* untuk melihat rekaman foto pelanggaran siswa.

---

### Modul 7: Evaluasi Hasil & AI Tutor Pembahasan Soal (AI Question Explainer)

#### US-12: Rekapitulasi Hasil & Penilaian Otomatis
* **User Story**: *Sebagai Siswa, saya ingin melihat ringkasan skor hasil ujian saya segera setelah submit, beserta status kelulusan terhadap ambang batas KKM.*
* **Acceptance Criteria**:
  * *Given* siswa menyelesaikan seluruh soal dan mengonfirmasi submit ujian.
  * *When* sistem menghitung skor akhir (otomatis untuk pilihan ganda, pending untuk essay).
  * *Then* data disimpan di tabel `exam_results` dan halaman rekap nilai menampilkan skor, jumlah jawaban benar/salah/kosong, dan status LULUS / TIDAK LULUS.

#### US-13: AI Academic Tutor Pembahasan Butir Soal Pasca-Ujian
* **User Story**: *Sebagai Siswa, saya ingin membuka pembahasan butir soal dan berkonsultasi dengan AI Tutor untuk memahami konsep materi dan alasan mengapa jawaban saya salah.*
* **Acceptance Criteria**:
  * *Given* siswa membuka halaman evaluasi soal di `/student/examReview/{id}`.
  * *When* siswa membuka *widget* AI Question Explainer pada nomor soal tertentu.
  * *Then* AI Tutor merespons dengan mematuhi sumber kebenaran mutlak resmi database: menampilkan teks soal, opsi jawaban, analisis letak kekeliruan jawaban siswa, serta uraian langkah konsep yang runut dan edukatif.

---

### Modul 8: Chatbot Percakapan Cerdas (Global Floating ExamBot)

#### US-14: Asisten Virtual Akademik 24/7 di Beranda & Portal
* **User Story**: *Sebagai Pengguna (Siswa, Guru, Admin, atau Tamu), saya ingin bertanya mengenai data jadwal ujian, jumlah peserta, rombel kelas, nama guru pengampu, maupun tata tertib ujian kepada ExamBot.*
* **Acceptance Criteria**:
  * **Skenario 14.1 (Pertanyaan Data Peserta)**:
    * *When* pengguna mengetik *"ada berapa peserta"* atau *"total peserta"*.
    * *Then* ExamBot merespons seketika (< 100 ms) dengan rincian ganda dari database: Total penugasan ujian (submitted, in progress, not started) dan total peserta didik terdaftar per rombel kelas.
  * **Skenario 14.2 (Pertanyaan Total Sesi Ujian)**:
    * *When* pengguna mengetik *"ada berapa total ujian"* atau *"total ujian"*.
    * *Then* ExamBot merespons dengan total 15 sesi ujian terdaftar beserta judul ujian, mata pelajaran, durasi pengerjaan, dan nilai KKM.
  * **Skenario 14.3 (Strict Guardrails & Keamanan)**:
    * *When* pengguna mencoba mengirimkan permintaan di luar lingkup (seperti meminta koding Python umum, resep masakan, manipulasi prompt, atau mencoba membocorkan kunci rahasia API/.env).
    * *Then* ExamBot menolak secara tegas dan santun melalui *Guardrails Layer* serta mengarahkan kembali ke topik sistem ujian online.

---

## 5. SPESIFIKASI KEBUTUHAN NON-FUNGSIONAL (NON-FUNCTIONAL REQUIREMENTS)

Kebutuhan non-fungsional disusun mengacu pada standar kualitas perangkat lunak **ISO/IEC 25010** dan kerangka **FURPS+**:

### 5.1 Kinerja (Performance Efficiency)
* **NFR-P1 (Waktu Respons Halaman)**: Waktu pemuatan halaman awal (*Initial Page Load*) pada jaringan lokal rata-rata $\le 1.5$ detik.
* **NFR-P2 (Latensi Chatbot Real-Time)**: Endpoint API Chatbot lokal (`/home/chat`) merespons pertanyaan berbasis basis data dalam waktu $\le 100$ milidetik (ms).
* **NFR-P3 (Skalabilitas Pemantauan Polling)**: *Live Monitoring polling* dioptimalkan menggunakan kueri SQL indeks komposit sehingga mampu menangani 50 peserta per rombel tanpa degradasi beban CPU server $> 30\%$.

### 5.2 Keamanan Perangkat Lunak (Security)
* **NFR-S1 (Hashing Kata Sandi)**: Seluruh kata sandi pengguna wajib di-hash menggunakan algoritma **BCRYPT** (`PASSWORD_BCRYPT` dengan cost standar PHP) dan tidak boleh disimpan dalam format *plain text*.
* **NFR-S2 (Perlindungan Injeksi SQL)**: 100% interaksi database wajib menggunakan *PDO Prepared Statements* dengan *parameter binding* ketat.
* **NFR-S3 (Perlindungan Pemalsuan Permintaan Antar-Situs / CSRF)**: Seluruh formulir HTTP POST/PUT/DELETE wajib menyertakan token CSRF unik per sesi yang divalidasi oleh `Csrf::verify()`.
* **NFR-S4 (Pencegahan Cross-Site Scripting / XSS)**: Seluruh luaran teks dari database dan input pengguna wajib melalui *HTML entity escaping* via fungsi global `e()` (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`).
* **NFR-S5 (Hardened Session Cookies)**: Konfigurasi sesi menerapkan atribut `HttpOnly`, `SameSite=Lax`, dan `session.use_strict_mode = 1` untuk mencegah pembajakan sesi (*session hijacking*).

### 5.3 Keandalan & Toleransi Kesalahan (Reliability & Fault Tolerance)
* **NFR-R1 (Graceful Database Degradation)**: Jika service MySQL offline atau koneksi terputus, sistem wajib menangani *exception* secara elegan dan menampilkan pesan diagnostik yang informatif, bukan *fatal uncaught crash error*.
* **NFR-R2 (Penyimpanan Status Sesi Ujian Klien)**: State jawaban ujian siswa dicatat berkala di penyimpanan lokal (*local state*) dan database server, sehingga jika terjadi pemadaman listrik atau reload peramban, sesi dapat dilanjutkan dari sisa waktu terakhir.
* **NFR-R3 (Dual-Engine Chatbot Fallback)**: Sistem AI Chatbot memiliki arsitektur *failover* bertingkat; jika API eksternal mengalami kendala atau kuota habis, mesin pengetahuan lokal (*Local Resilient Engine*) mengambil alih 100% fungsionalitas tanpa jeda.

### 5.4 Kemudahan Penggunaan & Antarmuka (Usability & UI/UX)
* **NFR-U1 (Desain Responsif Mobile & Desktop)**: Antarmuka dibangun di atas Bootstrap 5 dengan desain responsif penuh (layar desktop, tablet, dan smartphone).
* **NFR-U2 (Aksesibilitas & Kejelasan Visual)**: Indikator status ujian dan proctoring menggunakan konvensi kode warna intuitif:
  * Hijau: Normal / Lulus / Terjawab
  * Kuning: Peringatan / Sedang Dikerjakan / Belum Dijawab
  * Merah: Risiko Tinggi / Gagal / Pelanggaran Kritis

### 5.5 Kompatibilitas Lingkungan (Compatibility)
* **NFR-C1 (Peramban Sasaran)**: Kompatibel dengan peramban modern berbasis Chromium (Google Chrome, Microsoft Edge, Brave) dan Mozilla Firefox versi terbaru yang mendukung WebRTC, MediaDevices API, dan HTML5 Fullscreen API.

---

## 6. SPESIFIKASI FITUR KECERDASAN ARTIFISIAL (AI SPECIFICATIONS)

### 6.1 Arsitektur AI Proctoring & Heuristic Risk Engine
Fitur AI Proctoring memadukan inferensi visi komputer (*computer vision*) di sisi klien dengan komputasi heuristik di sisi server:

```
[Webcam Siswa] ---> [Client-Side Face Tracker (WebRTC & Canvas)]
                            |
           +----------------+----------------+
           |                                 |
 [Event Pelanggaran]                [Snapshot Base64]
           |                                 |
           +----------------> [POST /student/logProctoringEvent]
                                             |
                                             v
                           [proctoring_events Table]
                                             |
                                             v
                        [ProctoringRiskService Engine]
                                             |
                               +-------------+-------------+
                               |                           |
                       [Risk Score: 0-100]       [Trust Index: 0-100]
                               |                           |
                               +-------------+-------------+
                                             |
                                             v
                             [Teacher Live Monitoring Dashboard]
```

* **Daftar Event Pelanggaran yang Dikelola**:
  1. `FACE_NOT_DETECTED`: Wajah tidak terlihat pada tangkapan kamera (Bobot: 10).
  2. `MULTIPLE_FACE`: Terdeteksi lebih dari satu wajah di dalam ruangan ujian (Bobot: 40).
  3. `SUSPICIOUS_GAZE`: Gerak pandangan mata menoleh ekstrem meninggalkan layar (Bobot: 15).
  4. `CAMERA_BLOCKED`: Lensa kamera sengaja ditutup atau pencahayaan gelap total (Bobot: 15).
  5. `CAMERA_DISCONNECTED`: Perangkat webcam terputus secara tiba-tiba (Bobot: 20).
  6. `FACE_CHANGED`: Terdeteksi pergantian identitas wajah peserta (Bobot: 50).
  7. `TAB_SWITCH`: Peserta berpindah tab peramban atau meminimalkan jendela (Bobot: 10).

* **Formula Matematis Indeks Kepercayaan**:
  $$\text{Raw Risk} = \sum_{i=1}^{n} \text{Weight}(e_i)$$
  $$\text{Risk Score} = \min(100, \text{Raw Risk})$$
  $$\text{Trust Index} = 100 - \text{Risk Score}$$

### 6.2 Arsitektur Dual AI Chatbot & Knowledge Grounding
Platform menerapkan strategi *grounded conversational AI* di mana seluruh respon chatbot bersumber langsung dari *database of record* (tabel `exams`, `students`, `teachers`, `classes`, `questions`, dll.) untuk mencegah halusinasi data:

```
                       [Pesan Pengguna / Siswa]
                                  |
                                  v
                   [1. Strict Guardrails Layer]
                   (Blokir Koding, Prompt Injeksi,
                    Bocoran Kunci, Topik Luar)
                                  |
                        +---------+---------+
                        | (Lolos)           | (Pelanggaran)
                        v                   v
              [2. Scoped Context]      [Respon Edukatif
             (Ambil Data Sesuai         Pencegahan]
               Peran Pengguna)
                        |
            +-----------+-----------+
            | (API Key Real Valid)  | (Local Fallback Instan)
            v                       v
     [Google Gemini AI]      [Local Resilient Engine]
     (Model 3.8 Flash)       (Pencocokan Regex & SQL Cepat)
            |                       |
            +-----------+-----------+
                        |
                        v
          [Format Respon Markdown Rapi +
           Tombol Tindakan & Saran Topik]
```

---

## 7. BATASAN TEKNIS & LINGKUNGAN OPERASIONAL (TECH STACK)

* **Arsitektur Perangkat Lunak**: Native Model-View-Controller (MVC) Pattern murni tanpa ketergantungan framework berat (*Zero External Framework Dependency*), menjamin pemahaman rekayasa kode yang utuh.
* **Bahasa Pemrograman Backend**: PHP 8.2+ dengan *Strict Types*, *Null Safety*, dan *Object-Oriented Programming* (OOP) mematuhi prinsip **SOLID**.
* **Database Management System (DBMS)**: MySQL 10.4+ / MariaDB dengan *Engine InnoDB*, relasi *Foreign Key* berintegritas kaskade, dan indeks performa.
* **Frontend UI Framework**: HTML5, CSS3, JavaScript (ES6+ Vanilla), Bootstrap 5.3, Bootstrap Icons, KaTeX (render formula matematika LaTeX).
* **Automated Testing Suite**: PHP CLI Unit & Integration Test Suites (27 berkas pengujian mandiri mencakup seluruh use case).
* **Version Control & CI/CD**: Git, GitHub Repository Publik, GitHub Actions Workflow (`.github/workflows/ci.yml`).

---

## 8. STRUKTUR BERKAS APLIKASI (PROJECT REPOSITORY LAYOUT)

```
Project2/
├── .github/
│   └── workflows/
│       └── ci.yml                 # Alur Otomatisasi CI/CD GitHub Actions
├── app/
│   ├── config/
│   │   └── config.php             # Konfigurasi Konstan & Environtment
│   ├── controllers/               # Lapisan Controller (HTTP Request Handler)
│   │   ├── AdminController.php
│   │   ├── AuthController.php
│   │   ├── HomeController.php
│   │   ├── StudentController.php
│   │   └── TeacherController.php
│   ├── core/                      # Lapisan Inti Sistem (Framework Engine)
│   │   ├── App.php                # URL Routing Dispatcher
│   │   ├── Auth.php               # Guard Autentikasi, RBAC & Throttling
│   │   ├── Controller.php         # Base Controller Helper
│   │   ├── Csrf.php               # Modul Token Keamanan CSRF
│   │   ├── Database.php           # PDO Database Wrapper Singleton
│   │   └── Model.php              # Base Model Wrapper
│   ├── models/                    # Lapisan Model Data (SQL Queries)
│   │   ├── ClassModel.php
│   │   ├── ExamModel.php
│   │   ├── QuestionModel.php
│   │   ├── StudentModel.php
│   │   ├── SubjectModel.php
│   │   ├── TeacherModel.php
│   │   └── UserModel.php
│   ├── services/                  # Lapisan Layanan Bisnis & AI
│   │   ├── ChatbotService.php     # Mesin Chatbot Dual AI & Guardrails
│   │   └── ProctoringRiskService.php # Mesin Kalkulasi Skor Risiko Kecurangan
│   └── views/                     # Lapisan Tampilan Antarmuka (UI Templates)
├── database/
│   ├── schema.sql                 # DDL 13 Tabel Basis Data Utama
│   └── seeds.sql                  # DML Data Awal Sistem (Admin, Guru, Siswa)
├── docs/
│   └── TAHAP_1_PRD_SRS.md         # Dokumen Spesifikasi PRD/SRS Resmi
├── public/
│   ├── css/                       # Lembar Gaya CSS Kustom
│   ├── js/
│   │   ├── app.js
│   │   ├── chatbot.js             # Klien ExamBot AI
│   │   ├── proctoring.js          # Klien WebRTC Face Detection
│   │   └── question_explainer.js  # Klien AI Tutor Pembahasan Soal
│   └── index.php                  # Titik Masuk Tunggal (Front Controller)
├── tests/                         # Suite Pengujian Unit & Integrasi (27 Files)
├── .env                           # Konfigurasi Kredensial Lingkungan
└── README.md                      # Dokumentasi Utama Repositori GitHub
```

---

## 9. PANDUAN NASKAH PRESENTASI VIDEO PROGRES 1 (DURASI 3–5 MENIT)

Sesuai dengan ketentuan standar pada Bab III Panduan Tugas Proyek Akhir, video Progres 1 wajib memenuhi kriteria:
1. **Webcam Aktif di Pojok Layar**: Wajah mahasiswa wajib tampak jelas sepanjang perekaman layar.
2. **Kualitas Audio & Visual**: Minimal 720p (disarankan 1080p), suara narasi jernih dan percaya diri.
3. **Alokasi Durasi Waktu**: Total 3 hingga 5 menit (toleransi $\pm 1$ menit).

### Rincian Alokasi Waktu & Skrip Narasi Per Segmen

```
+-----------------------------------------------------------------------------------+
| DURASI     | SEGMEN & MATERI                 | VISUALISASI LAYAR                  |
+-----------------------------------------------------------------------------------+
| 00:00 -    | Pembukaan & Identitas Mahasiswa | Slide Judul Dokumen PRD:           |
| 00:45      | • Salam pembuka                 | • Judul Proyek: AI Online Exam &   |
| (45 detik) | • Nama, NIM, Kelas, Kelompok    |   Proctoring System                |
|            | • Tema: E-Learning & Smart      | • Nama Mahasiswa & NIM             |
|            |   Education AI                  | • Dosen: Yudhistira Arie Wijaya    |
|            | • Dosen Pengampu & Kampus       | • Webcam aktif jelas di pojok atas |
+-----------------------------------------------------------------------------------+
| 00:45 -    | Latar Belakang & Urgensi Masalah| Tampilkan Bagan Problem Statement: |
| 01:45      | • Krisis kejujuran ujian online | • Ilustrasi celah kecurangan CBT   |
| (60 detik) | • Keterbatasan pengawas manual  | • Ketiadaan umpan balik edukatif   |
|            | • Kebutuhan asesmen cerdas      | • Solusi: AI Proctoring + AI Tutor |
+-----------------------------------------------------------------------------------+
| 01:45 -    | Ruang Lingkup & Kebutuhan       | Dokumen PRD Bab 4 & Bab 5:         |
| 03:00      | Fungsional Utama (User Stories) | • Sorot tabel persona 4 peran      |
| (75 detik) | • Peran Admin, Guru, Siswa      | • Sorot ringkasan User Stories:    |
|            | • Alur ujian terlindungi        |   - RBAC & Hardened Security       |
|            | • Dasbor Live Monitoring        |   - Bank Soal & Penjadwalan        |
|            | • Review nilai & evaluasi       |   - Student Exam Engine            |
+-----------------------------------------------------------------------------------+
| 03:00 -    | Sorotan Fitur AI & Standar      | Diagram Arsitektur AI & NFR:       |
| 04:15      | Non-Fungsional                  | • Alur AI Proctoring (Face & Tab)  |
| (75 detik) | • AI Proctoring (7 Event Bobot) | • Heuristic Risk Engine & Trust    |
|            | • Dual AI Chatbot Grounded      | • Dual AI Chatbot & Guardrails     |
|            | • Kinerja (< 100ms) & Keamanan  | • Keamanan: BCRYPT, CSRF, Prepared |
+-----------------------------------------------------------------------------------+
| 04:15 -    | Rencana Tahap 2 & Penutup       | Roadmap Proyek & Penutup:          |
| 05:00      | • Target Tahap 2: Desain SDD,   | • Teks ringkasan rencana Tahap 2:  |
| (45 detik) |   4 Diagram UML & UI/UX Figma   |   Use Case, Activity, Class, Seq.  |
|            | • Terima kasih & salam penutup  | • Kontak & tautan repositori       |
+-----------------------------------------------------------------------------------+
```

---
*Dokumen ini disusun secara mandiri dan memenuhi standar kompetensi Kurikulum Outcome-Based Education (OBE) S1 Teknik Informatika STMIK IKMI Cirebon.*

