-- ==========================================================
-- AI Online Exam & Proctoring System
-- Database Schema for MySQL / MariaDB
-- ==========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables if re-migrating
DROP TABLE IF EXISTS proctoring_events;
DROP TABLE IF EXISTS exam_results;
DROP TABLE IF EXISTS exam_answers;
DROP TABLE IF EXISTS exam_participants;
DROP TABLE IF EXISTS exam_questions;
DROP TABLE IF EXISTS exams;
DROP TABLE IF EXISTS question_options;
DROP TABLE IF EXISTS questions;
DROP TABLE IF EXISTS subjects;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS teachers;
DROP TABLE IF EXISTS classes;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------
-- 1. USERS TABLE
-- ----------------------------------------------------------
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL DEFAULT 'student',
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NULL,
    avatar VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role (role),
    INDEX idx_users_email (email),
    INDEX idx_users_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 2. CLASSES TABLE
-- ----------------------------------------------------------
CREATE TABLE classes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    level VARCHAR(20) NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_classes_level (level),
    INDEX idx_classes_academic_year (academic_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 3. TEACHERS TABLE
-- ----------------------------------------------------------
CREATE TABLE teachers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    nip VARCHAR(30) NOT NULL UNIQUE,
    specialization VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_teachers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 4. STUDENTS TABLE
-- ----------------------------------------------------------
CREATE TABLE students (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    class_id BIGINT UNSIGNED NULL,
    nis VARCHAR(30) NOT NULL UNIQUE,
    nisn VARCHAR(30) NULL UNIQUE,
    gender ENUM('L', 'P') NOT NULL DEFAULT 'L',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_students_class (class_id),
    INDEX idx_students_nis (nis),
    CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_students_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 5. SUBJECTS TABLE
-- ----------------------------------------------------------
CREATE TABLE subjects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_subjects_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 6. QUESTIONS TABLE (Bank Soal)
-- ----------------------------------------------------------
CREATE TABLE questions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_id BIGINT UNSIGNED NOT NULL,
    teacher_id BIGINT UNSIGNED NOT NULL,
    question_text LONGTEXT NOT NULL,
    question_type ENUM('multiple_choice', 'essay') NOT NULL DEFAULT 'multiple_choice',
    difficulty ENUM('easy', 'medium', 'hard') NOT NULL DEFAULT 'medium',
    default_score DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    explanation TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_questions_subject (subject_id),
    INDEX idx_questions_teacher (teacher_id),
    INDEX idx_questions_type (question_type),
    INDEX idx_questions_difficulty (difficulty),
    CONSTRAINT fk_questions_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    CONSTRAINT fk_questions_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 7. QUESTION_OPTIONS TABLE (Pilihan Ganda A-E)
-- ----------------------------------------------------------
CREATE TABLE question_options (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id BIGINT UNSIGNED NOT NULL,
    option_label VARCHAR(5) NOT NULL,
    option_text TEXT NOT NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_options_question (question_id),
    INDEX idx_options_correct (is_correct),
    CONSTRAINT fk_options_question FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 8. EXAMS TABLE (Sesi Ujian)
-- ----------------------------------------------------------
CREATE TABLE exams (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    subject_id BIGINT UNSIGNED NOT NULL,
    teacher_id BIGINT UNSIGNED NOT NULL,
    class_id BIGINT UNSIGNED NOT NULL,
    duration_minutes INT UNSIGNED NOT NULL DEFAULT 60,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    token VARCHAR(10) NULL,
    is_random_question TINYINT(1) NOT NULL DEFAULT 1,
    is_random_option TINYINT(1) NOT NULL DEFAULT 1,
    enable_proctoring TINYINT(1) NOT NULL DEFAULT 1,
    status ENUM('draft', 'published', 'running', 'finished') NOT NULL DEFAULT 'draft',
    total_score DECIMAL(6,2) NOT NULL DEFAULT 100.00,
    passing_score DECIMAL(5,2) NOT NULL DEFAULT 75.00,
    show_result TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_exams_subject (subject_id),
    INDEX idx_exams_teacher (teacher_id),
    INDEX idx_exams_class (class_id),
    INDEX idx_exams_status (status),
    INDEX idx_exams_schedule (start_time, end_time),
    CONSTRAINT fk_exams_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    CONSTRAINT fk_exams_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    CONSTRAINT fk_exams_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 9. EXAM_QUESTIONS TABLE
-- ----------------------------------------------------------
CREATE TABLE exam_questions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_id BIGINT UNSIGNED NOT NULL,
    question_id BIGINT UNSIGNED NOT NULL,
    question_order INT UNSIGNED NOT NULL DEFAULT 1,
    question_score DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_exam_question (exam_id, question_id),
    INDEX idx_eq_exam (exam_id),
    INDEX idx_eq_question (question_id),
    CONSTRAINT fk_eq_exam FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    CONSTRAINT fk_eq_question FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 10. EXAM_PARTICIPANTS TABLE
-- ----------------------------------------------------------
CREATE TABLE exam_participants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    status ENUM('not_started', 'in_progress', 'submitted', 'terminated') NOT NULL DEFAULT 'not_started',
    start_time DATETIME NULL,
    submit_time DATETIME NULL,
    risk_score DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_exam_participant (exam_id, student_id),
    INDEX idx_ep_exam (exam_id),
    INDEX idx_ep_student (student_id),
    INDEX idx_ep_status (status),
    CONSTRAINT fk_ep_exam FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    CONSTRAINT fk_ep_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 11. EXAM_ANSWERS TABLE
-- ----------------------------------------------------------
CREATE TABLE exam_answers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_participant_id BIGINT UNSIGNED NOT NULL,
    question_id BIGINT UNSIGNED NOT NULL,
    selected_option_id BIGINT UNSIGNED NULL,
    essay_answer TEXT NULL,
    is_correct TINYINT(1) NULL,
    awarded_score DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    is_flagged_doubt TINYINT(1) NOT NULL DEFAULT 0,
    answered_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_participant_answer (exam_participant_id, question_id),
    INDEX idx_ea_participant (exam_participant_id),
    INDEX idx_ea_question (question_id),
    INDEX idx_ea_option (selected_option_id),
    CONSTRAINT fk_ea_participant FOREIGN KEY (exam_participant_id) REFERENCES exam_participants(id) ON DELETE CASCADE,
    CONSTRAINT fk_ea_question FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    CONSTRAINT fk_ea_option FOREIGN KEY (selected_option_id) REFERENCES question_options(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 12. EXAM_RESULTS TABLE
-- ----------------------------------------------------------
CREATE TABLE exam_results (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_participant_id BIGINT UNSIGNED NOT NULL UNIQUE,
    total_correct INT UNSIGNED NOT NULL DEFAULT 0,
    total_incorrect INT UNSIGNED NOT NULL DEFAULT 0,
    total_unanswered INT UNSIGNED NOT NULL DEFAULT 0,
    final_score DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    is_passed TINYINT(1) NOT NULL DEFAULT 0,
    proctoring_risk_level ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'low',
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_results_participant (exam_participant_id),
    INDEX idx_results_score (final_score),
    INDEX idx_results_passed (is_passed),
    INDEX idx_results_risk (proctoring_risk_level),
    CONSTRAINT fk_results_participant FOREIGN KEY (exam_participant_id) REFERENCES exam_participants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 13. PROCTORING_EVENTS TABLE
-- ----------------------------------------------------------
CREATE TABLE proctoring_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_id BIGINT UNSIGNED NULL,
    student_id BIGINT UNSIGNED NULL,
    exam_participant_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    severity ENUM('INFO', 'WARNING', 'CRITICAL') NOT NULL DEFAULT 'WARNING',
    risk_weight DECIMAL(4,2) NOT NULL DEFAULT 1.00,
    snapshot_path VARCHAR(255) NULL,
    camera_source ENUM('primary_camera', 'secondary_camera', 'screen') NOT NULL DEFAULT 'primary_camera',
    metadata_json JSON NULL,
    event_time DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pe_exam_student (exam_id, student_id),
    INDEX idx_pe_participant (exam_participant_id),
    INDEX idx_pe_type (event_type),
    INDEX idx_pe_severity (severity),
    INDEX idx_pe_time (event_time),
    CONSTRAINT fk_pe_participant FOREIGN KEY (exam_participant_id) REFERENCES exam_participants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 14. TABLE: password_resets (OTP Password Recovery)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS password_resets (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    phone VARCHAR(25) NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    is_used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (phone),
    INDEX (otp_code),
    INDEX (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

