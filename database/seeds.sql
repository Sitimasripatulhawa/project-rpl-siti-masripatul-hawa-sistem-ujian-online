-- ==========================================================
-- AI Online Exam & Proctoring System
-- Initial Seed Data
-- ==========================================================

-- All users have password: password123
-- Hash: $2y$10$1nIGQTiaw26pSYLwzMexnueZ/GS/xV.ErFc.12F/D5ayT2Pxdab0C

-- ----------------------------------------------------------
-- 1. SEED USERS (1 Admin, 4 Teachers, 5 Students)
-- ----------------------------------------------------------
INSERT INTO users (id, username, email, password, role, full_name, phone, is_active) VALUES
(1, 'admin', 'admin@sekolah.sch.id', '$2y$10$1nIGQTiaw26pSYLwzMexnueZ/GS/xV.ErFc.12F/D5ayT2Pxdab0C', 'admin', 'Super Administrator', '081234567890', 1),
(2, 'guru_budi', 'budi.santoso@sekolah.sch.id', '$2y$10$1nIGQTiaw26pSYLwzMexnueZ/GS/xV.ErFc.12F/D5ayT2Pxdab0C', 'teacher', 'Budi Santoso, M.Pd', '081234567891', 1),
(3, 'siswa_ani', 'ani.lestari@sekolah.sch.id', '$2y$10$1nIGQTiaw26pSYLwzMexnueZ/GS/xV.ErFc.12F/D5ayT2Pxdab0C', 'student', 'Ani Lestari', '081234567892', 1),
(4, 'siswa_bimo', 'bimo.nugroho@sekolah.sch.id', '$2y$10$1nIGQTiaw26pSYLwzMexnueZ/GS/xV.ErFc.12F/D5ayT2Pxdab0C', 'student', 'Bimo Nugroho', '081234567893', 1),
(5, 'siswa_citra', 'citra.dewi@sekolah.sch.id', '$2y$10$1nIGQTiaw26pSYLwzMexnueZ/GS/xV.ErFc.12F/D5ayT2Pxdab0C', 'student', 'Citra Dewi', '081234567894', 1),
(14, 'guru_tika', 'tika@sekolah.sch.id', '$2y$10$1nIGQTiaw26pSYLwzMexnueZ/GS/xV.ErFc.12F/D5ayT2Pxdab0C', 'teacher', 'Tika', '081234567895', 1),
(15, 'sairo', 'sairo@sekolah.sch.id', '$2y$10$1nIGQTiaw26pSYLwzMexnueZ/GS/xV.ErFc.12F/D5ayT2Pxdab0C', 'student', 'Sairo', '081234567896', 1),
(18, 'guru_ayu', 'ayu@sekolah.sch.id', '$2y$10$1nIGQTiaw26pSYLwzMexnueZ/GS/xV.ErFc.12F/D5ayT2Pxdab0C', 'teacher', 'Ayu, M.Pd', '081234567897', 1),
(19, 'salsa', 'salsa@sekolah.sch.id', '$2y$10$1nIGQTiaw26pSYLwzMexnueZ/GS/xV.ErFc.12F/D5ayT2Pxdab0C', 'student', 'Salsa', '081234567898', 1),
(20, 'guru_andi', 'andi.pratama@sekolah.sch.id', '$2y$10$1nIGQTiaw26pSYLwzMexnueZ/GS/xV.ErFc.12F/D5ayT2Pxdab0C', 'teacher', 'Andi Pratama, M.Pd', '081234567899', 1)
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), role=VALUES(role);

-- ----------------------------------------------------------
-- 2. SEED CLASSES (2 Rombel Kelas)
-- ----------------------------------------------------------
INSERT INTO classes (id, name, level, academic_year) VALUES
(1, 'XII MIPA 1', 'XII', '2026/2027'),
(13, 'XII MIPA 2', 'XII', '2026/2027')
ON DUPLICATE KEY UPDATE name=VALUES(name), academic_year=VALUES(academic_year);

-- ----------------------------------------------------------
-- 3. SEED TEACHERS (4 Guru Pengajar)
-- ----------------------------------------------------------
INSERT INTO teachers (id, user_id, nip, specialization) VALUES
(1, 2, '198503152010011005', 'Matematika Terapan & Statistika'),
(6, 14, '8973452', 'B. Inggris'),
(8, 18, '5370652', 'Bahasa Indonesia'),
(9, 20, '8571047', 'Biologi')
ON DUPLICATE KEY UPDATE specialization=VALUES(specialization);

-- ----------------------------------------------------------
-- 4. SEED STUDENTS (5 Siswa)
-- ----------------------------------------------------------
INSERT INTO students (id, user_id, class_id, nis, nisn, gender) VALUES
(1, 3, 1, '20261001', '0061234561', 'P'),
(2, 4, 1, '20261002', '0061234562', 'L'),
(3, 5, 1, '20261003', '0061234563', 'P'),
(6, 15, 13, '0898342', '0061234564', 'L'),
(8, 19, 13, '2387335', '0061234565', 'P')
ON DUPLICATE KEY UPDATE nis=VALUES(nis), class_id=VALUES(class_id);

-- ----------------------------------------------------------
-- 5. SEED SUBJECTS (4 Mata Pelajaran)
-- ----------------------------------------------------------
INSERT INTO subjects (id, code, name, description) VALUES
(1, 'MTK-XII-01', 'Matematika Wajib', 'Mata Pelajaran Matematika Wajib untuk Kelas XII Tingkat Menengah Atas'),
(5, 'B INGGRIS-XII-02', 'B. Inggris', 'Mata Pelajaran Bahasa Inggris untuk Tingkat Menengah Atas'),
(7, 'INDONESIA-XII-01', 'Bahasa Indonesia', 'Mata Pelajaran Bahasa Indonesia untuk Tingkat Menengah Atas'),
(8, 'BIOLOGI-XII-02', 'Biologi', 'Mata Pelajaran Biologi untuk Kelas XII MIPA')
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description);

-- ----------------------------------------------------------
-- 6. SEED QUESTIONS (Bank Soal)
-- ----------------------------------------------------------
INSERT INTO questions (id, subject_id, teacher_id, question_text, question_type, difficulty, default_score, explanation) VALUES
(1, 1, 1, 'Diketahui sebuah fungsi kuadrat f(x) = x² - 6x + 8. Tentukan titik balik minimum fungsi tersebut!', 'multiple_choice', 'easy', 20.00, 'Titik puncak xp = -b/(2a) = 6/2 = 3. yp = f(3) = 9 - 18 + 8 = -1. Jadi titik balik minimum adalah (3, -1).'),
(2, 1, 1, 'Berapakah nilai dari limit x menuju 2 untuk fungsi (x² - 4) / (x - 2)?', 'multiple_choice', 'easy', 20.00, 'Faktorisasi (x - 2)(x + 2) / (x - 2) = x + 2. Untuk x = 2, hasilnya adalah 2 + 2 = 4.'),
(3, 1, 1, 'Jika turunan pertama dari fungsi f(x) = 2x³ - 9x² + 12x + 5 adalah f\'(x), pada interval manakah fungsi tersebut naik?', 'multiple_choice', 'medium', 20.00, 'f\'(x) = 6x² - 18x + 12 = 6(x - 1)(x - 2) > 0. Fungsi naik pada interval x < 1 atau x > 2.'),
(4, 1, 1, 'Sebuah dadu bersisi 6 dilempar undi sebanyak satu kali. Peluang munculnya mata dadu prima ganjil adalah...', 'multiple_choice', 'easy', 20.00, 'Angka prima ganjil pada dadu adalah {3, 5} (ada 2). Ruang sampel = 6. Peluang = 2/6 = 1/3.'),
(5, 1, 1, 'Hasil dari integral tentu ∫ (3x² + 2x - 1) dx dari batas x = 0 sampai x = 2 adalah...', 'multiple_choice', 'medium', 20.00, 'Anti-turunan = [x³ + x² - x] dari 0 sampai 2 = (8 + 4 - 2) - 0 = 10.'),
(8, 1, 1, 'Jelaskan teorema limit pusat dan penerapannya dalam statistik!', 'essay', 'medium', 20.00, 'Teorema limit pusat menyatakan bahwa distribusi rata-rata sampel akan mendekati distribusi normal seiring bertambahnya ukuran sampel.'),
(11, 5, 6, 'Berapakah hasil dari 25 x 4?', 'multiple_choice', 'easy', 50.00, 'Hasil perkalian 25 dikali 4 secara aritmatika dasar adalah 100.'),
(12, 5, 6, 'Jelaskan prinsip kerja dari enkripsi password satu arah menggunakan Bcrypt!', 'essay', 'hard', 50.00, 'Bcrypt menggunakan algoritma eksponensial berbasis Blowfish dengan random salt untuk mencegah serangan rainbow table.'),
(13, 7, 8, 'Berapakah hasil dari 25 x 4?', 'multiple_choice', 'easy', 50.00, 'Hasil perkalian 25 dikali 4 secara aritmatika dasar adalah 100.'),
(14, 7, 8, 'Jelaskan prinsip kerja dari enkripsi password satu arah menggunakan Bcrypt!', 'essay', 'hard', 50.00, 'Bcrypt menggunakan algoritma eksponensial berbasis Blowfish dengan random salt untuk mencegah serangan rainbow table.'),
(15, 8, 9, 'Berapakah hasil dari 25 x 4?', 'multiple_choice', 'easy', 50.00, 'Hasil perkalian 25 dikali 4 secara aritmatika dasar adalah 100.'),
(16, 8, 9, 'Jelaskan prinsip kerja dari enkripsi password satu arah menggunakan Bcrypt!', 'essay', 'hard', 50.00, 'Bcrypt menggunakan algoritma eksponensial berbasis Blowfish dengan random salt untuk mencegah serangan rainbow table.')
ON DUPLICATE KEY UPDATE question_text=VALUES(question_text), explanation=VALUES(explanation);

-- ----------------------------------------------------------
-- 7. SEED QUESTION_OPTIONS
-- ----------------------------------------------------------
-- Options for Question 1
INSERT INTO question_options (id, question_id, option_label, option_text, is_correct) VALUES
(1, 1, 'A', '(3, -1)', 1),
(2, 1, 'B', '(-3, 1)', 0),
(3, 1, 'C', '(3, 1)', 0),
(4, 1, 'D', '(2, 4)', 0),
(5, 1, 'E', '(-2, -4)', 0)
ON DUPLICATE KEY UPDATE option_text=VALUES(option_text), is_correct=VALUES(is_correct);

-- Options for Question 2
INSERT INTO question_options (id, question_id, option_label, option_text, is_correct) VALUES
(6, 2, 'A', '0', 0),
(7, 2, 'B', '2', 0),
(8, 2, 'C', '4', 1),
(9, 2, 'D', '8', 0),
(10, 2, 'E', 'Tidak terdefinisi', 0)
ON DUPLICATE KEY UPDATE option_text=VALUES(option_text), is_correct=VALUES(is_correct);

-- Options for Question 3
INSERT INTO question_options (id, question_id, option_label, option_text, is_correct) VALUES
(11, 3, 'A', '1 < x < 2', 0),
(12, 3, 'B', 'x < 1 atau x > 2', 1),
(13, 3, 'C', 'x < -2 atau x > -1', 0),
(14, 3, 'D', '-2 < x < -1', 0),
(15, 3, 'E', 'x > 0', 0)
ON DUPLICATE KEY UPDATE option_text=VALUES(option_text), is_correct=VALUES(is_correct);

-- Options for Question 4
INSERT INTO question_options (id, question_id, option_label, option_text, is_correct) VALUES
(16, 4, 'A', '1/6', 0),
(17, 4, 'B', '1/3', 1),
(18, 4, 'C', '1/2', 0),
(19, 4, 'D', '2/3', 0),
(20, 4, 'E', '5/6', 0)
ON DUPLICATE KEY UPDATE option_text=VALUES(option_text), is_correct=VALUES(is_correct);

-- Options for Question 5
INSERT INTO question_options (id, question_id, option_label, option_text, is_correct) VALUES
(21, 5, 'A', '8', 0),
(22, 5, 'B', '10', 1),
(23, 5, 'C', '12', 0),
(24, 5, 'D', '14', 0),
(25, 5, 'E', '16', 0)
ON DUPLICATE KEY UPDATE option_text=VALUES(option_text), is_correct=VALUES(is_correct);

-- Options for Question 11 (B. Inggris)
INSERT INTO question_options (id, question_id, option_label, option_text, is_correct) VALUES
(26, 11, 'A', '50', 0),
(27, 11, 'B', '75', 0),
(28, 11, 'C', '100', 1),
(29, 11, 'D', '125', 0)
ON DUPLICATE KEY UPDATE option_text=VALUES(option_text), is_correct=VALUES(is_correct);

-- Options for Question 13 (Bahasa Indonesia)
INSERT INTO question_options (id, question_id, option_label, option_text, is_correct) VALUES
(30, 13, 'A', '50', 0),
(31, 13, 'B', '75', 0),
(32, 13, 'C', '100', 1),
(33, 13, 'D', '125', 0)
ON DUPLICATE KEY UPDATE option_text=VALUES(option_text), is_correct=VALUES(is_correct);

-- Options for Question 15 (Biologi)
INSERT INTO question_options (id, question_id, option_label, option_text, is_correct) VALUES
(34, 15, 'A', '50', 0),
(35, 15, 'B', '75', 0),
(36, 15, 'C', '100', 1),
(37, 15, 'D', '125', 0)
ON DUPLICATE KEY UPDATE option_text=VALUES(option_text), is_correct=VALUES(is_correct);

-- ----------------------------------------------------------
-- 8. SEED EXAMS (Sesi Ujian Resmi)
-- ----------------------------------------------------------
INSERT INTO exams (id, title, description, subject_id, teacher_id, class_id, duration_minutes, start_time, end_time, token, is_random_question, is_random_option, enable_proctoring, status, total_score, passing_score, show_result) VALUES
(4, 'Penilaian Akhir Semester (PAS) - Matematika Wajib', 'Ujian Akhir Semester Mata Pelajaran Matematika Wajib Kelas XII MIPA', 1, 1, 1, 60, NOW() - INTERVAL 1 DAY, NOW() + INTERVAL 7 DAY, 'PASMTK1', 1, 1, 1, 'published', 100.00, 75.00, 1)
ON DUPLICATE KEY UPDATE title=VALUES(title), passing_score=VALUES(passing_score), status=VALUES(status);
