<?php
/**
 * Student Controller
 * AI Online Exam & Proctoring System
 */

class StudentController extends Controller
{
    private ExamEngineModel $examEngine;

    public function __construct()
    {
        // Enforce student role authentication
        $this->requireAuth(['student']);
        $this->examEngine = $this->model('ExamEngineModel');
    }

    /**
     * Helper to get student row from users session
     */
    private function getStudentProfile(): ?array
    {
        $user = $this->currentUser();
        if (!$user) {
            return null;
        }

        $db = Database::getInstance();
        return $db->query("
            SELECT s.id, s.nis, s.nisn, s.gender, s.class_id, c.name AS class_name, c.academic_year 
            FROM students s 
            LEFT JOIN classes c ON s.class_id = c.id 
            WHERE s.user_id = :uid 
            LIMIT 1
        ")
        ->bind(':uid', $user['id'])
        ->single();
    }

    public function index()
    {
        $this->dashboard();
    }

    /**
     * Student Dashboard
     */
    public function dashboard()
    {
        $user = $this->currentUser();
        $student = $this->getStudentProfile();
        $studentId = (int)($student['id'] ?? 0);

        $db = Database::getInstance();

        // 1. Count Assigned Exams
        $assignedExams = $db->query("
            SELECT COUNT(*) AS total 
            FROM exam_participants ep
            JOIN exams e ON ep.exam_id = e.id
            WHERE ep.student_id = :sid AND e.status IN ('published', 'running', 'finished')
        ")->bind(':sid', $studentId)->single();

        // 2. Count Completed Exams
        $completedExams = $db->query("
            SELECT COUNT(*) AS total 
            FROM exam_participants 
            WHERE student_id = :sid AND status = 'submitted'
        ")->bind(':sid', $studentId)->single();

        // 3. Count Available / Ready Exams
        $availableExams = $db->query("
            SELECT COUNT(*) AS total 
            FROM exam_participants ep
            JOIN exams e ON ep.exam_id = e.id
            WHERE ep.student_id = :sid 
              AND ep.status IN ('not_started', 'in_progress')
              AND e.status IN ('published', 'running')
        ")->bind(':sid', $studentId)->single();

        // Recent exams for student
        $recentExams = $this->examEngine->getAssignedExamsForStudent($studentId, 'available');

        $data = [
            'page_title' => 'Dashboard Peserta Ujian',
            'current_role' => 'student',
            'user' => $user,
            'student' => $student,
            'stats' => [
                'assigned_exams' => (int)($assignedExams['total'] ?? 0),
                'available_exams' => (int)($availableExams['total'] ?? 0),
                'completed_exams' => (int)($completedExams['total'] ?? 0)
            ],
            'recent_exams' => array_slice($recentExams, 0, 5)
        ];

        $this->view('student/dashboard', $data);
    }

    /**
     * Catalog of assigned exams for student
     */
    public function exams()
    {
        $user = $this->currentUser();
        $student = $this->getStudentProfile();
        $studentId = (int)($student['id'] ?? 0);

        $filter = $_GET['filter'] ?? 'all';
        if (!in_array($filter, ['all', 'available', 'completed'])) {
            $filter = 'all';
        }

        $exams = $this->examEngine->getAssignedExamsForStudent($studentId, $filter);

        $data = [
            'page_title' => 'Daftar Ujian Peserta',
            'current_role' => 'student',
            'user' => $user,
            'student' => $student,
            'exams' => $exams,
            'current_filter' => $filter
        ];

        $this->view('student/exams', $data);
    }

    /**
     * Detail of an assigned exam
     *
     * @param int|string $examId
     */
    public function examDetail($examId = 0)
    {
        $examId = (int)$examId;
        $user = $this->currentUser();
        $student = $this->getStudentProfile();
        $studentId = (int)($student['id'] ?? 0);

        $participant = $this->examEngine->getExamParticipant($examId, $studentId);

        if (!$participant) {
            Auth::setFlash('danger', 'Ujian tidak ditemukan atau Anda tidak terdaftar pada sesi ujian ini.');
            $this->redirect('student/exams');
        }

        $now = time();
        $startTime = strtotime($participant['start_time']);
        $endTime = strtotime($participant['end_time']);

        $isScheduleOpen = ($now >= $startTime && $now <= $endTime);
        $isBeforeSchedule = ($now < $startTime);
        $isAfterSchedule = ($now > $endTime);

        $data = [
            'page_title' => 'Detail Ujian - ' . $participant['title'],
            'current_role' => 'student',
            'user' => $user,
            'student' => $student,
            'exam' => $participant,
            'is_schedule_open' => $isScheduleOpen,
            'is_before_schedule' => $isBeforeSchedule,
            'is_after_schedule' => $isAfterSchedule
        ];

        $this->view('student/detail', $data);
    }

    /**
     * Start exam action (validates token & schedule, routes to system check)
     *
     * @param int|string $examId
     */
    public function startExam($examId = 0)
    {
        $examId = (int)$examId;
        $student = $this->getStudentProfile();
        $studentId = (int)($student['id'] ?? 0);

        $token = $_POST['token'] ?? null;
        $verification = $this->examEngine->verifyExamAccess($examId, $studentId, $token);

        if (!$verification['valid']) {
            Auth::setFlash('danger', $verification['message']);
            $this->redirect('student/examDetail/' . $examId);
        }

        $participant = $verification['participant'];

        if ($participant['participant_status'] === 'submitted') {
            Auth::setFlash('info', 'Anda telah menyelesaikan ujian ini.');
            $this->redirect('student/examResult/' . $examId);
        }

        // Token is verified; set session flag
        $_SESSION['exam_verified_' . $examId] = true;

        $this->redirect('student/systemCheck/' . $examId);
    }

    /**
     * System check page (Connectivity, browser, integrity pledge)
     *
     * @param int|string $examId
     */
    public function systemCheck($examId = 0)
    {
        $examId = (int)$examId;
        $user = $this->currentUser();
        $student = $this->getStudentProfile();
        $studentId = (int)($student['id'] ?? 0);

        $participant = $this->examEngine->getExamParticipant($examId, $studentId);

        if (!$participant) {
            Auth::setFlash('danger', 'Ujian tidak ditemukan atau Anda tidak terdaftar.');
            $this->redirect('student/exams');
        }

        if ($participant['participant_status'] === 'submitted') {
            Auth::setFlash('info', 'Anda telah menyelesaikan ujian ini.');
            $this->redirect('student/examResult/' . $examId);
        }

        // If exam requires token, ensure token was verified
        if (!empty($participant['token']) && empty($_SESSION['exam_verified_' . $examId]) && $participant['participant_status'] === 'not_started') {
            Auth::setFlash('warning', 'Silakan masukkan token ujian terlebih dahulu.');
            $this->redirect('student/examDetail/' . $examId);
        }

        $data = [
            'page_title' => 'System Check & Konfirmasi Ujian',
            'current_role' => 'student',
            'user' => $user,
            'student' => $student,
            'exam' => $participant
        ];

        $this->view('student/system_check', $data);
    }

    /**
     * Enter exam attempt (sets status = in_progress and redirects to exam room)
     *
     * @param int|string $examId
     */
    public function enterExam($examId = 0)
    {
        $examId = (int)$examId;
        $student = $this->getStudentProfile();
        $studentId = (int)($student['id'] ?? 0);

        $participant = $this->examEngine->getExamParticipant($examId, $studentId);

        if (!$participant) {
            Auth::setFlash('danger', 'Ujian tidak ditemukan.');
            $this->redirect('student/exams');
        }

        if ($participant['participant_status'] === 'submitted') {
            Auth::setFlash('info', 'Anda telah menyelesaikan ujian ini.');
            $this->redirect('student/examResult/' . $examId);
        }

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $this->examEngine->startExam((int)$participant['participant_id'], $ipAddress, $userAgent);

        $this->redirect('student/examRoom/' . $examId);
    }

    /**
     * Main Exam Room Page
     *
     * @param int|string $examId
     */
    public function examRoom($examId = 0)
    {
        $examId = (int)$examId;
        $user = $this->currentUser();
        $student = $this->getStudentProfile();
        $studentId = (int)($student['id'] ?? 0);

        $participant = $this->examEngine->getExamParticipant($examId, $studentId);

        if (!$participant) {
            Auth::setFlash('danger', 'Ujian tidak ditemukan atau Anda tidak terdaftar.');
            $this->redirect('student/exams');
        }

        // If submitted, prevent re-entry
        if ($participant['participant_status'] === 'submitted') {
            Auth::setFlash('info', 'Anda telah menyelesaikan ujian ini.');
            $this->redirect('student/examResult/' . $examId);
        }

        if ($participant['participant_status'] === 'terminated') {
            Auth::setFlash('danger', 'Sesi ujian Anda telah dibatalkan oleh pengawas.');
            $this->redirect('student/dashboard');
        }

        // If not started yet, automatically start it
        if ($participant['participant_status'] === 'not_started') {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $this->examEngine->startExam((int)$participant['participant_id'], $ipAddress, $userAgent);
            $participant = $this->examEngine->getExamParticipant($examId, $studentId);
        }

        // Calculate remaining seconds from server source of truth
        $remainingSeconds = $this->examEngine->calculateRemainingSeconds($participant, $participant);

        // If time expired, automatically submit
        if ($remainingSeconds <= 0) {
            $this->examEngine->submitExamAndCalculateScore((int)$participant['participant_id']);
            Auth::setFlash('warning', 'Waktu ujian telah berakhir. Jawaban Anda otomatis dikumpulkan oleh sistem.');
            $this->redirect('student/examResult/' . $examId);
        }

        // Fetch questions & saved answers (SECURITY: without correct_answers)
        $questions = $this->examEngine->getExamQuestionsAndSavedAnswers(
            $examId,
            (int)$participant['participant_id'],
            (bool)$participant['is_random_question'],
            (bool)$participant['is_random_option']
        );

        $data = [
            'page_title' => 'Ruang Ujian - ' . $participant['title'],
            'current_role' => 'student',
            'user' => $user,
            'student' => $student,
            'exam' => $participant,
            'questions' => $questions,
            'remaining_seconds' => $remainingSeconds
        ];

        $this->view('student/room', $data);
    }

    /**
     * AJAX endpoint to save answer in real-time
     */
    public function saveAnswer()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Metode request tidak diizinkan.'], 405);
        }

        // Verify CSRF Token
        $this->verifyCsrfToken(true);

        $student = $this->getStudentProfile();
        $studentId = (int)($student['id'] ?? 0);

        // Read POST parameters
        $examId = (int)($_POST['exam_id'] ?? 0);
        $questionId = (int)($_POST['question_id'] ?? 0);
        $optionId = !empty($_POST['option_id']) ? (int)$_POST['option_id'] : null;
        $essayAnswer = isset($_POST['essay_answer']) ? trim((string)$_POST['essay_answer']) : null;
        $isDoubt = !empty($_POST['is_doubt']) ? 1 : 0;

        $participant = $this->examEngine->getExamParticipant($examId, $studentId);
        if (!$participant) {
            $this->json(['success' => false, 'message' => 'Peserta tidak terdaftar.'], 403);
        }

        if ($participant['participant_status'] !== 'in_progress') {
            $this->json(['success' => false, 'message' => 'Ujian tidak sedang dalam status pengerjaan.'], 400);
        }

        $result = $this->examEngine->saveAnswer(
            (int)$participant['participant_id'],
            $questionId,
            $optionId,
            $essayAnswer,
            $isDoubt
        );

        $this->json($result);
    }

    /**
     * AJAX endpoint to get authoritative remaining exam time
     *
     * @param int|string $examId
     */
    public function examTime($examId = 0)
    {
        $examId = (int)$examId;
        $student = $this->getStudentProfile();
        $studentId = (int)($student['id'] ?? 0);

        $participant = $this->examEngine->getExamParticipant($examId, $studentId);
        if (!$participant) {
            $this->json(['success' => false, 'remaining_seconds' => 0, 'is_expired' => true], 404);
        }

        if ($participant['participant_status'] === 'submitted') {
            $this->json(['success' => true, 'remaining_seconds' => 0, 'is_expired' => true, 'is_submitted' => true]);
        }

        $remaining = $this->examEngine->calculateRemainingSeconds($participant, $participant);

        $this->json([
            'success' => true,
            'remaining_seconds' => $remaining,
            'is_expired' => ($remaining <= 0),
            'server_time' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Submit exam endpoint
     *
     * @param int|string $examId
     */
    public function submitExam($examId = 0)
    {
        $examId = (int)$examId;
        $student = $this->getStudentProfile();
        $studentId = (int)($student['id'] ?? 0);

        $participant = $this->examEngine->getExamParticipant($examId, $studentId);

        if (!$participant) {
            $this->redirect('student/exams');
        }

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                  || (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

        if ($participant['participant_status'] === 'submitted') {
            if ($isAjax) {
                $this->json([
                    'success' => true,
                    'message' => 'Ujian sudah pernah dikumpulkan.',
                    'redirect' => BASE_URL . '/student/examResult/' . $examId
                ]);
            }
            Auth::setFlash('info', 'Ujian ini sudah pernah dikumpulkan.');
            $this->redirect('student/examResult/' . $examId);
        }

        // Submit & calculate scores
        $submitRes = $this->examEngine->submitExamAndCalculateScore((int)$participant['participant_id']);

        // Trigger notifications if result is allowed to be shown
        if ($submitRes['success']) {
            try {
                $notifService = new NotificationService();
                if (!empty($participant['show_result'])) {
                    $notifService->notifyExamResultReady((int)$participant['participant_id']);
                }
            } catch (Throwable $t) {
                error_log("Notification error on student submitExam: " . $t->getMessage());
            }
        }

        if ($isAjax) {
            $this->json([
                'success' => $submitRes['success'],
                'message' => $submitRes['message'],
                'redirect' => BASE_URL . '/student/examResult/' . $examId
            ]);
        }

        Auth::setFlash('success', 'Ujian berhasil dikumpulkan. Hasil ujian Anda dapat dilihat di bawah ini.');
        $this->redirect('student/examResult/' . $examId);
    }

    /**
     * Exam result summary page
     *
     * @param int|string $examId
     */
    public function examResult($examId = 0)
    {
        $examId = (int)$examId;
        $user = $this->currentUser();
        $student = $this->getStudentProfile();
        $studentId = (int)($student['id'] ?? 0);

        $participant = $this->examEngine->getExamParticipant($examId, $studentId);

        if (!$participant) {
            Auth::setFlash('danger', 'Data ujian tidak ditemukan.');
            $this->redirect('student/exams');
        }

        if ($participant['participant_status'] !== 'submitted') {
            Auth::setFlash('warning', 'Anda belum menyelesaikan sesi ujian ini.');
            $this->redirect('student/examDetail/' . $examId);
        }

        $showResult = (int)($participant['show_result'] ?? 1);
        $result = $this->examEngine->getExamResult((int)$participant['participant_id']);

        $questionsReview = [];
        if ($showResult === 1) {
            $resultModel = $this->model('ResultModel');
            $answerReview = $resultModel->getAnswerReview((int)$participant['participant_id']);
            $questionsReview = $answerReview['questions'] ?? [];
        }

        $data = [
            'page_title' => 'Hasil Ujian - ' . $participant['title'],
            'current_role' => 'student',
            'user' => $user,
            'student' => $student,
            'exam' => $participant,
            'result' => $result,
            'show_result' => $showResult,
            'questions' => $questionsReview
        ];

        $this->view('student/result', $data);
    }

    /**
     * AJAX endpoint: AI Question Explainer (Penjelasan Tiap Soal Setelah Ujian Selesai)
     * Scoped strictly to the specific question, student's answer, and correct answer.
     */
    public function explainQuestion()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['status' => 'error', 'message' => 'Metode HTTP tidak diizinkan.'], 405);
        }

        $user = $this->currentUser();
        $student = $this->getStudentProfile();
        $studentId = (int)($student['id'] ?? 0);

        // Read input from JSON or POST
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true) ?: $_POST;

        $participantId = (int)($input['participant_id'] ?? 0);
        $questionId = (int)($input['question_id'] ?? 0);
        $message = trim((string)($input['message'] ?? ''));
        $history = (array)($input['history'] ?? []);

        if ($participantId <= 0 || $questionId <= 0) {
            $this->json(['status' => 'error', 'message' => 'Parameter sesi atau soal tidak valid.'], 400);
        }

        // Verify that this participant attempt belongs to this student
        $db = Database::getInstance();
        $participant = $db->query("
            SELECT ep.id AS participant_id, ep.exam_id, ep.status AS participant_status, e.show_result
            FROM exam_participants ep
            JOIN exams e ON ep.exam_id = e.id
            WHERE ep.id = :pid AND ep.student_id = :sid
            LIMIT 1
        ")->bind(':pid', $participantId)->bind(':sid', $studentId)->single();

        if (!$participant) {
            $this->json(['status' => 'error', 'message' => 'Akses ditolak: Data pengerjaan tidak ditemukan.'], 403);
        }

        if ($participant['participant_status'] !== 'submitted') {
            $this->json(['status' => 'error', 'message' => 'Penjelasan AI hanya tersedia setelah ujian selesai dikumpulkan.'], 403);
        }

        if ((int)$participant['show_result'] !== 1) {
            $this->json(['status' => 'error', 'message' => 'Hasil ujian dan pembahasan belum dipublikasikan oleh guru penguji.'], 403);
        }

        // Fetch question details securely
        $resultModel = $this->model('ResultModel');
        $review = $resultModel->getAnswerReview($participantId);
        if (!$review || empty($review['questions'])) {
            $this->json(['status' => 'error', 'message' => 'Data soal tidak ditemukan.'], 404);
        }

        $targetQuestion = null;
        foreach ($review['questions'] as $q) {
            if ((int)$q['question_id'] === $questionId) {
                $targetQuestion = $q;
                break;
            }
        }

        if (!$targetQuestion) {
            $this->json(['status' => 'error', 'message' => 'Soal tidak ditemukan pada ujian ini.'], 404);
        }

        // Prepare scoped question data
        $studentAnswerLabel = '(Belum dijawab)';
        $correctAnswerLabel = '-';
        if ($targetQuestion['question_type'] === 'multiple_choice') {
            foreach ($targetQuestion['options'] as $opt) {
                if ((int)$opt['id'] === (int)($targetQuestion['selected_option_id'] ?? 0)) {
                    $studentAnswerLabel = $opt['option_label'] . '. ' . $opt['option_text'];
                }
                if ((int)$opt['is_correct'] === 1) {
                    $correctAnswerLabel = $opt['option_label'] . '. ' . $opt['option_text'];
                }
            }
        } else {
            $studentAnswerLabel = $targetQuestion['answer']['essay_answer'] ?? '(Kosong)';
            $correctAnswerLabel = 'Jawaban Terbuka (Rubrik Penilaian Guru)';
        }

        $questionPayload = [
            'question_id' => $targetQuestion['question_id'],
            'question_text' => $targetQuestion['question_text'],
            'question_type' => $targetQuestion['question_type'],
            'options' => $targetQuestion['options'] ?? [],
            'student_answer_label' => $studentAnswerLabel,
            'correct_answer_label' => $correctAnswerLabel,
            'is_correct' => !empty($targetQuestion['answer']['is_correct']),
            'explanation' => $targetQuestion['explanation'] ?? ''
        ];

        // Call Question Explainer engine
        $aiResponse = ChatbotService::explainQuestion($questionPayload, $message, $history);

        $this->json([
            'status' => 'success',
            'data' => $aiResponse,
            'question_meta' => [
                'question_id' => $targetQuestion['question_id'],
                'student_answer' => $studentAnswerLabel,
                'correct_answer' => $correctAnswerLabel,
                'is_correct' => !empty($targetQuestion['answer']['is_correct'])
            ]
        ]);
    }

    /**
     * AJAX endpoint to log AI Proctoring events with debounce & privacy controls
     */
    public function logProctoringEvent()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.'], 405);
        }

        $student = $this->getStudentProfile();
        $studentId = (int)($student['id'] ?? 0);

        // Verify CSRF token
        $this->verifyCsrfToken(true);

        // Read input from JSON body or standard $_POST
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true) ?: $_POST;

        $examId = (int)($input['exam_id'] ?? 0);
        $eventType = strtoupper(trim((string)($input['event_type'] ?? '')));
        $metadata = isset($input['metadata']) && is_array($input['metadata']) ? $input['metadata'] : [];
        $evidenceBase64 = isset($input['evidence_base64']) ? (string)$input['evidence_base64'] : null;
        $cameraSource = !empty($input['camera_source']) ? (string)$input['camera_source'] : 'primary_camera';

        // 1. SECURITY HARDENING: Whitelist allowed event types
        $allowedEvents = [
            'FACE_NOT_DETECTED',
            'MULTIPLE_FACE',
            'CAMERA_DISCONNECTED',
            'CAMERA_BLOCKED',
            'SUSPICIOUS_GAZE',
            'FACE_CHANGED',
            'TAB_SWITCH'
        ];

        if (empty($eventType) || !in_array($eventType, $allowedEvents, true)) {
            $this->json(['success' => false, 'message' => 'event_type tidak valid atau tidak diizinkan.'], 400);
        }

        // 2. SECURITY HARDENING: Server policy dictates severity (client cannot tamper)
        $severityMap = [
            'FACE_NOT_DETECTED' => 'WARNING',
            'MULTIPLE_FACE' => 'CRITICAL',
            'CAMERA_DISCONNECTED' => 'WARNING',
            'CAMERA_BLOCKED' => 'WARNING',
            'SUSPICIOUS_GAZE' => 'WARNING',
            'FACE_CHANGED' => 'CRITICAL',
            'TAB_SWITCH' => 'WARNING'
        ];
        $severity = $severityMap[$eventType] ?? 'WARNING';

        $participant = $this->examEngine->getExamParticipant($examId, $studentId);
        if (!$participant) {
            $this->json(['success' => false, 'message' => 'Peserta ujian tidak ditemukan.'], 404);
        }

        // Only log during active exam
        if ($participant['participant_status'] !== 'in_progress') {
            $this->json(['success' => false, 'message' => 'Ujian tidak sedang dalam status pengerjaan.'], 400);
        }

        $participantId = (int)$participant['participant_id'];

        // 3. SECURITY HARDENING: Validated evidence snapshot handling
        $snapshotPath = null;
        if (!empty($evidenceBase64)) {
            try {
                // Strip data uri scheme if present
                if (preg_match('/^data:image\/(\w+);base64,/', $evidenceBase64, $type)) {
                    $evidenceData = substr($evidenceBase64, strpos($evidenceBase64, ',') + 1);
                    $evidenceData = base64_decode($evidenceData, true);
                    if ($evidenceData !== false && strlen($evidenceData) <= 600000) { // Limit to ~600KB
                        $imgInfo = @getimagesizefromstring($evidenceData);
                        if ($imgInfo !== false && in_array($imgInfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG])) {
                            $dir = __DIR__ . '/../../public/uploads/proctoring';
                            if (!is_dir($dir)) {
                                mkdir($dir, 0755, true);
                            }
                            $cleanType = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $eventType));
                            $filename = 'snap_' . $participantId . '_' . $cleanType . '_' . time() . '_' . rand(100, 999) . '.jpg';
                            $fullPath = $dir . '/' . $filename;
                            if (file_put_contents($fullPath, $evidenceData) !== false) {
                                $snapshotPath = 'uploads/proctoring/' . $filename;
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                // If snapshot saving fails, proceed with event logging without snapshot
            }
        }

        /** @var ProctoringModel $proctoringModel */
        $proctoringModel = $this->model('ProctoringModel');
        $cooldown = ($severity === 'CRITICAL') ? 20 : 15;

        $logResult = $proctoringModel->logEvent(
            $participantId,
            $examId,
            $studentId,
            $eventType,
            $severity,
            $metadata,
            $snapshotPath,
            1.00,
            $cameraSource,
            $cooldown
        );

        $this->json([
            'success' => true,
            'logged' => $logResult['logged'] ?? false,
            'event_id' => $logResult['event_id'] ?? null,
            'reason' => $logResult['reason'] ?? null,
            'severity' => $severity,
            'cooldown_active' => !($logResult['logged'] ?? false)
        ]);
    }
}

