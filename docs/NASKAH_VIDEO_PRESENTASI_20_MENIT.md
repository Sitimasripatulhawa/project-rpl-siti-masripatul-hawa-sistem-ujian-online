# NASKAH LENGKAP VIDEO PRESENTASI & DEMO SISTEM (DURASI ~20 MENIT)
## PROYEK AKHIR REKAYASA PERANGKAT LUNAK: SISTEM UJIAN ONLINE BERBASIS WEB DENGAN AI PROCTORING DAN DUAL CHATBOT

---

**Identitas Mahasiswa:**
- **Nama** : Siti Masripatul Hawa
- **NIM** : 41257160
- **Kelas** : 1C-TI-2025
- **Program Studi** : Teknik Informatika
- **Perguruan Tinggi** : STMIK IKMI Cirebon
- **Mata Kuliah** : Rekayasa Perangkat Lunak (TIKB2503021)
- **Tahun Akademik** : 2026

---

## PETUNJUK TEKNIS REKAMAN VIDEO
1. **Panduan Visual Layar**:
   - `[ON CAMERA]` : Sorot wajah presenter di sudut (Picture-in-Picture) atau layar penuh.
   - `[SCREEN: SLIDE]` : Tampilkan slide presentasi / dokumen PRD & UML.
   - `[SCREEN: WEB BROWSER]` : Rekam layar aplikasi localhost / peramban aktif.
2. **Tempo & Artikulasi**: Gunakan artikulasi yang tenang, percaya diri, dan profesional dengan kecepatan rata-rata 130–140 kata per menit.
3. **Alokasi Waktu Total**: **20 Menit 30 Detik**.

```
========================================================================================
TIMELINE & STRUKTUR PRESENTASI:
----------------------------------------------------------------------------------------
00:00 - 01:30 | PROLOG & PEMBUKAAN (Identitas & Garis Besar Proyek)
01:30 - 06:00 | BAGIAN I: PRODUCT REQUIREMENTS DOCUMENT (PRD / SRS LENGKAP)
06:00 - 12:00 | BAGIAN II: PERANCANGAN ARSITEKTUR & 4 DIAGRAM UML (SDD)
12:00 - 18:30 | BAGIAN III: LIVE DEMO SISTEM & WALKTHROUGH 3 HAK AKSES
18:30 - 20:00 | BAGIAN IV: TESTING, DEVOPS CI/CD, KESIMPULAN & PENUTUP
========================================================================================
```

---

# NASKAH PRESENTASI KATA-PER-KATA (VERBATIM SCRIPT)

---

### [00:00 - 01:30] PROLOG & PEMBUKAAN
**Visual:** `[ON CAMERA - LAYAR PENUH]` dilanjutkan `[SCREEN: SLIDE COVER DENGAN LOGO STMIK IKMI CIREBON]`

> **Presenter:**  
> "Assalamu’alaikum Warahmatullahi Wabarakatuh.  
> Selamat pagi/siang/sore kepada Bapak/Ibu Dosen Pengampu mata kuliah Rekayasa Perangkat Lunak, serta rekan-rekan mahasiswa yang saya banggakan.
>
> Perkenalkan, nama saya **Siti Masripatul Hawa**, dengan NIM **41257160**, dari kelas **1C-TI-2025**, Program Studi Teknik Informatika, **STMIK IKMI Cirebon**.
>
> Pada kesempatan kali ini, saya akan mempresentasikan laporan proyek akhir rekayasa perangkat lunak yang telah saya rancang dan bangun, dengan judul:  
> **'Sistem Ujian Online Berbasis Web dengan AI Computer Vision Proctoring dan Dual Intelligent Chatbot'**.
>
> Presentasi berdurasi kurang lebih 20 menit ini akan saya bagi ke dalam 4 bagian utama:
> 1. **Bagian Pertama**: Pemaparan **Product Requirements Document (PRD / SRS)** secara komprehensif, mencakup latar belakang, rumusan masalah, batasan sistem, hingga kebutuhan fungsional dan non-fungsional.
> 2. **Bagian Kedua**: Pembahasan **Software Design Document (SDD)** dan arsitektur sistem melalui **4 Diagram UML Lengkap**: Use Case Diagram, Activity Diagram, Class Diagram berprinsip SOLID, dan Sequence Diagram dengan 30 langkah interaksi pesan.
> 3. **Bagian Ketiga**: **Live Demo Aplikasi Nyata**, mendemonstrasikan secara langsung antarmuka Administrator, Guru, Siswa, sistem Auto-Save pengerjaan soal, pengawasan AI Proctoring WebRTC secara *real-time*, integrasi Dual Chatbot, hingga pemulihan sandi dengan OTP WhatsApp.
> 4. **Bagian Keempat**: Pembuktian kualitas melalui **Pengujian Otomatis 86 Kasus Uji (Zero Failure)**, implementasi **DevOps CI/CD GitHub Actions**, serta kesimpulan akhir.
>
> Mari kita mulai pembahasan dari bagian pertama, yaitu analisis kebutuhan sistem."

---

### [01:30 - 06:00] BAGIAN I: PRODUCT REQUIREMENTS DOCUMENT (PRD / SRS LENGKAP)
**Visual:** `[SCREEN: SLIDE PRD / SRS - BAB I LAPORAN]`

> **Presenter:**  
> "Kita masuk ke **BAB I: Pendahuluan dan Product Requirements Document (PRD)**.
>
> #### 1.1 Latar Belakang & Identifikasi Masalah
> Transformasi digital di dunia pendidikan melalui *e-learning* memberikan fleksibilitas luar biasa. Namun, pelaksanaan evaluasi atau ujian secara daring masih menghadapi empat tantangan kritis:
> 1. **Pertama, Academic Dishonesty**: Tingginya risiko kecurangan akademik, seperti perpindahan tab ke mesin pencari, kolaborasi tanpa izin dengan orang lain di ruangan yang sama, atau bahkan joki ujian.
> 2. **Kedua, Proctoring Fatigue**: Pengawasan manual oleh guru secara tatap muka virtual dalam durasi lama sangat menguras fokus dan rentan terhadap kelalaian manusia (*human error*).
> 3. **Ketiga, Tiadanya Umpan Balik Pedagogis Pasca-Ujian**: Platform konvensional umumnya hanya menyajikan angka nilai tanpa memberikan penjelasan konsep mengapa jawaban siswa salah dan bagaimana cara memperbaikinya.
> 4. **Keempat, Gangguan Jaringan & Kehilangan Data**: Putusnya koneksi internet sering kali menyebabkan jawaban yang telah dikerjakan hilang atau gagal terkirim, menimbulkan frustrasi bagi siswa.
>
> #### 1.2 Tujuan dan Manfaat Proyek
> Berangkat dari permasalahan tersebut, proyek ini dirancang dengan tujuan:
> - Membangun platform ujian daring terpadu yang memfasilitasi peran Administrator, Guru/Penguji, dan Siswa/Peserta.
> - Mengintegrasikan **AI Computer Vision Proctoring** secara *real-time* berbasis browser untuk mendeteksi indikator risiko tanpa mengorbankan privasi.
> - Mengembangkan mekanisme **Resilient Auto-Save** dan sinkronisasi berkala melalui AJAX agar jawaban siswa tidak pernah hilang akibat kendala jaringan.
> - Menghadirkan inovasi **Dual Intelligent Chatbot**, yang terdiri dari *ExamBot Lokal* untuk operasional berkecepatan tinggi di bawah 50 milidetik, serta *Academic Tutor Gemini AI* untuk pembahasan materi butir soal setelah ujian berakhir.
>
> #### 1.3 Ruang Lingkup dan Batasan Sistem
> Ruang lingkup proyek mencakup:
> - Autentikasi multi-role dan pengelolaan master data akademik (guru, siswa, kelas rombel, dan mata pelajaran).
> - Pengelolaan bank soal dengan opsi acak, durasi pengerjaan, dan jadwal ujian.
> - Pengawasan kamera WebRTC yang sepenuhnya berjalan dengan izin eksplisit (*explicit consent*) dari siswa.
> - **Batasan penting**: Keputusan akhir terhadap indikasi kecurangan **tetap berada di tangan Guru atau Pengawas manusia**. AI bertindak sebagai asisten pencatat indikator risiko (*decision support tool*), bukan penentu vonis tunggal.
>
> #### 1.4 Kebutuhan Fungsional (Functional Requirements)
> Kebutuhan fungsional sistem dibagi menjadi 8 modul utama:
> - **FR-01**: Autentikasi aman berbasis enkripsi hash BCRYPT, pengelolaan sesi terlindungi, dan Role-Based Access Control (RBAC).
> - **FR-02 & FR-03**: Manajemen akun pengguna dan data akademik secara terstruktur.
> - **FR-04**: Manajemen bank soal bertipe pilihan ganda, pembuatan paket ujian, alokasi waktu, serta token ujian unik.
> - **FR-05**: Pelaksanaan ujian interaktif dengan navigasi nomor soal, penanda ragu-ragu, dan hitung mundur timer otomatis.
> - **FR-06**: Mesin *Auto-Save* berkala per perubahan jawaban melalui AJAX dengan penanganan status sukses visual.
> - **FR-07**: Pengawasan AI Proctoring berkemampuan deteksi wajah hilang (*face absent*), multi-wajah (*multiple faces*), serta pelanggaran perpindahan tab (*tab switch*).
> - **FR-08**: Integrasi layanan cerdas Dual Chatbot dan notifikasi OTP WhatsApp via gateway Fonnte serta email SMTP.
>
> #### 1.5 Kebutuhan Non-Fungsional (Non-Functional Requirements)
> - **NFR-01 (Keamanan)**: Proteksi serangan CSRF via token unik, sanitasi prepared statement PDO untuk mencegah SQL Injection, session hardening dengan atribut HttpOnly dan SameSite, serta isolasi kredensial API pada berkas lingkungan `.env`.
> - **NFR-02 (Kinerja)**: Respons kueri antarmuka sangat ringan (<100ms) dan ExamBot lokal dirancang merespons instan (<50ms).
> - **NFR-03 (Keandalan)**: Ketahanan penyimpanan data pengerjaan ujian dengan arsitektur idempotensi (UPSERT / Duplicate Key Update).
> - **NFR-04 (Usabilitas)**: Desain responsif berbasis Bootstrap 5.3 dan ramah pengguna di berbagai peramban modern.
>
> Semua kebutuhan ini telah memenuhi kriteria penerimaan formal sistem rekayasa perangkat lunak."

---

### [06:00 - 12:00] BAGIAN II: PERANCANGAN ARSITEKTUR & 4 DIAGRAM UML (SDD)
**Visual:** `[SCREEN: SLIDE BAB II - 4 DIAGRAM UML & DIAGRAM ARSITEKTUR]`

> **Presenter:**  
> "Kita beralih ke **BAB II: Perancangan Sistem dan Arsitektur UML (Software Design Document)**.
>
> #### 2.1 Arsitektur Sistem 3-Tier MVC Native & Service Layer
> Aplikasi ini dibangun tanpa menggunakan framework berat, melainkan menggunakan arsitektur **3-Tier Pure PHP MVC (Model-View-Controller) Native** yang diperluas dengan **Service Layer**:
> 1. **Presentation Tier**: Antarmuka HTML5, Bootstrap 5.3, JavaScript Vanilla modern, dan WebRTC MediaDevices API.
> 2. **Application Tier**: Terdiri atas Router dinamis, Controller terpisah per role pengguna, serta Domain Services seperti `ProctoringRiskService`, `ChatbotService`, dan `EmailService`.
> 3. **Data Tier**: Basis data MySQL yang diakses melalui PDO Singleton Database berintegritas relasional tinggi.
>
> Untuk memodelkan arsitektur ini secara baku, saya telah merancang **4 Diagram UML Standar OMG UML 2.5**:
>
> ---
>
> #### 2.2 Diagram 1: Use Case Diagram
> *(Arahkan kursor ke Gambar 2.1 Use Case Diagram)*  
> Pada Diagram Use Case, sistem memiliki batas (*system boundary*) yang jelas dengan **5 Aktor**:
> - **Di Sisi Kiri (Aktor Manusia)**:
>   1. **Administrator**: Mengelola master data, konfigurasi gateway, dan chatbot operasional.
>   2. **Guru / Penguji**: Mengelola bank soal, paket ujian, memantau *Live Proctoring*, dan melihat rekap nilai.
>   3. **Siswa / Peserta**: Menjalani *system readiness check*, mengerjakan ujian dengan *auto-save*, dan menggunakan chatbot tutor akademik.
> - **Di Sisi Kanan (Aktor Otomatis / Sistem)**:
>   4. **AI Engine**: Subsistem otomatis yang mengeksekusi penilaian risiko kecurangan visual dan kueri kecerdasan buatan.
>   5. **Aktor Kode OTP**: Gateway eksternal otomatis (Fonnte WhatsApp & SMTP Server) yang melayani pengiriman serta validasi token OTP keamanan.
>
> Diagram ini memetakan **12 Use Case** dengan relasi ketergantungan yang ketat:
> - **Relasi `«include»`**: `UC-09 Kerjakan Ujian` **wajib menyertakan** `UC-08 Cek Kesiapan Kamera`, `UC-01 Autentikasi`, dan `UC-10 Pengawasan AI Proctoring`. Siswa tidak dapat masuk ke ruang ujian tanpa lolos verifikasi perangkat.
> - **Relasi `«extend»`**: `UC-10 AI Proctoring` **memperluas** `UC-06 Live Monitoring Guru`. Saat AI mendeteksi lonjakan risiko, sinyal *Risk Alert* dikirimkan secara kondisional ke layar pantau guru.
>
> ---
>
> #### 2.3 Diagram 2: Activity Diagram Alur Bisnis & AI Proctoring
> *(Arahkan kursor ke Gambar 2.2 Activity Diagram)*  
> Diagram Aktivitas memodelkan alur kerja pelaksanaan ujian menggunakan **4 Swimlanes**:
> 1. *Siswa (Client / WebRTC)*
> 2. *Sistem Ujian (Backend MVC)*
> 3. *AI Proctoring Engine (Service)*
> 4. *Database (MySQL Storage)*
>
> Titik krusial pada diagram ini adalah penggunaan mekanisme **Fork & Join**:
> - Setelah siswa memasukkan token valid dan menyelesaikan *System Readiness Check*, proses dipecah secara paralel melalui **Fork**:
>   - Jalur pertama: Siswa memilih jawaban soal yang langsung memicu **Auto-Save AJAX** ke tabel `exam_answers`.
>   - Jalur kedua secara simultan: AI Proctoring memantau umpan kamera, perubahan tab, dan fullscreen, lalu menghitung skor risiko di `ProctoringRiskService`. Jika risiko melewati batas toleransi (>75%), sistem memasang bendera peringatan (*flag alert*).
> - Ketika siswa mengonfirmasi selesai atau waktu ujian habis, kedua alur digabungkan kembali melalui **Join**, di mana sistem menjalankan mesin penilaian otomatis (*Auto-Grading*) dan menyimpan hasil akhir.
>
> ---
>
> #### 2.4 Diagram 3: Class Diagram & Penerapan Prinsip SOLID
> *(Arahkan kursor ke Gambar 2.3 Class Diagram)*  
> Diagram Kelas memperlihatkan rancangan berorientasi objek yang mematuhi **5 Prinsip SOLID**:
> 1. **Single Responsibility Principle (SRP)**: `Model` hanya bertanggung jawab atas persistensi data, `Controller` hanya menangani lalu lintas request-response HTTP, sedangkan `ProctoringRiskService` khusus mengolah algoritma risiko kecurangan.
> 2. **Open/Closed Principle (OCP)**: Base Controller dan Base Model terbuka untuk diekstensi oleh modul baru tanpa perlu merombak kode fondasi.
> 3. **Liskov Substitution Principle (LSP)**: Seluruh controller spesifik role (`AdminController`, `TeacherController`, `StudentController`) konsisten terhadap kontrak Base Controller.
> 4. **Interface Segregation Principle (ISP)**: Pemisahan kontrak layanan yang spesifik antara service notifikasi email, service gateway OTP, dan service AI chatbot.
> 5. **Dependency Inversion Principle (DIP)**: Controller tingkat tinggi tidak menuliskan kueri mentah database secara langsung, melainkan bergantung pada abstraksi lapisan Model dan Service.
>
> ---
>
> #### 2.5 Diagram 4: Sequence Diagram 30 Pesan Antar-Objek
> *(Arahkan kursor ke Gambar 2.4 Sequence Diagram)*  
> Sequence Diagram menggambarkan kolaborasi dinamis pertukaran pesan secara sinkron dan asinkron yang terbagi ke dalam **4 Fase Terstruktur**:
> - **Fase 1 (Pesan 1–8)**: Inisialisasi ruang ujian dan verifikasi token antara `StudentController` dan `ExamEngineModel`.
> - **Fase 2 (Pesan 9–16)**: Mekanisme *Real-Time Auto-Save* via AJAX saat siswa mengklik opsi radio button, disimpan secara aman ke database dengan konfirmasi visual nomor soal menjadi hijau.
> - **Fase 3 (Pesan 17–25)**: Deteksi kecurangan real-time saat terjadi event *tab switch*, di mana `ProctoringRiskService` mengalkulasi penambahan bobot risiko dan mengembalikan dialog peringatan ke layar siswa.
> - **Fase 4 (Pesan 26–30)**: Penyelesaian ujian, kalkulasi skor otomatis oleh *Auto-Grading Engine*, penyimpanan status ujian menjadi *COMPLETED*, dan pengalihan ke halaman rekap hasil.
>
> Desain ini ditopang oleh skema basis data relasional **12 Tabel** yang dinormalisasi dengan rapi."

---

### [12:00 - 18:30] BAGIAN III: LIVE DEMO SISTEM & WALKTHROUGH FITUR
**Visual:** `[SCREEN: WEB BROWSER PADA http://localhost/Project2/]`

> **Presenter:**  
> "Sekarang, mari kita buktikan rancangan tersebut secara langsung melalui **Live Demo Aplikasi**.
>
> #### 3.1 Halaman Login & Fitur Lupa Sandi (OTP WhatsApp)
> *(Tampilkan halaman login)*  
> "Ini adalah halaman login utama Sistem Ujian Online. Antarmuka telah diamankan dengan validasi token CSRF, enkripsi password BCRYPT, dan perlindungan brute-force login.
>
> *(Klik 'Lupa Password?')*  
> Jika pengguna lupa kata sandi, mereka dapat mengakses menu pemulihan sandi. Di sini, sistem terintegrasi dengan **Gateway WhatsApp Fonnte** dan **SMTP Gmail**. Pengguna cukup memasukkan nomor WhatsApp atau email yang terdaftar, dan sistem akan menggenerasi 6 digit Kode OTP unik dengan masa kedaluwarsa 10 menit untuk melakukan reset sandi secara aman.
>
> ---
>
> #### 3.2 Demo Role Administrator: Tata Kelola & ExamBot
> *(Login menggunakan akun `admin` / `password123`)*  
> "Pertama, kita login sebagai **Super Administrator**.  
> Kita langsung disambut oleh Dasbor Administrator yang menyajikan ringkasan metrik statistik: total siswa, guru, kelas rombel, mata pelajaran, dan paket ujian aktif.
>
> *(Buka menu Master Data: Pengguna, Kelas, Mapel)*  
> Admin memiliki wewenang penuh dalam manajemen data:
> - Mengelola akun pengguna dengan enkripsi otomatis.
> - Mengatur rombongan belajar kelas seperti XII MIPA 1 dan XII MIPA 2.
> - Mengalokasikan mata pelajaran dan penugasan guru pengajar.
>
> *(Buka ExamBot di pojok kanan bawah dasbor)*  
> Inilah salah satu inovasi utama: **ExamBot Lokal**. Asisten cerdas operasional ini dirancang untuk menjawab pertanyaan teknis seputar sistem dengan sangat cepat—di bawah 50 milidetik—tanpa bergantung pada kuota API eksternal.  
> *(Ketik pertanyaan di chat: 'Berapa jumlah siswa yang terdaftar saat ini?')*  
> Lihat, dalam hitungan milidetik ExamBot langsung menyajikan data akurat secara real-time dari database!
>
> Kita logout dari akun Administrator.
>
> ---
>
> #### 3.3 Demo Role Guru / Penguji: Bank Soal & Live Monitoring
> *(Login menggunakan akun `guru_budi` / `password123`)*  
> "Kedua, kita login sebagai **Guru Penguji (Budi Santoso, M.Pd)**.
>
> *(Buka menu Bank Soal & Paket Ujian)*  
> Di modul ini, Guru dapat:
> - Menyusun butir soal pilihan ganda lengkap dengan opsi acak, kunci jawaban, dan pembahasan materi.
> - Membuat paket ujian baru, menentukan durasi pengerjaan (misalnya 60 menit), jadwal mulai dan selesai, serta menggenerasi token ujian unik yang nantinya dibagikan kepada siswa.
>
> *(Buka menu Live Monitoring Ujian)*  
> Fitur unggulan bagi guru adalah **Live Monitoring & Proctoring Feed**.  
> Guru dapat memantau seluruh peserta ujian dalam satu layar grid: melihat progres nomor soal yang sedang dikerjakan siswa, status koneksi, serta indikator level risiko kecurangan (apakah LOW, MEDIUM, atau HIGH) yang dilaporkan oleh AI secara *real-time*.
>
> Sekarang, mari kita lihat bagaimana pengalaman dari sudut pandang siswa. Kita logout dari akun Guru.
>
> ---
>
> #### 3.4 Demo Role Siswa: Readiness Check, Ujian dengan Auto-Save & AI Proctoring
> *(Login menggunakan akun `siswa_ani` / `password123`)*  
> "Ketiga, kita login sebagai **Siswa Peserta (Ani Lestari)**.
>
> *(Tampilkan Dasbor Siswa & Klik 'Ikuti Ujian')*  
> Pada Dasbor Siswa, tampak daftar ujian aktif yang dapat diikuti. Siswa memasukkan token ujian yang valid dan mengklik mulai.
>
> *(Tampilkan Halaman System Readiness Check)*  
> Sebelum memasuki soal, sistem mengarahkan siswa ke **Halaman Cek Kesiapan Sistem (WebRTC)**:
> - Peramban meminta izin akses kamera webcam secara eksplisit.
> - Layar menampilkan pratinjau video langsung untuk memastikan wajah siswa berada di tengah bingkai dan pencahayaan memadai.
> - Sistem meminta konfirmasi untuk mengaktifkan mode layar penuh (*fullscreen*).
>
> *(Klik 'Masuk Ruang Ujian')*  
> Kita sekarang berada di dalam **Ruang Ujian Utama**:
> - Di bagian atas, terdapat timer hitung mundur yang disinkronkan dengan server.
> - Di sebelah kanan, terdapat kisi-kisi nomor navigasi soal.
> - Di sudut layar, terdapat *floating camera feed* WebRTC yang terus aktif memantau.
>
> *(Simulasikan klik opsi jawaban B pada Soal No. 1)*  
> Perhatikan: saat opsi jawaban diklik, skrip AJAX di latar belakang langsung mengirim data ke server. Indikator status penyimpanan berubah menjadi centang hijau tersimpan, dan tombol nomor soal di navigasi kanan seketika berubah warna menjadi hijau. Bahkan jika browser sengaja di-refresh *(lakukan refresh halaman)*, seluruh jawaban siswa tetap aman dan pulih 100%!
>
> *(Simulasikan Pelanggaran AI Proctoring: Berpindah Tab)*  
> Sekarang mari kita uji kemampuan AI Proctoring-nya. Saya akan mencoba berpindah tab ke halaman lain...  
> *(Pindah tab selama 2 detik lalu kembali)*  
> Perhatikan! Sistem seketika mendeteksi keluarnya fokus peramban, memutar sinyal audio peringatan, dan memunculkan jendela dialog peringatan:  
> *'Peringatan: Perpindahan Tab Terdeteksi! Aktivitas Anda telah dicatat oleh sistem proctoring'*.  
> Skor risiko kecurangan di backend secara otomatis meningkat dan langsung terlaporkan di layar monitor Guru!
>
> *(Klik 'Selesaikan Ujian' & Konfirmasi)*  
> Siswa menyelesaikan ujian. Mesin *Auto-Grading* langsung mengoreksi seluruh jawaban dalam waktu kurang dari satu detik, menghitung persentase nilai akhir, dan menampilkan kartu rekap kelulusan.
>
> *(Buka Chatbot Academic Explainer)*  
> Dan pasca-ujian, siswa dapat berinteraksi dengan **Academic Tutor berbasis Gemini AI** untuk menanyakan pembahasan butir soal yang dijawab salah, sehingga proses evaluasi benar-benar memberikan nilai edukasi pedagogis yang bermakna!"

---

### [18:30 - 20:00] BAGIAN IV: TESTING, DEVOPS CI/CD, KESIMPULAN & PENUTUP
**Visual:** `[SCREEN: TERMINAL TEST RUNNER & REPOSITORI GITHUB ACTIONS]`

> **Presenter:**  
> "Kita melangkah ke bagian akhir: **Pengujian Sistem, DevOps CI/CD, dan Kesimpulan**.
>
> #### 4.1 Rekapitulasi Pengujian Otomatis (86 Kasus Uji - Zero Failure)
> Keandalan sistem ini telah diuji secara menyeluruh melalui pengujian otomatis (*automated test runner*) berbasis PHP CLI yang mencakup **6 Test Suites Lengkap**:
> 1. *Authentication & Authorization Suite* (Login, Logout, RBAC, Session Hardening).
> 2. *User & Academic Management Suite* (CRUD Pengguna, Kelas, dan Mapel).
> 3. *Exam Management Suite* (Bank Soal, Paket Ujian, Token Generator).
> 4. *Exam Attempt & Resilient Auto-Save Suite* (Inisialisasi Sesi, AJAX Auto-Save, Pemulihan Koneksi).
> 5. *AI & Proctoring Integration Suite* (Scoring Risiko Pelanggaran, Fallback Chatbot).
> 6. *Notification & Security Suite* (Fonnte WA, SMTP Mailer, Validasi CSRF).
>
> Hasilnya: **Total 86 kasus uji dinyatakan LULUS 100% (Zero Failure)**!
>
> #### 4.2 DevOps CI/CD & GitHub Push Protection
> Proyek ini dikelola secara modern menggunakan Git dengan konvensi **Semantic Commits** (`feat`, `fix`, `test`, `docs`, `refactor`). Seluruh kode telah terintegrasi dengan **GitHub Actions** untuk menjalankan pengujian otomatis pada setiap *push* dan *pull request*.  
> Selain itu, seluruh kredensial rahasia seperti API Key Gemini, token Fonnte, dan sandi SMTP Gmail dilindungi dengan ketat di dalam berkas lingkungan `.env` serta menerapkan fitur **GitHub Push Protection** sehingga tidak ada rahasia yang bocor ke repositori publik.
>
> #### 4.3 Kesimpulan Akhir
> Berdasarkan keseluruhan tahapan analisis, perancangan, implementasi, dan pengujian yang telah dipaparkan, dapat disimpulkan bahwa:
> 1. Proyek **Sistem Ujian Online Berbasis Web dengan AI Proctoring dan Dual Chatbot** ini telah berhasil menyelesaikan seluruh target kebutuhan fungsional dan non-fungsional dengan tuntas.
> 2. Penerapan arsitektur **MVC Native 3-Tier** dan kepatuhan terhadap prinsip **SOLID** menghasilkan fondasi perangkat lunak yang kokoh, mudah dirawat (*maintainable*), dan siap dikembangkan lebih lanjut.
> 3. Integrasi teknologi **AI Computer Vision Proctoring** dan **Dual Chatbot** terbukti efektif meningkatkan integritas pengawasan sekaligus memberikan pengalaman belajar yang solutif bagi siswa.
>
> Demikian presentasi laporan proyek akhir Rekayasa Perangkat Lunak ini saya sampaikan. Terima kasih yang sebesar-besarnya kepada Bapak/Ibu Dosen atas segala bimbingan dan masukannya. Mohon maaf apabila terdapat kekurangan dalam penyampaian.
>
> *Wabillahi taufiq wal hidayah,*  
> *Wassalamu’alaikum Warahmatullahi Wabarakatuh.*"

---
*Naskah ini disusun secara lengkap dan siap digunakan sebagai panduan rekaman video presentasi (teleprompter / cue card) oleh Siti Masripatul Hawa.*
