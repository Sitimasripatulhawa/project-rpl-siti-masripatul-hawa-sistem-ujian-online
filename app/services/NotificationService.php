<?php
/**
 * Notification Service
 * Orchestrates in-website notifications and automated email deliveries
 * based on database user records and real business events.
 */

class NotificationService
{
    protected Database $db;
    protected NotificationModel $notificationModel;
    protected EmailService $emailService;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->notificationModel = new NotificationModel();
        $this->emailService = new EmailService();
    }

    /**
     * Create an in-website notification record
     */
    public function createInApp(
        int $userId,
        string $title,
        string $message,
        string $type = 'info',
        string $category = 'general',
        ?string $linkUrl = null
    ): int {
        return $this->notificationModel->create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'category' => $category,
            'link_url' => $linkUrl
        ]);
    }

    /**
     * Notify an individual user: creates in-website notification AND delivers email to their registered address
     */
    public function notifyUser(
        int $userId,
        string $title,
        string $message,
        string $category,
        ?string $linkUrl,
        string $eventType,
        ?string $referenceId = null,
        ?string $emailSubject = null,
        ?string $emailContent = null,
        array $emailDetails = [],
        string $actionText = 'Lihat di Website',
        string $type = 'info'
    ): array {
        // 1. Fetch user from database
        $user = $this->db->query("SELECT id, username, email, full_name, role, is_active FROM users WHERE id = :uid LIMIT 1")
            ->bind(':uid', $userId)
            ->single();

        if (!$user) {
            return [
                'success' => false,
                'message' => "Pengguna dengan ID {$userId} tidak ditemukan di database."
            ];
        }

        // 2. Create in-website notification (always created, independent of email status)
        $notifId = $this->createInApp($userId, $title, $message, $type, $category, $linkUrl);

        // 3. Prepare and send email to user's registered database email
        $recipientEmail = trim($user['email'] ?? '');
        $recipientName = trim($user['full_name'] ?? $user['username']);
        $subject = $emailSubject ?: $title;

        $fullActionUrl = null;
        if (!empty($linkUrl)) {
            $fullActionUrl = strpos($linkUrl, 'http') === 0 ? $linkUrl : rtrim(BASE_URL, '/') . '/' . ltrim($linkUrl, '/');
        }

        $emailBody = $this->emailService->buildHtmlTemplate(
            $title,
            "Halo {$recipientName},",
            $emailContent ?: "<p>" . nl2br(htmlspecialchars($message)) . "</p>",
            $fullActionUrl,
            $actionText,
            $emailDetails
        );

        $emailResult = $this->emailService->send(
            $recipientEmail,
            $recipientName,
            $subject,
            $emailBody,
            $eventType,
            $referenceId,
            $userId
        );

        return [
            'success' => true,
            'notification_id' => $notifId,
            'email_result' => $emailResult
        ];
    }

    /**
     * EVENT 1: Exam is published (Ujian Baru Diterbitkan)
     * Notifies all students belonging to the target class and the author teacher.
     */
    public function notifyExamPublished(int $examId): array
    {
        $exam = $this->getExamWithDetails($examId);
        if (!$exam) {
            return ['success' => false, 'message' => 'Data ujian tidak ditemukan.'];
        }

        // 1. Fetch all students in the assigned class
        $students = $this->db->query("
            SELECT s.id AS student_id, u.id AS user_id, u.full_name, u.email 
            FROM students s 
            JOIN users u ON s.user_id = u.id 
            WHERE s.class_id = :cid AND u.is_active = 1
        ")->bind(':cid', $exam['class_id'])->resultSet();

        $studentsNotified = 0;
        $startTime = date('d M Y, H:i', strtotime($exam['start_time']));
        $endTime = date('d M Y, H:i', strtotime($exam['end_time']));

        $emailDetails = [
            'Mata Pelajaran' => $exam['subject_name'] . ' (' . $exam['subject_code'] . ')',
            'Kelas Sasaran' => $exam['class_name'],
            'Durasi Ujian' => $exam['duration_minutes'] . ' Menit',
            'Waktu Mulai' => $startTime . ' WIB',
            'Waktu Selesai' => $endTime . ' WIB',
            'Passing Score (KKM)' => $exam['passing_score'],
            'Pengawas / Guru' => $exam['teacher_name']
        ];

        foreach ($students as $student) {
            $userId = (int)$student['user_id'];
            $title = "Ujian Baru Diterbitkan: " . $exam['title'];
            $msg = "Sesi ujian {$exam['title']} ({$exam['subject_name']}) telah dipublikasikan untuk kelas Anda. Jadwal: {$startTime} s/d {$endTime} WIB.";
            $linkUrl = "student/examDetail/{$examId}";

            $emailContent = "<p>Sesi ujian baru telah dipublikasikan oleh guru pengampu dan dijadwalkan untuk kelas Anda. Silakan persiapkan perangkat dan pelajari materi sebelum waktu ujian dimulai.</p>";

            $this->notifyUser(
                $userId,
                $title,
                $msg,
                'exam_published',
                $linkUrl,
                'exam_published',
                (string)$examId,
                $title,
                $emailContent,
                $emailDetails,
                'Lihat Detail & Persiapan Ujian',
                'info'
            );
            $studentsNotified++;
        }

        // 2. Also notify the author teacher
        if (!empty($exam['teacher_user_id'])) {
            $tUserId = (int)$exam['teacher_user_id'];
            $tTitle = "Status Ujian Dipublikasikan: " . $exam['title'];
            $tMsg = "Sesi ujian {$exam['title']} untuk kelas {$exam['class_name']} berhasil dipublikasikan. Sebanyak {$studentsNotified} peserta didik telah menerima pemberitahuan.";
            $tLink = "teacher/examShow/{$examId}";

            $this->notifyUser(
                $tUserId,
                $tTitle,
                $tMsg,
                'exam_published',
                $tLink,
                'exam_published_teacher',
                (string)$examId,
                $tTitle,
                "<p>Ujian yang Anda buat telah berhasil dipublikasikan ke sistem. Peserta didik di kelas {$exam['class_name']} telah menerima pemberitahuan otomatis.</p>",
                $emailDetails,
                'Buka Preview Ujian',
                'success'
            );
        }

        return [
            'success' => true,
            'students_notified' => $studentsNotified,
            'message' => "Notifikasi & email berhasil diproses untuk {$studentsNotified} peserta didik."
        ];
    }

    /**
     * EVENT 2: Exam schedule changed (Perubahan Jadwal Ujian)
     */
    public function notifyExamScheduleChanged(int $examId, string $oldStart, string $newStart, string $oldEnd, string $newEnd): array
    {
        $exam = $this->getExamWithDetails($examId);
        if (!$exam) {
            return ['success' => false, 'message' => 'Data ujian tidak ditemukan.'];
        }

        $students = $this->db->query("
            SELECT s.id AS student_id, u.id AS user_id, u.full_name, u.email 
            FROM students s 
            JOIN users u ON s.user_id = u.id 
            WHERE s.class_id = :cid AND u.is_active = 1
        ")->bind(':cid', $exam['class_id'])->resultSet();

        $fOldStart = date('d M Y, H:i', strtotime($oldStart));
        $fNewStart = date('d M Y, H:i', strtotime($newStart));
        $fNewEnd = date('d M Y, H:i', strtotime($newEnd));

        $emailDetails = [
            'Mata Pelajaran' => $exam['subject_name'],
            'Jadwal Baru' => $fNewStart . ' s/d ' . $fNewEnd . ' WIB',
            'Jadwal Sebelumnya' => $fOldStart . ' WIB',
            'Kelas' => $exam['class_name']
        ];

        $refId = $examId . '_' . strtotime($newStart);
        $count = 0;

        foreach ($students as $student) {
            $userId = (int)$student['user_id'];
            $title = "Perubahan Jadwal Ujian: " . $exam['title'];
            $msg = "Jadwal pelaksanaan ujian {$exam['title']} telah diubah menjadi: {$fNewStart} s/d {$fNewEnd} WIB. Harap perhatikan waktu pengerjaan terbaru.";
            $linkUrl = "student/examDetail/{$examId}";

            $this->notifyUser(
                $userId,
                $title,
                $msg,
                'exam_schedule',
                $linkUrl,
                'exam_schedule',
                (string)$refId,
                $title,
                "<p>Terdapat penyesuaian jadwal pelaksanaan pada sesi ujian berikut. Pastikan Anda hadir tepat waktu sesuai jadwal terbaru.</p>",
                $emailDetails,
                'Periksa Jadwal Ujian',
                'warning'
            );
            $count++;
        }

        return ['success' => true, 'count' => $count];
    }

    /**
     * EVENT 3: Exam cancelled or deleted (Pembatalan Ujian)
     */
    public function notifyExamCancelled(int $examId, string $examTitle, int $classId, ?string $reason = null): array
    {
        $students = $this->db->query("
            SELECT s.id AS student_id, u.id AS user_id, u.full_name, u.email 
            FROM students s 
            JOIN users u ON s.user_id = u.id 
            WHERE s.class_id = :cid AND u.is_active = 1
        ")->bind(':cid', $classId)->resultSet();

        $count = 0;
        $emailDetails = [
            'Judul Ujian' => $examTitle,
            'Status' => 'Dibatalkan / Dihapus',
            'Waktu Pembatalan' => date('d M Y, H:i') . ' WIB'
        ];

        if (!empty($reason)) {
            $emailDetails['Alasan'] = $reason;
        }

        foreach ($students as $student) {
            $userId = (int)$student['user_id'];
            $title = "Pembatalan Sesi Ujian: " . $examTitle;
            $msg = "Sesi ujian {$examTitle} yang dijadwalkan untuk kelas Anda telah dibatalkan oleh guru pengampu.";
            $linkUrl = "student/exams";

            $this->notifyUser(
                $userId,
                $title,
                $msg,
                'exam_cancel',
                $linkUrl,
                'exam_cancelled',
                (string)$examId,
                $title,
                "<p>Pemberitahuan resmi bahwa pelaksanaan sesi ujian berikut telah dibatalkan atau ditarik dari sistem.</p>",
                $emailDetails,
                'Lihat Daftar Ujian Tersedia',
                'danger'
            );
            $count++;
        }

        return ['success' => true, 'count' => $count];
    }

    /**
     * EVENT 4: Exam Result Available (Hasil Ujian Telah Tersedia)
     * Strictly verifies show_result is enabled before publishing and sending notifications.
     */
    public function notifyExamResultReady(int $participantId): array
    {
        $p = $this->db->query("
            SELECT ep.id AS participant_id, ep.exam_id, ep.student_id, ep.status,
                   e.title AS exam_title, e.show_result, e.passing_score,
                   s.name AS subject_name,
                   er.final_score, er.is_passed, er.total_correct, er.total_incorrect,
                   u.id AS user_id, u.full_name, u.email 
            FROM exam_participants ep 
            JOIN exams e ON ep.exam_id = e.id 
            JOIN subjects s ON e.subject_id = s.id 
            JOIN students st ON ep.student_id = st.id 
            JOIN users u ON st.user_id = u.id 
            LEFT JOIN exam_results er ON ep.id = er.exam_participant_id 
            WHERE ep.id = :pid 
            LIMIT 1
        ")->bind(':pid', $participantId)->single();

        if (!$p) {
            return ['success' => false, 'message' => 'Peserta ujian tidak ditemukan.'];
        }

        // Privacy protection: Do NOT send results if show_result is disabled
        if (empty($p['show_result'])) {
            return [
                'success' => false,
                'message' => 'Hasil ujian belum diizinkan oleh guru untuk ditampilkan kepada peserta (show_result = 0).'
            ];
        }

        $userId = (int)$p['user_id'];
        $examId = (int)$p['exam_id'];
        $score = number_format((float)($p['final_score'] ?? 0.0), 1);
        $statusLulus = !empty($p['is_passed']) ? 'Lulus (Memenuhi KKM)' : 'Belum Memenuhi KKM';

        $title = "Hasil Ujian Tersedia: " . $p['exam_title'];
        $msg = "Hasil penilaian sesi ujian {$p['exam_title']} ({$p['subject_name']}) telah dirilis. Nilai Anda: {$score} ({$statusLulus}).";
        $linkUrl = "student/examResult/{$examId}";

        $emailDetails = [
            'Mata Pelajaran' => $p['subject_name'],
            'Nilai Akhir' => $score . ' / 100',
            'Passing Score (KKM)' => $p['passing_score'],
            'Status Kelulusan' => $statusLulus,
            'Jawaban Benar' => (int)($p['total_correct'] ?? 0),
            'Jawaban Salah' => (int)($p['total_incorrect'] ?? 0)
        ];

        return $this->notifyUser(
            $userId,
            $title,
            $msg,
            'exam_result',
            $linkUrl,
            'exam_result',
            (string)$participantId,
            $title,
            "<p>Penilaian untuk sesi ujian Anda telah selesai dan diizinkan untuk dilihat. Berikut rangkuman perolehan nilai Anda:</p>",
            $emailDetails,
            'Buka Rincian & Pembahasan',
            !empty($p['is_passed']) ? 'success' : 'warning'
        );
    }

    /**
     * EVENT 5: Exam Reminder before start time (Pengingat Ujian)
     * For scheduler / cron job execution
     */
    public function notifyExamReminder(int $examId, int $minutesBefore = 30): array
    {
        $exam = $this->getExamWithDetails($examId);
        if (!$exam || $exam['status'] !== 'published') {
            return ['success' => false, 'message' => 'Ujian tidak aktif atau tidak ditemukan.'];
        }

        $students = $this->db->query("
            SELECT s.id AS student_id, u.id AS user_id, u.full_name, u.email 
            FROM students s 
            JOIN users u ON s.user_id = u.id 
            WHERE s.class_id = :cid AND u.is_active = 1
        ")->bind(':cid', $exam['class_id'])->resultSet();

        $startTime = date('H:i', strtotime($exam['start_time']));
        $refId = $examId . '_remind_' . date('Ymd');
        $count = 0;

        $emailDetails = [
            'Mata Pelajaran' => $exam['subject_name'],
            'Waktu Mulai' => $startTime . ' WIB',
            'Durasi' => $exam['duration_minutes'] . ' Menit',
            'Kelas' => $exam['class_name']
        ];

        foreach ($students as $student) {
            $userId = (int)$student['user_id'];
            $title = "Pengingat: Ujian {$exam['title']} Dimulai Segera";
            $msg = "Pengingat: Ujian {$exam['title']} akan dimulai kurang lebih {$minutesBefore} menit lagi (Pukul {$startTime} WIB). Mohon siapkan koneksi dan perangkat Anda.";
            $linkUrl = "student/examDetail/{$examId}";

            $this->notifyUser(
                $userId,
                $title,
                $msg,
                'exam_reminder',
                $linkUrl,
                'exam_reminder',
                $refId,
                $title,
                "<p>Ini adalah pesan pengingat resmi bahwa sesi ujian online berikut akan segera dimulai dalam {$minutesBefore} menit ke depan.</p>",
                $emailDetails,
                'Masuk ke Ruang Tunggu Ujian',
                'warning'
            );
            $count++;
        }

        return ['success' => true, 'count' => $count];
    }

    /**
     * Helper to fetch full exam details with relations
     */
    protected function getExamWithDetails(int $examId): ?array
    {
        return $this->db->query("
            SELECT e.*, s.name AS subject_name, s.code AS subject_code,
                   c.name AS class_name,
                   t.user_id AS teacher_user_id,
                   u.full_name AS teacher_name, u.email AS teacher_email 
            FROM exams e 
            JOIN subjects s ON e.subject_id = s.id 
            JOIN classes c ON e.class_id = c.id 
            JOIN teachers t ON e.teacher_id = t.id 
            JOIN users u ON t.user_id = u.id 
            WHERE e.id = :eid 
            LIMIT 1
        ")->bind(':eid', $examId)->single();
    }
}
