<?php
/**
 * Teacher Controller (Panel Guru & Bank Soal)
 * AI Online Exam & Proctoring System
 */

class TeacherController extends Controller
{
    protected QuestionModel $questionModel;
    protected SubjectModel $subjectModel;
    protected TeacherModel $teacherModel;
    protected ExamModel $examModel;
    protected ClassModel $classModel;
    protected ResultModel $resultModel;

    public function __construct()
    {
        // Enforce teacher role authorization
        $this->requireAuth(['teacher']);

        $this->questionModel = $this->model('QuestionModel');
        $this->subjectModel = $this->model('SubjectModel');
        $this->teacherModel = $this->model('TeacherModel');
        $this->examModel = $this->model('ExamModel');
        $this->classModel = $this->model('ClassModel');
        $this->resultModel = $this->model('ResultModel');
    }

    /**
     * Helper to get current teacher profile and ID
     *
     * @return array
     */
    protected function getTeacherProfile(): array
    {
        $user = $this->currentUser();
        $db = Database::getInstance();

        $teacher = $db->query("SELECT id, nip, specialization FROM teachers WHERE user_id = :uid LIMIT 1")
            ->bind(':uid', $user['id'])
            ->single();

        if (!$teacher) {
            // Self-repair or handle missing teacher record
            $db->query("INSERT INTO teachers (user_id, nip) VALUES (:uid, :nip)")
                ->bind(':uid', $user['id'])
                ->bind(':nip', 'T-' . str_pad((string)$user['id'], 6, '0', STR_PAD_LEFT))
                ->execute();

            $teacher = $db->query("SELECT id, nip, specialization FROM teachers WHERE user_id = :uid LIMIT 1")
                ->bind(':uid', $user['id'])
                ->single();
        }

        return $teacher;
    }

    // ==========================================================
    // 1. DASHBOARD GURU
    // ==========================================================
    public function index()
    {
        $this->dashboard();
    }

    public function dashboard()
    {
        $user = $this->currentUser();
        $teacher = $this->getTeacherProfile();
        $teacherId = (int)$teacher['id'];
        $db = Database::getInstance();

        // Statistics
        $myQuestions = $this->questionModel->countByTeacher($teacherId);
        $examStats = $this->examModel->countByTeacherGroupedStatus($teacherId);
        $totalSubjects = $db->query("SELECT COUNT(*) FROM subjects")->single();

        // Recent 5 exams
        $recentExams = $this->examModel->getByTeacher($teacherId);
        $recentExams = array_slice($recentExams, 0, 5);

        // Recent 5 questions
        $recentQuestions = $this->questionModel->getByTeacher($teacherId, '', null, '');
        $recentQuestions = array_slice($recentQuestions, 0, 5);

        $data = [
            'page_title' => 'Dashboard Guru & Penguji',
            'current_role' => 'teacher',
            'user' => $user,
            'teacher' => $teacher,
            'recent_exams' => $recentExams,
            'recent_questions' => $recentQuestions,
            'stats' => [
                'my_questions' => $myQuestions,
                'total_subjects' => array_values($totalSubjects)[0] ?? 0,
                'exam_draft' => $examStats['draft'] ?? 0,
                'exam_published' => $examStats['published'] ?? 0,
                'exam_running' => $examStats['running'] ?? 0,
                'exam_finished' => $examStats['finished'] ?? 0,
                'exam_total' => $examStats['total'] ?? 0,
            ]
        ];

        $this->view('teacher/dashboard', $data);
    }

    // ==========================================================
    // 2. BANK SOAL (QUESTION BANK CRUD)
    // ==========================================================
    public function questions()
    {
        $teacher = $this->getTeacherProfile();
        $teacherId = (int)$teacher['id'];

        $search = trim($_GET['q'] ?? '');
        $subjectId = !empty($_GET['subject_id']) ? (int)$_GET['subject_id'] : null;
        $type = trim($_GET['type'] ?? '');
        $difficulty = trim($_GET['difficulty'] ?? '');

        $questions = $this->questionModel->getByTeacher($teacherId, $search, $subjectId, $type, $difficulty);
        $subjects = $this->subjectModel->getAll();

        $data = [
            'page_title' => 'Bank Soal Saya - Panel Guru',
            'questions' => $questions,
            'subjects' => $subjects,
            'search' => $search,
            'subject_filter' => $subjectId,
            'type_filter' => $type,
            'difficulty_filter' => $difficulty
        ];

        $this->view('teacher/questions/index', $data);
    }

    public function questionShow($id = null)
    {
        if (!$id) $this->redirect('teacher/questions');
        $teacher = $this->getTeacherProfile();
        $teacherId = (int)$teacher['id'];

        $question = $this->questionModel->findByIdAndTeacher((int)$id, $teacherId);
        if (!$question) {
            Auth::setFlash('danger', 'Soal tidak ditemukan atau Anda tidak memiliki akses ke butir soal ini.');
            $this->redirect('teacher/questions');
        }

        $data = [
            'page_title' => 'Detail Butir Soal #' . $question['id'],
            'question' => $question
        ];

        $this->view('teacher/questions/show', $data);
    }

    public function questionCreate()
    {
        $teacher = $this->getTeacherProfile();
        $teacherId = (int)$teacher['id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $subjectId = !empty($_POST['subject_id']) ? (int)$_POST['subject_id'] : null;
            $questionText = trim($_POST['question_text'] ?? '');
            $questionType = trim($_POST['question_type'] ?? 'multiple_choice');
            $difficulty = trim($_POST['difficulty'] ?? 'medium');
            $defaultScore = !empty($_POST['default_score']) ? (float)$_POST['default_score'] : 1.00;
            $explanation = trim($_POST['explanation'] ?? '');

            // Options payload for multiple choice
            $options = $_POST['options'] ?? [];
            $correctOption = trim($_POST['correct_option'] ?? 'A');

            // Validation
            if (empty($subjectId) || empty($questionText)) {
                Auth::setFlash('danger', 'Mata pelajaran dan teks pertanyaan wajib diisi.');
                $this->redirect('teacher/questionCreate');
            }

            if ($questionType === 'multiple_choice') {
                if (empty($options['A']) || empty($options['B']) || empty($options['C']) || empty($options['D'])) {
                    Auth::setFlash('danger', 'Untuk soal pilihan ganda, minimal Opsi A, B, C, dan D wajib diisi.');
                    $this->redirect('teacher/questionCreate');
                }
            }

            try {
                $this->questionModel->createQuestion($teacherId, [
                    'subject_id' => $subjectId,
                    'question_text' => $questionText,
                    'question_type' => $questionType,
                    'difficulty' => $difficulty,
                    'default_score' => $defaultScore,
                    'explanation' => $explanation,
                    'options' => $options,
                    'correct_option' => $correctOption
                ]);

                Auth::setFlash('success', 'Butir soal berhasil ditambahkan ke Bank Soal Anda.');
                $this->redirect('teacher/questions');
            } catch (Exception $e) {
                Auth::setFlash('danger', 'Gagal menambahkan soal: ' . $e->getMessage());
                $this->redirect('teacher/questionCreate');
            }
        }

        $subjects = $this->subjectModel->getAll();
        $data = [
            'page_title' => 'Tambah Butir Soal Baru - Panel Guru',
            'is_edit' => false,
            'subjects' => $subjects
        ];

        $this->view('teacher/questions/form', $data);
    }

    public function questionEdit($id = null)
    {
        if (!$id) $this->redirect('teacher/questions');
        $teacher = $this->getTeacherProfile();
        $teacherId = (int)$teacher['id'];

        $question = $this->questionModel->findByIdAndTeacher((int)$id, $teacherId);
        if (!$question) {
            Auth::setFlash('danger', 'Soal tidak ditemukan atau Anda tidak memiliki akses ke butir soal ini.');
            $this->redirect('teacher/questions');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $subjectId = !empty($_POST['subject_id']) ? (int)$_POST['subject_id'] : null;
            $questionText = trim($_POST['question_text'] ?? '');
            $questionType = trim($_POST['question_type'] ?? 'multiple_choice');
            $difficulty = trim($_POST['difficulty'] ?? 'medium');
            $defaultScore = !empty($_POST['default_score']) ? (float)$_POST['default_score'] : 1.00;
            $explanation = trim($_POST['explanation'] ?? '');

            $options = $_POST['options'] ?? [];
            $correctOption = trim($_POST['correct_option'] ?? 'A');

            if (empty($subjectId) || empty($questionText)) {
                Auth::setFlash('danger', 'Mata pelajaran dan teks pertanyaan wajib diisi.');
                $this->redirect('teacher/questionEdit/' . $id);
            }

            if ($questionType === 'multiple_choice') {
                if (empty($options['A']) || empty($options['B']) || empty($options['C']) || empty($options['D'])) {
                    Auth::setFlash('danger', 'Untuk soal pilihan ganda, minimal Opsi A, B, C, dan D wajib diisi.');
                    $this->redirect('teacher/questionEdit/' . $id);
                }
            }

            try {
                $this->questionModel->updateQuestion((int)$id, $teacherId, [
                    'subject_id' => $subjectId,
                    'question_text' => $questionText,
                    'question_type' => $questionType,
                    'difficulty' => $difficulty,
                    'default_score' => $defaultScore,
                    'explanation' => $explanation,
                    'options' => $options,
                    'correct_option' => $correctOption
                ]);

                Auth::setFlash('success', 'Butir soal berhasil diperbarui.');
                $this->redirect('teacher/questions');
            } catch (Exception $e) {
                Auth::setFlash('danger', 'Gagal memperbarui soal: ' . $e->getMessage());
                $this->redirect('teacher/questionEdit/' . $id);
            }
        }

        $subjects = $this->subjectModel->getAll();
        $data = [
            'page_title' => 'Edit Butir Soal #' . $question['id'],
            'is_edit' => true,
            'question' => $question,
            'subjects' => $subjects
        ];

        $this->view('teacher/questions/form', $data);
    }

    public function questionDelete($id = null)
    {
        if (!$id) $this->redirect('teacher/questions');
        $teacher = $this->getTeacherProfile();
        $teacherId = (int)$teacher['id'];

        $deleted = $this->questionModel->deleteQuestion((int)$id, $teacherId);
        if ($deleted) {
            Auth::setFlash('success', 'Butir soal berhasil dihapus dari Bank Soal.');
        } else {
            Auth::setFlash('danger', 'Soal tidak ditemukan atau Anda tidak memiliki akses untuk menghapusnya.');
        }

        $this->redirect('teacher/questions');
    }

    // ==========================================================
    // 3. EXAM MANAGEMENT (MANAJEMEN UJIAN)
    // ==========================================================
    public function exams()
    {
        $teacher = $this->getTeacherProfile();
        $teacherId = (int)$teacher['id'];

        $search = trim($_GET['q'] ?? '');
        $subjectId = !empty($_GET['subject_id']) ? (int)$_GET['subject_id'] : null;
        $status = trim($_GET['status'] ?? '');

        $exams = $this->examModel->getByTeacher($teacherId, $search, $subjectId, $status);
        $subjects = $this->subjectModel->getAll();
        $stats = $this->examModel->countByTeacherGroupedStatus($teacherId);

        $data = [
            'page_title' => 'Manajemen Ujian - Panel Guru',
            'exams' => $exams,
            'subjects' => $subjects,
            'stats' => $stats,
            'search' => $search,
            'subject_filter' => $subjectId,
            'status_filter' => $status
        ];

        $this->view('teacher/exams/index', $data);
    }

    public function examShow($id = null)
    {
        if (!$id) $this->redirect('teacher/exams');
        $teacher = $this->getTeacherProfile();
        $teacherId = (int)$teacher['id'];

        $exam = $this->examModel->findByIdAndTeacher((int)$id, $teacherId);
        if (!$exam) {
            Auth::setFlash('danger', 'Sesi ujian tidak ditemukan atau Anda tidak memiliki hak akses.');
            $this->redirect('teacher/exams');
        }

        $data = [
            'page_title' => 'Preview Sesi Ujian: ' . $exam['title'],
            'exam' => $exam
        ];

        $this->view('teacher/exams/show', $data);
    }

    public function examCreate()
    {
        $teacher = $this->getTeacherProfile();
        $teacherId = (int)$teacher['id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title'] ?? '');
            $subjectId = !empty($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
            $classId = !empty($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
            $description = trim($_POST['description'] ?? '');
            $durationMinutes = !empty($_POST['duration_minutes']) ? (int)$_POST['duration_minutes'] : 0;
            $startTime = trim($_POST['start_time'] ?? '');
            $endTime = trim($_POST['end_time'] ?? '');
            $status = trim($_POST['status'] ?? 'draft');
            $isRandomQuestion = isset($_POST['is_random_question']) ? 1 : 0;
            $isRandomOption = isset($_POST['is_random_option']) ? 1 : 0;
            $enableProctoring = isset($_POST['enable_proctoring']) ? 1 : 0;
            $passingScore = !empty($_POST['passing_score']) ? (float)$_POST['passing_score'] : 75.00;
            $token = trim($_POST['token'] ?? '');
            $questionIds = $_POST['questions'] ?? [];

            // Validation rules
            if (empty($title)) {
                Auth::setFlash('danger', 'Judul ujian wajib diisi.');
                $this->redirect('teacher/examCreate');
            }

            if (empty($subjectId)) {
                Auth::setFlash('danger', 'Mata pelajaran wajib dipilih.');
                $this->redirect('teacher/examCreate');
            }

            if (empty($classId)) {
                Auth::setFlash('danger', 'Kelas sasaran wajib dipilih.');
                $this->redirect('teacher/examCreate');
            }

            if ($durationMinutes <= 0) {
                Auth::setFlash('danger', 'Durasi ujian harus lebih dari 0 menit.');
                $this->redirect('teacher/examCreate');
            }

            if (empty($startTime) || empty($endTime)) {
                Auth::setFlash('danger', 'Jadwal waktu mulai dan waktu selesai wajib diisi.');
                $this->redirect('teacher/examCreate');
            }

            if (strtotime($startTime) >= strtotime($endTime)) {
                Auth::setFlash('danger', 'Waktu mulai ujian harus lebih awal daripada waktu selesai.');
                $this->redirect('teacher/examCreate');
            }

            if (empty($questionIds) || !is_array($questionIds)) {
                Auth::setFlash('danger', 'Minimal pilih 1 butir soal dari Bank Soal untuk ujian ini.');
                $this->redirect('teacher/examCreate');
            }

            try {
                $examId = $this->examModel->createExamWithQuestions($teacherId, [
                    'title' => $title,
                    'subject_id' => $subjectId,
                    'description' => $description,
                    'duration_minutes' => $durationMinutes,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'token' => $token,
                    'is_random_question' => $isRandomQuestion,
                    'is_random_option' => $isRandomOption,
                    'enable_proctoring' => $enableProctoring,
                    'status' => in_array($status, ['draft', 'published', 'running', 'finished']) ? $status : 'draft',
                    'total_score' => 100.00,
                    'passing_score' => $passingScore
                ], $questionIds, $classId);

                // Trigger notification & email to students if exam is immediately published
                if ($status === 'published') {
                    try {
                        $notifService = new NotificationService();
                        $notifService->notifyExamPublished((int)$examId);
                    } catch (Throwable $t) {
                        error_log("Notification error on examCreate: " . $t->getMessage());
                    }
                }

                Auth::setFlash('success', 'Sesi ujian berhasil dibuat dan ' . count($questionIds) . ' butir soal berhasil di-assign ke kelas.');
                $this->redirect('teacher/exams');
            } catch (Exception $e) {
                Auth::setFlash('danger', 'Gagal membuat sesi ujian: ' . $e->getMessage());
                $this->redirect('teacher/examCreate');
            }
        }

        $subjects = $this->subjectModel->getAll();
        $classes = $this->classModel->getAll();
        $availableQuestions = $this->questionModel->getByTeacher($teacherId);

        $examDefaults = class_exists('SettingModel') ? SettingModel::getExamDefaults() : [
            'duration_minutes' => 90,
            'passing_score' => 75.00,
            'is_random_question' => 1,
            'is_random_option' => 1,
            'enable_proctoring' => 1,
            'show_result' => 1
        ];

        $data = [
            'page_title' => 'Buat Sesi Ujian Baru - Panel Guru',
            'is_edit' => false,
            'exam' => $examDefaults,
            'subjects' => $subjects,
            'classes' => $classes,
            'available_questions' => $availableQuestions,
            'selected_questions' => []
        ];

        $this->view('teacher/exams/form', $data);
    }

    public function examEdit($id = null)
    {
        if (!$id) $this->redirect('teacher/exams');
        $teacher = $this->getTeacherProfile();
        $teacherId = (int)$teacher['id'];

        $exam = $this->examModel->findByIdAndTeacher((int)$id, $teacherId);
        if (!$exam) {
            Auth::setFlash('danger', 'Sesi ujian tidak ditemukan atau Anda tidak memiliki hak akses.');
            $this->redirect('teacher/exams');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title'] ?? '');
            $subjectId = !empty($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
            $classId = !empty($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
            $description = trim($_POST['description'] ?? '');
            $durationMinutes = !empty($_POST['duration_minutes']) ? (int)$_POST['duration_minutes'] : 0;
            $startTime = trim($_POST['start_time'] ?? '');
            $endTime = trim($_POST['end_time'] ?? '');
            $status = trim($_POST['status'] ?? 'draft');
            $isRandomQuestion = isset($_POST['is_random_question']) ? 1 : 0;
            $isRandomOption = isset($_POST['is_random_option']) ? 1 : 0;
            $enableProctoring = isset($_POST['enable_proctoring']) ? 1 : 0;
            $passingScore = !empty($_POST['passing_score']) ? (float)$_POST['passing_score'] : 75.00;
            $questionIds = $_POST['questions'] ?? [];

            if (empty($title)) {
                Auth::setFlash('danger', 'Judul ujian wajib diisi.');
                $this->redirect('teacher/examEdit/' . $id);
            }

            if (empty($subjectId)) {
                Auth::setFlash('danger', 'Mata pelajaran wajib dipilih.');
                $this->redirect('teacher/examEdit/' . $id);
            }

            if (empty($classId)) {
                Auth::setFlash('danger', 'Kelas sasaran wajib dipilih.');
                $this->redirect('teacher/examEdit/' . $id);
            }

            if ($durationMinutes <= 0) {
                Auth::setFlash('danger', 'Durasi ujian harus lebih dari 0 menit.');
                $this->redirect('teacher/examEdit/' . $id);
            }

            if (empty($startTime) || empty($endTime)) {
                Auth::setFlash('danger', 'Jadwal waktu mulai dan waktu selesai wajib diisi.');
                $this->redirect('teacher/examEdit/' . $id);
            }

            if (strtotime($startTime) >= strtotime($endTime)) {
                Auth::setFlash('danger', 'Waktu mulai ujian harus lebih awal daripada waktu selesai.');
                $this->redirect('teacher/examEdit/' . $id);
            }

            if (empty($questionIds) || !is_array($questionIds)) {
                Auth::setFlash('danger', 'Minimal pilih 1 butir soal dari Bank Soal untuk ujian ini.');
                $this->redirect('teacher/examEdit/' . $id);
            }

            try {
                $this->examModel->updateExamWithQuestions((int)$id, $teacherId, [
                    'title' => $title,
                    'subject_id' => $subjectId,
                    'description' => $description,
                    'duration_minutes' => $durationMinutes,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'is_random_question' => $isRandomQuestion,
                    'is_random_option' => $isRandomOption,
                    'enable_proctoring' => $enableProctoring,
                    'status' => in_array($status, ['draft', 'published', 'running', 'finished']) ? $status : 'draft',
                    'total_score' => 100.00,
                    'passing_score' => $passingScore
                ], $questionIds, $classId);

                // Trigger notifications for schedule changes or publish status transition
                try {
                    $notifService = new NotificationService();
                    if ($startTime !== $exam['start_time'] || $endTime !== $exam['end_time']) {
                        $notifService->notifyExamScheduleChanged((int)$id, $exam['start_time'], $startTime, $exam['end_time'], $endTime);
                    }
                    if ($exam['status'] !== 'published' && $status === 'published') {
                        $notifService->notifyExamPublished((int)$id);
                    }
                } catch (Throwable $t) {
                    error_log("Notification error on examEdit: " . $t->getMessage());
                }

                Auth::setFlash('success', 'Sesi ujian berhasil diperbarui.');
                $this->redirect('teacher/exams');
            } catch (Exception $e) {
                Auth::setFlash('danger', 'Gagal memperbarui sesi ujian: ' . $e->getMessage());
                $this->redirect('teacher/examEdit/' . $id);
            }
        }

        $subjects = $this->subjectModel->getAll();
        $classes = $this->classModel->getAll();
        $availableQuestions = $this->questionModel->getByTeacher($teacherId);
        
        $selectedQuestionIds = array_column($exam['questions'] ?? [], 'id');

        $data = [
            'page_title' => 'Edit Sesi Ujian: ' . $exam['title'],
            'is_edit' => true,
            'exam' => $exam,
            'subjects' => $subjects,
            'classes' => $classes,
            'available_questions' => $availableQuestions,
            'selected_questions' => $selectedQuestionIds
        ];

        $this->view('teacher/exams/form', $data);
    }

    public function examDelete($id = null)
    {
        if (!$id) $this->redirect('teacher/exams');
        $teacher = $this->getTeacherProfile();
        $teacherId = (int)$teacher['id'];

        $exam = $this->examModel->findByIdAndTeacher((int)$id, $teacherId);

        $deleted = $this->examModel->deleteExam((int)$id, $teacherId);
        if ($deleted) {
            // Notify students that the exam was cancelled
            if ($exam) {
                try {
                    $notifService = new NotificationService();
                    $notifService->notifyExamCancelled((int)$id, $exam['title'], (int)$exam['class_id']);
                } catch (Throwable $t) {
                    error_log("Notification error on examDelete: " . $t->getMessage());
                }
            }
            Auth::setFlash('success', 'Sesi ujian berhasil dihapus.');
        } else {
            Auth::setFlash('danger', 'Ujian tidak ditemukan atau Anda tidak memiliki akses untuk menghapusnya.');
        }

        $this->redirect('teacher/exams');
    }

    public function examTogglePublish($id = null)
    {
        if (!$id) $this->redirect('teacher/exams');
        $teacher = $this->getTeacherProfile();
        $teacherId = (int)$teacher['id'];

        $exam = $this->examModel->findByIdAndTeacher((int)$id, $teacherId);
        if (!$exam) {
            Auth::setFlash('danger', 'Ujian tidak ditemukan atau Anda tidak memiliki hak akses.');
            $this->redirect('teacher/exams');
        }

        $newStatus = ($exam['status'] === 'published') ? 'draft' : 'published';
        $this->examModel->updateStatus((int)$id, $teacherId, $newStatus);

        // Notify enrolled students when published
        if ($newStatus === 'published') {
            try {
                $notifService = new NotificationService();
                $notifService->notifyExamPublished((int)$id);
            } catch (Throwable $t) {
                error_log("Notification error on examTogglePublish: " . $t->getMessage());
            }
        }

        $statusLabel = ($newStatus === 'published') ? 'dipublikasikan (Published)' : 'diubah menjadi Draft';
        Auth::setFlash('success', 'Status ujian berhasil ' . $statusLabel . '.');
        $this->redirect('teacher/exams');
    }

    // ==========================================================
    // 5. MODUL HASIL UJIAN & PENILAIAN ESSAY (TAHAP 8)
    // ==========================================================

    /**
     * List of all exams with results summary for teacher
     */
    public function results()
    {
        $teacher = $this->getTeacherProfile();
        $teacherId = (int)$teacher['id'];

        $search = trim((string)($_GET['search'] ?? ''));
        $examsSummary = $this->resultModel->getTeacherExamsSummary($teacherId, $search);

        $data = [
            'page_title' => 'Hasil Ujian & Analitik',
            'current_role' => 'teacher',
            'user' => $this->currentUser(),
            'teacher' => $teacher,
            'exams' => $examsSummary,
            'search' => $search
        ];

        $this->view('teacher/results/index', $data);
    }

    /**
     * Detailed participant results and statistics for a specific exam
     *
     * @param int|string $examId
     */
    public function examResultDetail($examId = 0)
    {
        $examId = (int)$examId;
        $teacher = $this->getTeacherProfile();
        $teacherId = (int)$teacher['id'];

        $filter = $_GET['filter'] ?? 'all';
        $search = trim((string)($_GET['search'] ?? ''));

        $detail = $this->resultModel->getExamResultDetail($examId, $teacherId, $filter, $search);

        if (!$detail) {
            Auth::setFlash('danger', 'Data ujian tidak ditemukan atau Anda tidak memiliki hak akses.');
            $this->redirect('teacher/results');
        }

        $data = [
            'page_title' => 'Hasil Ujian: ' . $detail['exam']['title'],
            'current_role' => 'teacher',
            'user' => $this->currentUser(),
            'teacher' => $teacher,
            'exam' => $detail['exam'],
            'stats' => $detail['stats'],
            'participants' => $detail['participants'],
            'current_filter' => $filter,
            'search' => $search
        ];

        $this->view('teacher/results/detail', $data);
    }

    /**
     * Review student answers and grade essay questions
     *
     * @param int|string $participantId
     */
    public function reviewAnswers($participantId = 0)
    {
        $participantId = (int)$participantId;
        $teacher = $this->getTeacherProfile();
        $teacherId = (int)$teacher['id'];

        $review = $this->resultModel->getAnswerReview($participantId, $teacherId);

        if (!$review) {
            Auth::setFlash('danger', 'Data jawaban peserta tidak ditemukan atau Anda tidak memiliki hak akses.');
            $this->redirect('teacher/results');
        }

        /** @var ProctoringModel $proctoringModel */
        $proctoringModel = $this->model('ProctoringModel');
        $proctoringEvents = $proctoringModel->getEventsByParticipant($participantId);
        $proctoringSummary = $proctoringModel->getRiskSummary($participantId);

        $data = [
            'page_title' => 'Review Jawaban - ' . $review['header']['student_name'],
            'current_role' => 'teacher',
            'user' => $this->currentUser(),
            'teacher' => $teacher,
            'header' => $review['header'],
            'questions' => $review['questions'],
            'proctoring_events' => $proctoringEvents,
            'proctoring_summary' => $proctoringSummary
        ];

        $this->view('teacher/results/review', $data);
    }

    /**
     * Save manual essay grade (POST)
     */
    public function saveEssayGrade()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('teacher/results');
        }

        // Verify CSRF token
        $this->verifyCsrfToken(true);

        $teacher = $this->getTeacherProfile();
        $teacherId = (int)$teacher['id'];

        $participantId = (int)($_POST['participant_id'] ?? 0);
        $questionId = (int)($_POST['question_id'] ?? 0);
        $score = (float)($_POST['score'] ?? 0.0);

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                  || (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

        $res = $this->resultModel->gradeEssayAnswer($participantId, $questionId, $score, $teacherId);

        if ($isAjax) {
            $this->json($res);
        }

        if ($res['success']) {
            Auth::setFlash('success', $res['message']);
        } else {
            Auth::setFlash('danger', $res['message']);
        }

        $this->redirect('teacher/reviewAnswers/' . $participantId);
    }

    /**
     * Toggle result visibility to students
     *
     * @param int|string $examId
     */
    public function toggleResultVisibility($examId = 0)
    {
        // Verify CSRF token
        $this->verifyCsrfToken(true);
        $examId = (int)$examId;
        $teacher = $this->getTeacherProfile();
        $teacherId = (int)$teacher['id'];

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        $res = $this->resultModel->toggleResultVisibility($examId, $teacherId);

        // Notify participants when results are enabled (show_result = 1)
        if ($res['success'] && !empty($res['show_result'])) {
            try {
                $notifService = new NotificationService();
                $db = Database::getInstance();
                $participants = $db->query("
                    SELECT id FROM exam_participants 
                    WHERE exam_id = :eid AND status = 'submitted'
                ")->bind(':eid', $examId)->resultSet();

                foreach ($participants as $p) {
                    $notifService->notifyExamResultReady((int)$p['id']);
                }
            } catch (Throwable $t) {
                error_log("Notification error on toggleResultVisibility: " . $t->getMessage());
            }
        }

        if ($isAjax) {
            $this->json($res);
        }

        if ($res['success']) {
            Auth::setFlash('success', $res['message']);
        } else {
            Auth::setFlash('danger', $res['message']);
        }

        $this->redirect('teacher/examResultDetail/' . $examId);
    }

    /**
     * Teacher Live Exam Monitoring Page (Tahap 11)
     *
     * @param int|string $examId
     */
    public function monitoring($examId = 0)
    {
        $examId = (int)$examId;
        $teacher = $this->getTeacherProfile();
        $teacherId = (int)$teacher['id'];
        $db = Database::getInstance();

        // 1. Fetch all teacher's exams for the selector dropdown
        $exams = $db->query("
            SELECT e.id, e.title, e.status, e.enable_proctoring, e.duration_minutes, e.start_time, e.end_time,
                   c.name AS class_name, s.name AS subject_name,
                   (SELECT COUNT(*) FROM exam_participants ep WHERE ep.exam_id = e.id) AS participant_count,
                   (SELECT COUNT(*) FROM exam_participants ep WHERE ep.exam_id = e.id AND ep.status = 'in_progress') AS active_count
            FROM exams e
            JOIN classes c ON e.class_id = c.id
            JOIN subjects s ON e.subject_id = s.id
            WHERE e.teacher_id = :tid
            ORDER BY active_count DESC, e.created_at DESC
        ")
        ->bind(':tid', $teacherId)
        ->resultSet();

        // 2. Select default exam if not specified
        $selectedExam = null;
        if ($examId > 0) {
            foreach ($exams as $ex) {
                if ((int)$ex['id'] === $examId) {
                    $selectedExam = $ex;
                    break;
                }
            }
        }

        if (!$selectedExam && !empty($exams)) {
            // Pick first active exam, or first exam
            $selectedExam = $exams[0];
            $examId = (int)$selectedExam['id'];
        }

        // 3. Load initial live monitoring data
        /** @var ProctoringModel $proctoringModel */
        $proctoringModel = $this->model('ProctoringModel');
        $participants = [];
        $summary = [
            'total' => 0,
            'online' => 0,
            'completed' => 0,
            'not_started' => 0,
            'normal' => 0,
            'warning' => 0,
            'high_risk' => 0
        ];

        if ($selectedExam) {
            $participants = $proctoringModel->getExamLiveParticipants($examId);
            $summary = $proctoringModel->getExamMonitoringSummary($examId, $participants);
        }

        $data = [
            'page_title' => 'Live Monitoring Ujian - ' . ($selectedExam['title'] ?? 'Panel Guru'),
            'current_role' => 'teacher',
            'user' => $this->currentUser(),
            'teacher' => $teacher,
            'exams' => $exams,
            'selected_exam' => $selectedExam,
            'participants' => $participants,
            'summary' => $summary
        ];

        $this->view('teacher/monitoring/index', $data);
    }

    /**
     * AJAX endpoint to poll live monitoring data
     *
     * @param int|string $examId
     */
    public function monitoringData($examId = 0)
    {
        $examId = (int)$examId;
        $teacher = $this->getTeacherProfile();
        $teacherId = (int)$teacher['id'];
        $db = Database::getInstance();

        // Verify teacher owns the exam
        $exam = $db->query("
            SELECT id, title, duration_minutes, enable_proctoring, status 
            FROM exams 
            WHERE id = :eid AND teacher_id = :tid 
            LIMIT 1
        ")
        ->bind(':eid', $examId)
        ->bind(':tid', $teacherId)
        ->single();

        if (!$exam) {
            $this->json(['success' => false, 'message' => 'Ujian tidak ditemukan atau hak akses ditolak.'], 404);
        }

        /** @var ProctoringModel $proctoringModel */
        $proctoringModel = $this->model('ProctoringModel');
        $participants = $proctoringModel->getExamLiveParticipants($examId);
        $summary = $proctoringModel->getExamMonitoringSummary($examId, $participants);

        $this->json([
            'success' => true,
            'exam' => $exam,
            'summary' => $summary,
            'participants' => $participants,
            'server_time' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * AJAX endpoint to get participant timeline details
     *
     * @param int|string $participantId
     */
    public function participantTimelineData($participantId = 0)
    {
        $participantId = (int)$participantId;
        $teacher = $this->getTeacherProfile();
        $teacherId = (int)$teacher['id'];
        $db = Database::getInstance();

        // Verify teacher owns the exam for this participant
        $check = $db->query("
            SELECT ep.id, ep.exam_id, u.full_name AS student_name, s.nis, c.name AS class_name
            FROM exam_participants ep
            JOIN exams e ON ep.exam_id = e.id
            JOIN students s ON ep.student_id = s.id
            JOIN users u ON s.user_id = u.id
            LEFT JOIN classes c ON s.class_id = c.id
            WHERE ep.id = :pid AND e.teacher_id = :tid
            LIMIT 1
        ")
        ->bind(':pid', $participantId)
        ->bind(':tid', $teacherId)
        ->single();

        if (!$check) {
            $this->json(['success' => false, 'message' => 'Data peserta tidak ditemukan.'], 404);
        }

        /** @var ProctoringModel $proctoringModel */
        $proctoringModel = $this->model('ProctoringModel');
        $timeline = $proctoringModel->getParticipantTimeline($participantId);
        $riskSummary = $proctoringModel->getRiskSummary($participantId);

        $this->json([
            'success' => true,
            'participant' => $check,
            'timeline' => $timeline,
            'risk_summary' => $riskSummary
        ]);
    }
}



