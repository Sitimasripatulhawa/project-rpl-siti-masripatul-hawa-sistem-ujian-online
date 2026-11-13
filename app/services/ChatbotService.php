<?php
/**
 * Chatbot Service - AI Online Exam & Proctoring System
 * Powered by Google Gemini AI with resilient Scoped Data Engine & Strict Guardrails.
 */

require_once __DIR__ . '/../core/Database.php';

class ChatbotService
{
    /**
     * List of prioritized Gemini / Google AI models
     */
    private static array $candidateModels = [
        'gemini-3.8-flash',
        'gemma-4-26b-a4b-it',
        'gemini-3.1-flash-lite'
    ];

    /**
     * Generate an intelligent reply for Global Floating Chatbot with Scoped Data Access
     *
     * @param string $message
     * @param array|null $user
     * @return array
     */
    public static function reply(string $message, ?array $user = null): array
    {
        $rawMessage = trim($message);
        if ($rawMessage === '') {
            $scopedData = self::getUserScopedContext($user);
            return [
                'status' => 'success',
                'source' => 'local',
                'reply' => self::getPersonalizedWelcome($user, $scopedData),
                'suggestions' => self::getSuggestionsForRole($user['role'] ?? 'guest'),
                'action' => self::getDefaultActionForRole($user['role'] ?? 'guest')
            ];
        }

        // 1. Strict Guardrails Check (Catches out-of-scope, coding requests, injections, etc.)
        $guardrailResult = self::detectGuardrailViolation($rawMessage, 'global', [
            'user' => $user
        ]);

        if ($guardrailResult !== null) {
            return [
                'status' => 'success',
                'source' => 'guardrail',
                'reply' => $guardrailResult['reply'],
                'suggestions' => $guardrailResult['suggestions'] ?? self::getSuggestionsForRole($user['role'] ?? 'guest'),
                'action' => $guardrailResult['action'] ?? null
            ];
        }

        // 2. Fetch Scoped Data Context for this specific user
        $scopedContext = self::getUserScopedContext($user);

        // Pre-query database for matching exam questions if user is asking for question discussion
        $matchedQuestions = [];
        $isDiscussionQuery = (
            preg_match('/(pembahasan|bahas\b|kunci jawaban|penjelasan soal|jelaskan soal|ulas soal|bedah soal|soal nomor|soal no|soal id|soal #|soal limit|soal kuadrat|soal integral|soal dadu|soal turunan|soal 25|soal bcrypt|25\s*[x\*]\s*4|bcrypt|limit|integral|dadu|turunan|fungsi kuadrat|rekap.*soal|kunci.*pembahasan)/i', $rawMessage)
            || (($user['role'] ?? '') === 'student' && preg_match('/(pembahasan ujian|bahas ujian|evaluasi ujian|soal.*salah|soal yang salah|ujian matematika)/i', $rawMessage))
            || preg_match('/(?:soal\s+(?:nomor|no|id|#)?\s*[0-9]+|(?:nomor|no|#)\s*[0-9]+)/i', $rawMessage)
        ) && !preg_match('/(panduan guru|buat bank soal|panduan membuat|cara membuat|tambah soal)/i', $rawMessage);
        if ($isDiscussionQuery) {
            $matchedQuestions = self::findQuestionsInDatabase($rawMessage, $user);
        }

        // 3. Prioritize Google Gemini AI with Scoped System Prompt & Database Ground Truth
        $hasRealGeminiKey = defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY) && str_starts_with(GEMINI_API_KEY, 'AIzaSy') && (!defined('FORCE_LOCAL_CHATBOT') || !FORCE_LOCAL_CHATBOT);
        if ($hasRealGeminiKey) {
            $geminiReply = self::callGeminiWithContext($rawMessage, $user, $scopedContext, $matchedQuestions);
            if ($geminiReply !== null && trim($geminiReply) !== '') {
                // Run post-generation safety scan on AI output
                $sanitizedReply = self::sanitizeOutput($geminiReply);

                return [
                    'status' => 'success',
                    'source' => 'gemini',
                    'reply' => $sanitizedReply,
                    'suggestions' => self::getContextualSuggestions($rawMessage, $user['role'] ?? 'guest'),
                    'action' => self::detectAction($rawMessage, $user['role'] ?? 'guest')
                ];
            }
        }

        // 4. Local Resilient Engine with Scoped Awareness & Database Fallback
        return self::replyWithScopedLocalEngine($rawMessage, $user, $scopedContext, $matchedQuestions);
    }

    /**
     * Dedicated Question Explainer Chatbot (Chatbot Penjelasan Tiap Soal Setelah Ujian Selesai)
     * Scoped strictly to:
     * - Question text & options
     * - Student answer
     * - Correct answer & official explanation
     *
     * @param array $questionData Question, options, student answer, correct answer
     * @param string $studentMessage Follow-up question from student
     * @param array $history Previous conversation messages for this question
     * @return array
     */
    public static function explainQuestion(array $questionData, string $studentMessage, array $history = []): array
    {
        $rawMessage = trim($studentMessage);

        // 1. Strict Guardrails for Question Mode
        $guardrailResult = self::detectGuardrailViolation($rawMessage, 'question', [
            'question_id' => $questionData['question_id'] ?? null
        ]);

        if ($guardrailResult !== null) {
            return [
                'status' => 'success',
                'source' => 'guardrail',
                'reply' => $guardrailResult['reply'],
                'suggestions' => [
                    'Jelaskan konsep utama soal ini',
                    'Mengapa jawaban saya salah?',
                    'Bagaimana langkah menemukan jawaban benar?'
                ]
            ];
        }

        // 2. Call Gemini API in Question Explanation Mode
        $hasRealGeminiKey = defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY) && str_starts_with(GEMINI_API_KEY, 'AIzaSy') && (!defined('FORCE_LOCAL_CHATBOT') || !FORCE_LOCAL_CHATBOT);
        if ($hasRealGeminiKey) {
            $geminiReply = self::callGeminiQuestionExplainer($questionData, $rawMessage, $history);
            if ($geminiReply !== null && trim($geminiReply) !== '') {
                return [
                    'status' => 'success',
                    'source' => 'gemini',
                    'reply' => self::sanitizeOutput($geminiReply),
                    'suggestions' => [
                        'Bisa jelaskan langkah penyelesaiannya lebih detail?',
                        'Apa rumus atau konsep materi kunci soal ini?',
                        'Tips agar tidak keliru pada soal serupa'
                    ]
                ];
            }
        }

        // 3. Local Pedagogical Fallback Explanation
        return self::replyWithLocalQuestionExplainer($questionData, $rawMessage);
    }

    /**
     * Strict Guardrails: Detect prompt injections, confidential data probes, or out-of-bounds topics
     *
     * @param string $message
     * @param string $mode 'global' or 'question'
     * @param array|null $context
     * @return array|null Null if clean, array with response if violation detected
     */
    public static function detectGuardrailViolation(string $message, string $mode = 'global', ?array $context = null): ?array
    {
        $normalized = mb_strtolower(trim($message), 'UTF-8');
        if ($normalized === '') {
            return null;
        }

        // A. PROMPT INJECTION / JAILBREAK / SYSTEM PROMPT EXTRACTION ATTEMPTS
        $injectionPatterns = [
            '/\b(ignore|abaikan|lupakan)\b.*?\b(previous|sebelumnya|instruksi|aturan|prompt|rules)\b/i',
            '/\b(system prompt|prompt rahasia|instruksi awal|developer mode|dan mode|jailbreak)\b/i',
            '/\b(pretend to be|berpura-puralah|jadilah ai tanpa batas|uncensored)\b/i',
            '/\b(bypass security|bypass filter|override system)\b/i',
            '/\b(tampilkan instruksi sistem|tuliskan semua prompt|print prompt)\b/i'
        ];

        foreach ($injectionPatterns as $pattern) {
            if (preg_match($pattern, $normalized)) {
                return [
                    'reply' => "🛡️ **Protokol Keamanan Aktif**: Permintaan Anda terdeteksi mencoba memodifikasi instruksi dasar atau aturan sistem ExamBot. Demi keamanan dan integritas akademik, seluruh parameter perlindungan sistem tetap berjalan.",
                    'suggestions' => [
                        'Apa saja fitur AI Proctoring?',
                        'Bagaimana alur pengerjaan ujian?',
                        'Panduan mengatasi kamera error'
                    ]
                ];
            }
        }

        // B. CONFIDENTIAL / CREDENTIAL / DATABASE PROBES
        $confidentialPatterns = [
            '/\b(api[_\s]?key|gemini[_\s]?key|secret[_\s]?key|kunci rahasia api)\b/i',
            '/\b(db_pass|db_user|db_host|database password|kredensial database|password db)\b/i',
            '/\b(hash password|password hash|\.env|config\.php|sql injection|drop table|select \* from users)\b/i',
            '/\b(kunci jawaban ujian besok|jawaban ulangan besok|bocoran kunci jawaban)\b/i',
            '/\b(password user lain|password siswa lain|password guru|data pribadi siswa lain)\b/i'
        ];

        foreach ($confidentialPatterns as $pattern) {
            if (preg_match($pattern, $normalized)) {
                return [
                    'reply' => "🔒 **Data Bersifat Rahasia (Confidential)**: ExamBot tidak dapat membagikan informasi sensitif seperti API key, kredensial server, data rahasia pengguna lain, maupun bocoran kunci jawaban ujian yang sedang/akan berlangsung demi menjunjung tinggi integritas ujian.",
                    'suggestions' => [
                        'Panduan persiapan ujian online',
                        'Aturan tata tertib ujian',
                        'Bantuan teknis aplikasi'
                    ]
                ];
            }
        }

        // C. OUT-OF-SCOPE CODING / PROGRAMMING / SCRIPT GENERATION REQUESTS
        // Pertanyaan seperti "buatkan coding python untuk menuliskan nama anda", penulisan script, atau tugas pemrograman di luar sistem
        $codingGenerationPatterns = [
            '/\b(buatkan|tuliskan|bikin|generate|write|tulis|buat)\b.*?\b(coding|koding|kodingan|script|skrip|program|source code|kode)\b/iu',
            '/\b(coding|koding|kodingan|script|skrip|program|source code|kode)\b.*?\b(python|javascript|php|java|c\+\+|c#|html|css|sql|bash|powershell|golang|ruby|rust|pascal|kotlin|swift)\b/iu',
            '/\b(coding|koding|kodingan|script|skrip|program|source code|kode)\b.*?\b(untuk|buat|tuliskan|menuliskan|mencetak|print|menampilkan|menghitung|hitung|nama)\b/iu',
            '/\b(coding python|koding python|script python|program python|kode python)\b/iu',
            '/\b(buatkan|tuliskan|bikin)\b.*?\b(fungsi|function|algoritma|class|query sql)\b/iu',
            '/\b(def\s+[a-z_][a-z0-9_]*\s*\(|print\(["\'].*?["\']\)|console\.log\(|public\s+static\s+void\s+main)/i'
        ];

        foreach ($codingGenerationPatterns as $pattern) {
            if (preg_match($pattern, $normalized)) {
                if ($mode === 'question') {
                    return [
                        'reply' => "📚 **Fokus Pembahasan Soal**: Sesi tanya-jawab ini dikhususkan secara ketat untuk membahas **soal ini saja**. Permintaan di luar materi soal (seperti penulisan kode/skrip pemrograman) berada di luar ruang lingkup.",
                        'suggestions' => [
                            'Jelaskan konsep inti soal ini',
                            'Mengapa opsi jawaban saya salah?',
                            'Bagaimana metode penyelesaian yang benar?'
                        ]
                    ];
                }

                return [
                    'reply' => "⚠️ **Di Luar Ruang Lingkup Sistem Ujian**\n\nMaaf, ExamBot tidak melayani pembuatan kode atau program (seperti coding Python, script pemrograman, atau tugas koding lainnya). Saya berfokus pada informasi ujian online, data akademik sekolah dari database, dan panduan AI Proctoring.",
                    'suggestions' => [
                        'Bahas soal nomor 1',
                        'Bagaimana cara kerja AI Proctoring?',
                        'Solusi jika kamera error saat ujian',
                        'Ada berapa mata pelajaran'
                    ],
                    'action' => null
                ];
            }
        }

        // D. OUT-OF-SCOPE GENERAL CREATIVE / ENTERTAINMENT / NON-EXAM TASKS
        $creativeAndGeneralPatterns = [
            '/\b(buatkan|tuliskan|bikin|ciptakan|karang)\b.*?\b(puisi|cerpen|pantun|lirik lagu|lagu|dongeng|novel|surat cinta|cerita pendek|naskah drama|puisi cinta)\b/iu',
            '/\b(resep masak|resep makanan|cara memasak|masakan enak|kuliner|menu makanan|resep kue|kue bolu|bolu coklat)\b/iu',
            '/\b(cuaca hari ini|ramalan bintang|zodiak|horoskop|jodoh saya|pacar saya)\b/iu',
            '/\b(presiden indonesia|politik|pemilu|pilpres|partai politik|kampanye)\b/iu',
            '/\b(crypto|bitcoin|saham|investasi uang|trading forex)\b/iu',
            '/\b(game online|mobile legends|free fire|valorant|pubg|roblox|genshin)\b/iu',
            '/\b(ceritakan lelucon|cerita lucu|tebak-tebakan lucu|joke)\b/iu'
        ];

        foreach ($creativeAndGeneralPatterns as $pattern) {
            if (preg_match($pattern, $normalized)) {
                if ($mode === 'question') {
                    return [
                        'reply' => "📚 **Fokus Pembahasan Soal**: Sesi ini dikhususkan secara ketat untuk membahas **soal ini saja** beserta konsep keilmuannya. Pertanyaan di luar materi ini berada di Luar Ruang Lingkup pembahasan butir soal.",
                        'suggestions' => [
                            'Jelaskan konsep inti soal ini',
                            'Mengapa opsi jawaban saya salah?',
                            'Bagaimana metode penyelesaian yang benar?'
                        ]
                    ];
                }

                return [
                    'reply' => "⚠️ **Di Luar Ruang Lingkup Sistem Ujian**\n\nMaaf, permintaan ini di luar cakupan sistem ujian online. ExamBot berfokus melayani bantuan teknis ujian, aturan AI Proctoring, dan pembahasan butir soal pada database sistem.",
                    'suggestions' => self::getSuggestionsForRole($context['user']['role'] ?? 'guest'),
                    'action' => null
                ];
            }
        }

        // E. HAZARDOUS TOPICS (meretas, judi, narkoba)
        $hazardousOrCompletelyOffTopic = [
            '/\b(meretas|membobol|bobol|hack|peretasan|ddos|malware|virus)\b/i',
            '/\b(judi|slot online|gacor|taruhan)\b/i',
            '/\b(obat terlarang|narkoba|senjata tajam)\b/i'
        ];

        foreach ($hazardousOrCompletelyOffTopic as $pattern) {
            if (preg_match($pattern, $normalized)) {
                return [
                    'reply' => "⚠️ **Di Luar Ruang Lingkup & Kebijakan Keamanan**: ExamBot adalah asisten resmi untuk sistem ujian online dan pengawasan AI. Pertanyaan ini berada di luar cakupan layanan dan melanggar kebijakan keamanan sistem.",
                    'suggestions' => self::getSuggestionsForRole($context['user']['role'] ?? 'guest'),
                    'action' => null
                ];
            }
        }

        // F. SPECIFIC QUESTION EXPLAINER GUARDRAILS (mode == 'question')
        if ($mode === 'question') {
            $offTopicQuestionPatterns = [
                '/\b(soal nomor [0-9]+|soal nomor lain|bahas soal nomor [0-9]+|bocorkan soal lain|soal lain|bocorkan jawaban)\b/i',
                '/\b(resep|kue|bolu|masak|makanan|lagu|puisi|film|cuaca|presiden)\b/i'
            ];

            foreach ($offTopicQuestionPatterns as $pattern) {
                if (preg_match($pattern, $normalized)) {
                    return [
                        'reply' => "📚 **Fokus Pembahasan Soal**: Sesi tanya-jawab ini dikhususkan secara ketat untuk membahas **soal ini saja** beserta konsep keilmuannya. Pertanyaan di luar butir soal ini tidak dapat dijawab di sini agar Anda dapat fokus mengevaluasi pemahaman materi.",
                        'suggestions' => [
                            'Jelaskan konsep inti soal ini',
                            'Mengapa opsi jawaban saya salah?',
                            'Bagaimana metode penyelesaian yang benar?'
                        ]
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Retrieve Scoped Data Access Context from Database
     * Enforces strict user isolation:
     * - Guest: Public platform overview only
     * - Student: Only their own profile, assigned exams, and released results
     * - Teacher: Only their own classes, created exams, and student submissions
     * - Admin: Platform metrics
     *
     * @param array|null $user
     * @return array
     */
    public static function getUserScopedContext(?array $user): array
    {
        $context = [
            'role' => $user['role'] ?? 'guest',
            'user_id' => $user['id'] ?? null,
            'full_name' => $user['full_name'] ?? 'Pengunjung',
            'scoped_summary' => '',
            'details' => []
        ];

        if (!$user || !isset($user['id'])) {
            $context['scoped_summary'] = "Pengguna belum login (Guest). Hanya diizinkan melihat informasi umum sistem, tata cara ujian, akun demo, dan troubleshooting perangkat.";
            return $context;
        }

        $db = Database::getInstance();
        if (!$db->isConnected()) {
            $context['scoped_summary'] = "Mode basis data offline.";
            return $context;
        }

        $userId = (int)$user['id'];
        $role = $user['role'];

        if ($role === 'student') {
            // Fetch student profile
            $student = $db->query("
                SELECT s.id AS student_id, s.nis, s.nisn, s.gender, s.class_id, c.name AS class_name 
                FROM students s 
                LEFT JOIN classes c ON s.class_id = c.id 
                WHERE s.user_id = :uid 
                LIMIT 1
            ")->bind(':uid', $userId)->single();

            if ($student) {
                $studentId = (int)$student['student_id'];
                $context['details']['student'] = $student;

                // Fetch assigned exams for this student
                $exams = $db->query("
                    SELECT e.id AS exam_id, e.title, e.duration_minutes, e.start_time, e.end_time, e.show_result,
                           ep.status AS participant_status, sub.name AS subject_name,
                           er.final_score, er.is_passed
                    FROM exam_participants ep
                    JOIN exams e ON ep.exam_id = e.id
                    JOIN subjects sub ON e.subject_id = sub.id
                    LEFT JOIN exam_results er ON ep.id = er.exam_participant_id
                    WHERE ep.student_id = :sid AND e.status IN ('published', 'running', 'finished')
                    ORDER BY e.start_time DESC
                    LIMIT 10
                ")->bind(':sid', $studentId)->resultSet();

                $context['details']['exams'] = $exams;

                // Build text representation for prompt
                $examLines = [];
                foreach ($exams as $ex) {
                    $statusDesc = match($ex['participant_status']) {
                        'submitted' => 'Selesai dikerjakan',
                        'in_progress' => 'Sedang berlangsung',
                        default => 'Belum dimulai'
                    };
                    $scoreDesc = ($ex['participant_status'] === 'submitted' && (int)$ex['show_result'] === 1 && $ex['final_score'] !== null)
                        ? "Nilai: {$ex['final_score']} (" . ($ex['is_passed'] ? 'Lulus' : 'Belum Lulus') . ")"
                        : "Hasil: Belum dipublikasikan / dalam penilaian";

                    $examLines[] = "- {$ex['title']} ({$ex['subject_name']}): Status {$statusDesc}, {$scoreDesc}";
                }

                // Fetch submitted exam question reviews with DB explanation
                $submittedExams = $db->query("
                    SELECT ep.id AS participant_id, ep.exam_id, e.title, sub.name AS subject_name, er.final_score, er.is_passed
                    FROM exam_participants ep
                    JOIN exams e ON ep.exam_id = e.id
                    JOIN subjects sub ON e.subject_id = sub.id
                    LEFT JOIN exam_results er ON ep.id = er.exam_participant_id
                    WHERE ep.student_id = :sid AND ep.status = 'submitted' AND e.show_result = 1
                    ORDER BY ep.submit_time DESC
                    LIMIT 3
                ")->bind(':sid', $studentId)->resultSet();

                $reviewLines = [];
                $examReviews = [];
                foreach ($submittedExams as $se) {
                    $qList = $db->query("
                        SELECT eq.question_order, q.id AS question_id, q.question_text, q.explanation,
                               ea.selected_option_id, ea.essay_answer, ea.is_correct
                        FROM exam_questions eq
                        JOIN questions q ON eq.question_id = q.id
                        LEFT JOIN exam_answers ea ON eq.question_id = ea.question_id AND ea.exam_participant_id = :pid
                        WHERE eq.exam_id = :eid
                        ORDER BY eq.question_order ASC
                    ")->bind(':pid', $se['participant_id'])->bind(':eid', $se['exam_id'])->resultSet();

                    $examReviews[$se['exam_id']] = [
                        'exam' => $se,
                        'questions' => $qList
                    ];

                    foreach ($qList as $ql) {
                        $eval = ($ql['is_correct'] == 1) ? 'Benar' : 'Salah';
                        $reviewLines[] = "  * Soal #{$ql['question_order']} [{$eval}]: {$ql['question_text']} | Pembahasan Database: {$ql['explanation']}";
                    }
                }
                $context['details']['exam_reviews'] = $examReviews;

                $context['scoped_summary'] = "Siswa: {$user['full_name']} (NIS: {$student['nis']}, Kelas: {$student['class_name']}). " .
                    "Daftar ujian terkait:\n" . (empty($examLines) ? "Belum ada ujian terdaftar." : implode("\n", $examLines)) .
                    (!empty($reviewLines) ? "\n\nPembahasan Soal Ujian yang Telah Selesai (Database):\n" . implode("\n", array_slice($reviewLines, 0, 10)) : "");
            }
        } elseif ($role === 'teacher') {
            // Fetch teacher profile & statistics
            $teacher = $db->query("
                SELECT t.id AS teacher_id, t.nip, t.specialization 
                FROM teachers t 
                WHERE t.user_id = :uid 
                LIMIT 1
            ")->bind(':uid', $userId)->single();

            if ($teacher) {
                $teacherId = (int)$teacher['teacher_id'];
                $context['details']['teacher'] = $teacher;

                $examStats = $db->query("
                    SELECT e.id, e.title, e.status, sub.name AS subject_name,
                           (SELECT COUNT(*) FROM exam_participants WHERE exam_id = e.id) AS total_assigned,
                           (SELECT COUNT(*) FROM exam_participants WHERE exam_id = e.id AND status = 'submitted') AS total_submitted
                    FROM exams e
                    JOIN subjects sub ON e.subject_id = sub.id
                    WHERE e.teacher_id = :tid
                    ORDER BY e.created_at DESC
                    LIMIT 8
                ")->bind(':tid', $teacherId)->resultSet();

                $context['details']['exam_stats'] = $examStats;

                $lines = [];
                foreach ($examStats as $es) {
                    $lines[] = "- {$es['title']} ({$es['subject_name']}): Status {$es['status']}, Peserta {$es['total_submitted']}/{$es['total_assigned']} submit";
                }

                // Fetch bank soal questions with DB explanation
                $bankQuestions = $db->query("
                    SELECT q.id, q.question_text, q.question_type, q.explanation, sub.name AS subject_name
                    FROM questions q
                    JOIN subjects sub ON q.subject_id = sub.id
                    WHERE q.teacher_id = :tid
                    ORDER BY q.id ASC
                    LIMIT 15
                ")->bind(':tid', $teacherId)->resultSet();
                $context['details']['bank_questions'] = $bankQuestions;

                $bankLines = [];
                foreach ($bankQuestions as $bq) {
                    $bankLines[] = "  * [ID {$bq['id']}] {$bq['question_text']} | Pembahasan DB: {$bq['explanation']}";
                }

                $context['scoped_summary'] = "Guru: {$user['full_name']} (Spesialisasi: {$teacher['specialization']}). " .
                    "Ujian yang diampu:\n" . (empty($lines) ? "Belum ada ujian yang dibuat." : implode("\n", $lines)) .
                    (!empty($bankLines) ? "\n\nBank Soal & Pembahasan Database:\n" . implode("\n", array_slice($bankLines, 0, 10)) : "");
            }
        } elseif ($role === 'admin') {
            $userCount = $db->query("SELECT COUNT(*) AS total FROM users")->single()['total'] ?? 0;
            $examCount = $db->query("SELECT COUNT(*) AS total FROM exams")->single()['total'] ?? 0;
            $classCount = $db->query("SELECT COUNT(*) AS total FROM classes")->single()['total'] ?? 0;

            $context['scoped_summary'] = "Super Admin: Akses pengawasan master data. Total pengguna: {$userCount}, Total sesi ujian: {$examCount}, Total rombel kelas: {$classCount}.";
        }

        return $context;
    }

    /**
     * Build comprehensive Omni-Database knowledge summary across all 13 database tables
     *
     * @param array|null $user
     * @return string
     */
    public static function getOmniDatabaseSummary(?array $user = null): string
    {
        $db = Database::getInstance();
        if (!$db->isConnected()) {
            return "Status basis data: Mode offline.";
        }

        try {
            // 1. Users, Students, Classes, Subjects
            $userCounts = $db->query("
                SELECT 
                    COUNT(*) AS total_users,
                    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) AS total_admin,
                    SUM(CASE WHEN role = 'teacher' THEN 1 ELSE 0 END) AS total_teacher,
                    SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) AS total_student
                FROM users
            ")->single() ?: [];

            $studentStats = $db->query("
                SELECT COUNT(*) AS total,
                       SUM(CASE WHEN gender = 'L' THEN 1 ELSE 0 END) AS male,
                       SUM(CASE WHEN gender = 'P' THEN 1 ELSE 0 END) AS female
                FROM students
            ")->single() ?: [];

            $classRows = $db->query("
                SELECT c.name AS class_name, COUNT(s.id) AS student_count
                FROM classes c
                LEFT JOIN students s ON c.id = s.class_id
                GROUP BY c.id, c.name
                ORDER BY c.name ASC
            ")->resultSet() ?: [];
            $classCount = count($classRows);
            $classesDetails = implode(', ', array_map(fn($c) => "{$c['class_name']} ({$c['student_count']} siswa)", $classRows));

            $teacherRows = $db->query("
                SELECT u.full_name, t.nip, t.specialization
                FROM teachers t
                JOIN users u ON t.user_id = u.id
                ORDER BY u.full_name ASC
            ")->resultSet() ?: [];
            $teacherDetails = implode(', ', array_map(fn($t) => "{$t['full_name']} (Bidang: {$t['specialization']})", $teacherRows));

            $studentRows = $db->query("
                SELECT u.full_name, s.nis, c.name AS class_name
                FROM students s
                JOIN users u ON s.user_id = u.id
                LEFT JOIN classes c ON s.class_id = c.id
                ORDER BY u.full_name ASC
            ")->resultSet() ?: [];
            $studentNisDetails = implode(', ', array_map(fn($s) => "{$s['full_name']} (NIS: {$s['nis']}, {$s['class_name']})", $studentRows));

            $subjectQRows = $db->query("
                SELECT s.name AS subject_name, s.code, COUNT(q.id) AS q_count
                FROM subjects s
                LEFT JOIN questions q ON s.id = q.subject_id
                GROUP BY s.id, s.name, s.code
                ORDER BY s.name ASC
            ")->resultSet() ?: [];
            $subjectsStr = implode(', ', array_map(fn($s) => "{$s['subject_name']} ({$s['code']}: {$s['q_count']} soal)", $subjectQRows));

            $submittedStudents = $db->query("
                SELECT DISTINCT u.full_name, s.nis, c.name AS class_name
                FROM exam_participants ep
                JOIN students s ON ep.student_id = s.id
                JOIN users u ON s.user_id = u.id
                LEFT JOIN classes c ON s.class_id = c.id
                WHERE ep.status = 'submitted'
                ORDER BY u.full_name ASC
            ")->resultSet() ?: [];
            $submittedNames = implode(', ', array_map(fn($s) => "{$s['full_name']} ({$s['class_name']})", $submittedStudents));

            $activeExamStudents = $db->query("
                SELECT DISTINCT u.full_name, s.nis, c.name AS class_name
                FROM exam_participants ep
                JOIN students s ON ep.student_id = s.id
                JOIN users u ON s.user_id = u.id
                LEFT JOIN classes c ON s.class_id = c.id
                WHERE ep.status IN ('in_progress', 'submitted')
                ORDER BY u.full_name ASC
            ")->resultSet() ?: [];
            $activeNames = implode(', ', array_map(fn($s) => "{$s['full_name']} ({$s['class_name']})", $activeExamStudents));

            // 2. Questions & Options
            $qStats = $db->query("
                SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN question_type = 'multiple_choice' THEN 1 ELSE 0 END) AS pg_count,
                    SUM(CASE WHEN question_type = 'essay' THEN 1 ELSE 0 END) AS essay_count,
                    SUM(CASE WHEN difficulty = 'easy' THEN 1 ELSE 0 END) AS easy_count,
                    SUM(CASE WHEN difficulty = 'medium' THEN 1 ELSE 0 END) AS med_count,
                    SUM(CASE WHEN difficulty = 'hard' THEN 1 ELSE 0 END) AS hard_count
                FROM questions
            ")->single() ?: [];

            // 3. Exams & Participants
            $examStats = $db->query("
                SELECT 
                    COUNT(*) AS total_exams,
                    SUM(CASE WHEN status = 'running' THEN 1 ELSE 0 END) AS running_exams,
                    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS published_exams,
                    SUM(CASE WHEN status = 'finished' THEN 1 ELSE 0 END) AS finished_exams
                FROM exams
            ")->single() ?: [];

            $partStats = $db->query("
                SELECT 
                    COUNT(*) AS total_parts,
                    SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) AS submitted,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress,
                    SUM(CASE WHEN status = 'not_started' THEN 1 ELSE 0 END) AS not_started
                FROM exam_participants
            ")->single() ?: [];

            // 4. Exam Results
            $resStats = $db->query("
                SELECT 
                    COUNT(*) AS total_results,
                    SUM(CASE WHEN is_passed = 1 THEN 1 ELSE 0 END) AS passed,
                    MAX(final_score) AS high_score,
                    AVG(final_score) AS avg_score
                FROM exam_results
            ")->single() ?: [];

            // 5. Proctoring Events
            $procEvents = $db->query("
                SELECT 
                    COUNT(*) AS total_events,
                    SUM(CASE WHEN severity = 'CRITICAL' THEN 1 ELSE 0 END) AS critical_events,
                    SUM(CASE WHEN severity = 'WARNING' THEN 1 ELSE 0 END) AS warning_events,
                    SUM(CASE WHEN event_type = 'FACE_NOT_DETECTED' THEN 1 ELSE 0 END) AS face_missing,
                    SUM(CASE WHEN event_type = 'MULTIPLE_FACE' THEN 1 ELSE 0 END) AS multi_face,
                    SUM(CASE WHEN event_type = 'CAMERA_BLOCKED' THEN 1 ELSE 0 END) AS cam_block,
                    SUM(CASE WHEN event_type = 'CAMERA_DISCONNECTED' THEN 1 ELSE 0 END) AS cam_dc
                FROM proctoring_events
            ")->single() ?: [];

            $lines = [
                "RINGKASAN DATA KESELURUHAN 13 TABEL DATABASE SISTEM (SUMBER KEBENARAN RESMI):",
                "- Pengguna (users): Total {$userCounts['total_users']} pengguna ({$userCounts['total_student']} Siswa [{$studentStats['male']} L / {$studentStats['female']} P], {$userCounts['total_teacher']} Guru, {$userCounts['total_admin']} Admin).",
                "- Rombel Kelas (classes): Total {$classCount} kelas, yaitu: {$classesDetails}.",
                "- Guru Pengajar (teachers): Total " . count($teacherRows) . " guru terdaftar: {$teacherDetails}. Catatan: Guru Matematika adalah Budi Santoso, M.Pd.",
                "- Siswa & NIS (students): Total " . count($studentRows) . " siswa terdaftar beserta NIS: {$studentNisDetails}.",
                "- Mata Pelajaran & Soal per Mapel (subjects, questions): Total " . count($subjectQRows) . " mapel dengan butir soal: {$subjectsStr}. Total bank soal: {$qStats['total']} butir ({$qStats['pg_count']} PG, {$qStats['essay_count']} Essay).",
                "- Sesi Ujian (exams): Total {$examStats['total_exams']} sesi ujian tercatat di database ({$examStats['running_exams']} Berlangsung, {$examStats['published_exams']} Terjadwal, {$examStats['finished_exams']} Selesai).",
                "- Status Siswa Mengikuti Ujian: Ada " . count($activeExamStudents) . " siswa yang telah/sedang mengikuti ujian: " . (empty($activeNames) ? "Belum ada" : $activeNames) . ".",
                "- Status Siswa Submit Ujian: Ada " . count($submittedStudents) . " siswa yang telah submit (mengumpulkan) ujian: " . (empty($submittedNames) ? "Belum ada" : $submittedNames) . " (Total submit: {$partStats['submitted']} kali).",
                "- Rekap Hasil (exam_results): {$resStats['total_results']} nilai terbit ({$resStats['passed']} Lulus, Skor tertinggi: {$resStats['high_score']}, Rata-rata: " . round((float)($resStats['avg_score'] ?? 0), 1) . ").",
                "- AI Proctoring (proctoring_events): {$procEvents['total_events']} kejadian tercatat ({$procEvents['critical_events']} Kritis, {$procEvents['warning_events']} Peringatan | {$procEvents['face_missing']} Wajah Tidak Terdeteksi, {$procEvents['multi_face']} Wajah Ganda, {$procEvents['cam_block']} Kamera Terhalang, {$procEvents['cam_dc']} Kamera Terputus)."
            ];

            return implode("\n", $lines);
        } catch (\Throwable $e) {
            return "Status database: Terhubung.";
        }
    }

    /**
     * Call Google Gemini API with candidate model fallback
     *
     * @param string $message
     * @param array|null $user
     * @param array $scopedContext
     * @return string|null
     */
    public static function callGeminiWithContext(string $message, ?array $user, array $scopedContext, array $matchedQuestions = []): ?string
    {
        $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
        if (empty($apiKey)) {
            return null;
        }

        $userName = $user['full_name'] ?? 'Pengunjung';
        $role = $user['role'] ?? 'guest';
        $scopedSummary = $scopedContext['scoped_summary'] ?? '';
        $omniSummary = self::getOmniDatabaseSummary($user);

        $dbQuestionsContext = '';
        if (!empty($matchedQuestions)) {
            $dbQuestionsContext = "\n\nDATA RESMI BUTIR SOAL & PEMBAHASAN DARI DATABASE SISTEM (SUMBER KEBENARAN MUTLAK RESMI):\n";
            foreach ($matchedQuestions as $idx => $mq) {
                $qNum = $mq['question_order'] ?? ($idx + 1);
                $dbQuestionsContext .= "--- Soal #{$qNum} (ID {$mq['id']}) ---\n";
                $dbQuestionsContext .= "Pertanyaan: {$mq['question_text']}\n";
                if (!empty($mq['options'])) {
                    $dbQuestionsContext .= "Pilihan Jawaban:\n";
                    foreach ($mq['options'] as $opt) {
                        $isCorrectMark = (!empty($opt['is_correct'])) ? ' (BENAR/KUNCI)' : '';
                        $dbQuestionsContext .= "- {$opt['option_label']}. {$opt['option_text']}{$isCorrectMark}\n";
                    }
                }
                $dbQuestionsContext .= "Kunci Jawaban Resmi: {$mq['correct_answer_label']}\n";
                $dbQuestionsContext .= "Pembahasan Resmi Database: {$mq['explanation']}\n\n";
            }
        }

        // Strict System Instructions with Scoped Data Constraints & Guardrails
        $systemInstruction = "Anda adalah ExamBot, asisten AI resmi untuk platform \"AI Online Exam & Proctoring System\" (AI-Exam).\n" .
            "IDENTITAS PENGGUNA SAAT INI:\n" .
            "- Nama: {$userName}\n" .
            "- Role: {$role}\n" .
            "- Data Akses Khusus (Scoped Context) Pengguna Ini:\n{$scopedSummary}\n\n" .
            "- DATA SELURUH TABEL DATABASE SISTEM (13 TABEL TERHUBUNG LENGKAP):\n{$omniSummary}{$dbQuestionsContext}\n\n" .
            "PEDOMAN JAWABAN RINGKAS BERTINGKAT (PROGRESSIVE DISCLOSURE - WAJIB DIPATUHI):\n" .
            "1. JAWAB SINGKAT DI AWAL (DEFAULT): Jika pengguna menanyakan jumlah, total, status, atau gambaran umum (contoh: \"ada berapa user?\", \"ada berapa ujian?\", \"ada berapa guru?\"), berikan jawaban yang SANGAT SINGKAT DAN LANGSUNG KE INTI (1-2 kalimat padat, contoh: \"Saat ini ada 10 user pada sistem ujian online (5 siswa, 4 guru, dan 1 admin). Ketik 'rincian user' jika Anda ingin melihat daftar lengkapnya.\"). JANGAN LANGSUNG memberikan daftar panjang berpoin-poin atau tabel besar di awal sebelum diminta.\n" .
            "2. BERIKAN RINCIAN HANYA JIKA DIMINTA: Jika pengguna meminta rincian (contoh: \"minta rinciannya\", \"rincian user\", \"detailnya\", \"siapa saja\"), barulah tampilkan data lengkap dan rinci yang 100% akurat sesuai database di atas.\n" .
            "3. WAJIB 100% SESUAI & RELEVAN DENGAN DATABASE: Seluruh fakta, nomor butir soal, kunci jawaban, pembahasan guru, jadwal, hasil ujian, statistik peserta, dan log proctoring HARUS 100% SESUAI DATA DATABASE RESMI DI ATAS. Dilarang keras mengarang kunci atau data di luar database.\n" .
            "4. DILARANG KERAS MENJAWAB PERMINTAAN KODING / PROGRAMMING UMUM:\n" .
            "   - Tolak secara tegas dan ringkas jika pengguna meminta script python, tugas pemrograman eksternal, puisi, resep, atau hiburan non-ujian.\n" .
            "5. BATAS AKSES (SCOPED ACCESS): Hanya tampilkan data milik pengguna ini. JANGAN PERNAH menampilkan data/nilai siswa lain atau kredensial rahasia server.\n" .
            "6. GUEST: Jika role adalah guest (belum login) dan menanyakan nilai/jadwal pribadi, arahkan secara singkat untuk login terlebih dahulu.\n" .
            "7. GAYA BAHASA: Bersahabat, profesional, ringkas, berbahasa Indonesia dengan Markdown rapi.";

        $payload = [
            'systemInstruction' => [
                'parts' => [
                    ['text' => $systemInstruction]
                ]
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $message]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.3,
                'maxOutputTokens' => 500,
                'topP' => 0.9
            ]
        ];

        return self::executeGeminiRequest($payload, self::$candidateModels);
    }

    /**
     * Call Google Gemini API specifically for Question Explainer
     *
     * @param array $questionData
     * @param string $studentMessage
     * @param array $history
     * @return string|null
     */
    public static function callGeminiQuestionExplainer(array $questionData, string $studentMessage, array $history = []): ?string
    {
        $qText = $questionData['question_text'] ?? '';
        $qType = $questionData['question_type'] ?? 'multiple_choice';
        $studentAns = $questionData['student_answer_label'] ?? $questionData['student_answer'] ?? '(Tidak dijawab)';
        $correctAns = $questionData['correct_answer_label'] ?? $questionData['correct_answer'] ?? '';
        $isCorrect = !empty($questionData['is_correct']) ? 'BENAR' : 'SALAH';
        $officialExplanation = $questionData['explanation'] ?? '';

        $optionsText = '';
        if (!empty($questionData['options']) && is_array($questionData['options'])) {
            foreach ($questionData['options'] as $opt) {
                $optionsText .= "- " . ($opt['option_label'] ?? '') . ". " . ($opt['option_text'] ?? '') . "\n";
            }
        }

        $systemInstruction = "Anda adalah AI Academic Tutor resmi untuk pembahasan soal ujian online.\n" .
            "SUMBER KEBENARAN MUTLAK RESMI (DATABASE OF RECORD):\n" .
            "- Teks Soal: {$qText}\n" .
            "- Tipe Soal: {$qType}\n" .
            (!empty($optionsText) ? "- Pilihan Jawaban:\n{$optionsText}\n" : "") .
            "- Jawaban Murid: {$studentAns} (Status: {$isCorrect})\n" .
            "- Kunci Jawaban Resmi: {$correctAns}\n" .
            "- Pembahasan Resmi Database Guru: {$officialExplanation}\n\n" .
            "PEDOMAN TUTOR RINGKAS, EDUKATIF & 100% DATABASE:\n" .
            "1. WAJIB: Seluruh pembahasan, formula, dan penjelasan Anda HARUS 100% PERSIS DAN SESUAI DENGAN PEMBAHASAN RESMI DARI DATABASE: \"{$officialExplanation}\".\n" .
            "2. JAWAB RINGKAS & TEPAT SASARAN: Jawab secara padat dan langsung fokus pada pertanyaan siswa tanpa pengantar atau penutup bertele-tele.\n" .
            "3. JANGAN PERNAH menyimpulkan kunci jawaban atau hasil akhir yang bertolak belakang dengan database.\n" .
            "4. Rinci langkah konsep materi dari dasar ke kesimpulan akhir kunci jawaban dalam format poin-poin ringkas.\n" .
            "5. BATAS RUANG LINGKUP: Jawab HANYA materi butir soal ini. Tolak penulisan koding/program umum atau topik di luar soal ini secara sopan dan ringkas.\n" .
            "6. Format respon dengan Markdown yang jelas, rapi, dan mudah dipahami siswa.";

        $contents = [];

        // Attach previous chat turns if available
        if (!empty($history) && is_array($history)) {
            foreach ($history as $h) {
                $contents[] = [
                    'role' => ($h['role'] === 'user') ? 'user' : 'model',
                    'parts' => [['text' => $h['text']]]
                ];
            }
        }

        // Add the current student message
        $promptText = ($studentMessage === '' || $studentMessage === 'init')
            ? "Halo Tutor AI, tolong jelaskan pembahasan lengkap untuk soal ini dan evaluasi jawaban saya."
            : $studentMessage;

        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $promptText]]
        ];

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $systemInstruction]]
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.3,
                'maxOutputTokens' => 500
            ]
        ];

        return self::executeGeminiRequest($payload, self::$candidateModels);
    }

    /**
     * Execute Gemini API request with multi-model fallback and strict timeout
     *
     * @param array $payload
     * @param array $models
     * @return string|null
     */
    private static function executeGeminiRequest(array $payload, array $models): ?string
    {
        $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
        if (empty($apiKey)) {
            return null;
        }

        $activeModel = class_exists('SettingModel') ? SettingModel::get('gemini_model') : null;
        if (!empty($activeModel) && in_array($activeModel, ['gemini-3.8-flash', 'gemma-4-26b-a4b-it', 'gemini-3.1-flash-lite'], true)) {
            $models = array_values(array_unique(array_merge([$activeModel], $models)));
        }

        $jsonPayload = json_encode($payload);
        $startTime = microtime(true);
        foreach ($models as $model) {
            // Never block the user experience longer than 5 seconds in total
            if ((microtime(true) - $startTime) > 5.0) {
                break;
            }

            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json; charset=utf-8',
                'x-goog-api-key: ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 4);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if (!$curlError && $httpCode === 200 && $response) {
                $result = json_decode($response, true);
                if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                    return trim($result['candidates'][0]['content']['parts'][0]['text']);
                }
            }
        }

        return null;
    }

    /**
     * Local rule-based engine with Scoped Knowledge
     *
     * @param string $rawMessage
     * @param array|null $user
     * @param array $scopedContext
     * @return array
     */
    public static function replyWithScopedLocalEngine(string $rawMessage, ?array $user, array $scopedContext, array $preMatchedQuestions = []): array
    {
        $normalized = mb_strtolower($rawMessage, 'UTF-8');
        $role = $user['role'] ?? 'guest';
        $userName = $user['full_name'] ?? 'Pengunjung';

        // 1. Direct Ping / Test Check
        if (preg_match('/^(tes|test|testing|cek|ping|coba)\b/i', $normalized)) {
            $apiStatus = (defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY) && str_starts_with(GEMINI_API_KEY, 'AIzaSy'))
                ? "*(ℹ️ Status Gemini: API Key terpasang dengan mode multi-model fallback & local knowledge engine)*"
                : "*(ℹ️ Sistem saat ini berjalan menggunakan Knowledge Base cerdas lokal)*";

            return [
                'status' => 'success',
                'source' => 'local',
                'reply' => "⚡ **Koneksi ExamBot Aktif & Siap!**\n\nSistem AI Chatbot berjalan normal untuk role: **" . strtoupper($role) . "**.\n\n{$apiStatus}\n\nAda yang ingin Anda tanyakan seputar sistem ujian online?",
                'suggestions' => self::getSuggestionsForRole($role),
                'action' => null
            ];
        }

        // 2. Greeting / Sapaan
        if (preg_match('/^(halo|hai|hi|hello|selamat|assalamualaikum|pagi|siang|sore|malam)\b/i', $normalized)) {
            $greetingTime = self::getGreetingTime();
            return [
                'status' => 'success',
                'source' => 'local',
                'reply' => "{$greetingTime}, **{$userName}**! 👋 Saya **ExamBot**, asisten AI sistem ujian online & AI proctoring.\n\nAda yang bisa saya bantu hari ini?",
                'suggestions' => self::getSuggestionsForRole($role),
                'action' => self::getDefaultActionForRole($role)
            ];
        }

        // 3.0.0 Permintaan Rincian Lanjutan (Follow-up Details via Last Topic Session)
        if (preg_match('/(siapa saja|siapa aja|apa saja|apa aja|dijabarkan|jabarkan|minta rincian|berikan rincian|rinciannya|detailnya|minta detail|rincian lengkap|detail lengkap|sebutkan)/iu', $normalized) || $normalized === 'rincian' || $normalized === 'detail') {
            // Direct specific mentions
            if (preg_match('/(user|pengguna|akun)/iu', $normalized)) {
                $_SESSION['chatbot_last_topic'] = 'users';
                return self::getUserDetailsResponse($role);
            }
            if (preg_match('/(guru|pengajar)/iu', $normalized)) {
                $_SESSION['chatbot_last_topic'] = 'teachers';
                return self::getTeacherDetailsResponse();
            }
            if (preg_match('/(siswa|murid)/iu', $normalized)) {
                $_SESSION['chatbot_last_topic'] = 'students';
                return self::getStudentDetailsResponse();
            }
            if (preg_match('/(kelas|rombel)/iu', $normalized)) {
                $_SESSION['chatbot_last_topic'] = 'classes';
                return self::getClassDetailsResponse();
            }
            if (preg_match('/(mapel|mata pelajaran|pelajaran)/iu', $normalized)) {
                $_SESSION['chatbot_last_topic'] = 'subjects';
                return self::getSubjectDetailsResponse();
            }
            if (preg_match('/(ujian|sesi)/iu', $normalized)) {
                $_SESSION['chatbot_last_topic'] = 'exams';
                return self::getExamDetailsResponse($role);
            }

            // Contextual fallback from last topic in session
            $lastTopic = $_SESSION['chatbot_last_topic'] ?? '';
            if ($lastTopic === 'users') {
                return self::getUserDetailsResponse($role);
            } elseif ($lastTopic === 'teachers') {
                return self::getTeacherDetailsResponse();
            } elseif ($lastTopic === 'students') {
                return self::getStudentDetailsResponse();
            } elseif ($lastTopic === 'classes') {
                return self::getClassDetailsResponse();
            } elseif ($lastTopic === 'subjects') {
                return self::getSubjectDetailsResponse();
            } elseif ($lastTopic === 'exams') {
                return self::getExamDetailsResponse($role);
            } else {
                return [
                    'status' => 'success',
                    'source' => 'local',
                    'reply' => "Silakan sebutkan data apa yang ingin dijabarkan (misal: **\"siapa saja gurunya\"**, **\"siapa saja usernya\"**, **\"siapa saja siswanya\"**, **\"apa saja kelasnya\"**, atau **\"apa saja mapelnya\"**).",
                    'suggestions' => ['Siapa saja gurunya', 'Siapa saja usernya', 'Siapa saja siswanya'],
                    'action' => null
                ];
            }
        }

        // 3.0.1 Identitas Pengguna ("siapa saya", "siapakah saya", "akun saya", "profil saya")
        if (preg_match('/(siapa\s+(saya|aku|diriku|nama\s*saya|nama\s*ku)|siapakah\s*(saya|aku)|(saya|aku)\s*siapa|profil\s*saya|identitas\s*saya|akun\s*saya)/iu', $normalized)) {
            $db = Database::getInstance();
            if (!$user || empty($user['id']) || $role === 'guest') {
                return [
                    'status' => 'success',
                    'source' => 'local',
                    'reply' => "👤 **Identitas Pengguna**:\nSaat ini Anda mengakses sistem sebagai **Tamu (Guest)** (belum login).\n\nSilakan masuk (login) ke akun Siswa, Guru, atau Admin Anda untuk melihat profil dan data pribadi Anda.",
                    'suggestions' => ['Akun demo untuk uji coba', 'Apa itu AI Proctoring?'],
                    'action' => ['label' => 'Login Sekarang', 'url' => BASE_URL . '/auth/login', 'icon' => 'bi-box-arrow-in-right']
                ];
            }

            if ($role === 'student') {
                $st = null;
                if ($db->isConnected()) {
                    $st = $db->query("
                        SELECT s.nis, s.nisn, c.name AS class_name
                        FROM students s
                        LEFT JOIN classes c ON s.class_id = c.id
                        WHERE s.user_id = :uid
                        LIMIT 1
                    ")->bind(':uid', $user['id'])->single();
                }
                $nisStr = !empty($st['nis']) ? "NIS: `{$st['nis']}`" : "NIS: -";
                $clsStr = !empty($st['class_name']) ? "Kelas: **{$st['class_name']}**" : "Kelas: Belum ditentukan";

                return [
                    'status' => 'success',
                    'source' => 'database_info',
                    'reply' => "👤 **Identitas Anda (Siswa)**:\n- Nama: **{$userName}**\n- {$nisStr}\n- {$clsStr}\n- Peran: **Siswa (Student)**\n- Status: Terdaftar aktif di sistem ujian.",
                    'suggestions' => ['Jadwal ujian saya yang tersedia', 'Berapa nilai ujian saya yang selesai?'],
                    'action' => ['label' => 'Buka Ujian Saya', 'url' => BASE_URL . '/student/exams', 'icon' => 'bi-journal-check']
                ];
            }

            if ($role === 'teacher') {
                $tc = null;
                if ($db->isConnected()) {
                    $tc = $db->query("
                        SELECT t.nip, t.specialization
                        FROM teachers t
                        WHERE t.user_id = :uid
                        LIMIT 1
                    ")->bind(':uid', $user['id'])->single();
                }
                $nipStr = !empty($tc['nip']) ? "NIP: `{$tc['nip']}`" : "NIP: -";
                $specStr = !empty($tc['specialization']) ? "Bidang: **{$tc['specialization']}**" : "Bidang: Umum";

                return [
                    'status' => 'success',
                    'source' => 'database_info',
                    'reply' => "👨‍🏫 **Identitas Anda (Guru)**:\n- Nama: **{$userName}**\n- {$nipStr}\n- {$specStr}\n- Peran: **Guru Pengajar (Teacher)**\n- Status: Terdaftar aktif mengampu ujian.",
                    'suggestions' => ['Rekapitulasi ujian yang saya buat', 'Kelola bank soal'],
                    'action' => ['label' => 'Live Monitoring', 'url' => BASE_URL . '/teacher/monitoring', 'icon' => 'bi-camera-video']
                ];
            }

            if ($role === 'admin') {
                return [
                    'status' => 'success',
                    'source' => 'database_info',
                    'reply' => "🛡️ **Identitas Anda (Administrator)**:\n- Nama: **{$userName}**\n- Peran: **Super Administrator (Admin)**\n- Hak Akses: Pengawasan penuh master data dan server ujian.",
                    'suggestions' => ['Ada berapa user', 'Ada berapa kelas', 'Ada berapa guru'],
                    'action' => ['label' => 'Dashboard Admin', 'url' => BASE_URL . '/admin/dashboard', 'icon' => 'bi-speedometer2']
                ];
            }
        }

        // 3. Identitas ExamBot (Menjawab "siapa anda", "siapakah anda", "siapa kamu", dll)
        if (!preg_match('/(guru|pengajar|siswa|murid|peserta|admin|saya|aku)/i', $normalized) && preg_match('/(siapa\s+(kamu|anda|dirimu|namamu|nama\s*anda|nama\s*kamu|bot|exambot)|siapakah\s*(kamu|anda|dirimu|exambot)|(kamu|anda)\s*siapa|tentang\s*(kamu|anda|exambot|bot)|identitas|bot\s*apa|profil\s*bot|apa\s*itu\s*exambot|^siapa\??$)/i', $normalized)) {
            return [
                'status' => 'success',
                'source' => 'local',
                'reply' => "🤖 **Saya adalah ExamBot**, asisten AI resmi platform **AI Online Exam & Proctoring System**.\n\n" .
                    "Saya bertugas membantu Anda secara ringkas dan akurat terkait:\n" .
                    "- 📋 **Data Akademik**: Informasi jadwal ujian, kelas, guru, siswa, dan mata pelajaran dari database.\n" .
                    "- 📚 **Pembahasan Soal**: Kunci jawaban dan pembahasan resmi dari butir soal database.\n" .
                    "- 🛡️ **AI Proctoring**: Aturan pengawasan kamera, tata tertib, dan kendala teknis ujian.",
                'suggestions' => self::getSuggestionsForRole($role),
                'action' => self::getDefaultActionForRole($role)
            ];
        }

        // 3.1 Ucapan Terima Kasih
        if (preg_match('/^(terima kasih|makasih|thanks|thank you|syukron)\b/i', $normalized)) {
            return [
                'status' => 'success',
                'source' => 'local',
                'reply' => "Sama-sama, **{$userName}**! 😊 Senang bisa membantu Anda. Silakan tanyakan hal lain seputar sistem ujian atau butir soal database kapan saja!",
                'suggestions' => self::getSuggestionsForRole($role),
                'action' => null
            ];
        }

        // 3.2.1 Status Siswa yang Telah Submit Ujian (Tabel exam_participants, users, students, classes)
        if (!preg_match('/(alur|cara|panduan|bagaimana|langkah)/iu', $normalized) && preg_match('/(siswa.*(submit|mengumpulkan|kumpul|selesai).*ujian|(submit|mengumpulkan|kumpul|selesai).*ujian.*siswa|berapa siswa.*submit|siswa.*sudah submit|siswa.*telah submit)/iu', $normalized)) {
            $db = Database::getInstance();
            $submittedStudents = [];
            $totalSubmissions = 0;
            if ($db->isConnected()) {
                $submittedStudents = $db->query("
                    SELECT DISTINCT u.full_name, s.nis, c.name AS class_name
                    FROM exam_participants ep
                    JOIN students s ON ep.student_id = s.id
                    JOIN users u ON s.user_id = u.id
                    LEFT JOIN classes c ON s.class_id = c.id
                    WHERE ep.status = 'submitted'
                    ORDER BY u.full_name ASC
                ")->resultSet() ?: [];
                $totalSubmissions = $db->query("SELECT COUNT(*) as cnt FROM exam_participants WHERE status = 'submitted'")->single()['cnt'] ?? 0;
            }

            $count = count($submittedStudents);
            if ($count > 0) {
                $lines = ["Terdapat **{$count} siswa** yang telah submit (mengumpulkan) ujian:"];
                foreach ($submittedStudents as $st) {
                    $cls = $st['class_name'] ? " ({$st['class_name']})" : '';
                    $lines[] = "- **{$st['full_name']}** - NIS: `{$st['nis']}`{$cls}";
                }
                $lines[] = "*(Total riwayat submit penugasan: {$totalSubmissions} kali)*";
                $replyText = implode("\n", $lines);
            } else {
                $replyText = "Belum ada siswa yang melakukan submit ujian di sistem.";
            }

            return [
                'status' => 'success',
                'source' => 'database_info',
                'reply' => $replyText,
                'suggestions' => ['Siapa siswa yang telah mengikuti ujian', 'Status peserta ujian', 'Rekap nilai kelulusan'],
                'action' => ($role === 'teacher') ? ['label' => 'Buka Live Monitoring', 'url' => BASE_URL . '/teacher/monitoring', 'icon' => 'bi-camera-video'] : null
            ];
        }

        // 3.2.2 Siswa yang Telah / Sedang Mengikuti Ujian (Tabel exam_participants, users, students, classes)
        if (!preg_match('/(alur|cara|panduan|bagaimana|langkah)/iu', $normalized) && preg_match('/((siapa|daftar|mana|sebutkan).*siswa.*(mengikuti|ikut|mengerjakan|ujian)|siswa.*yang.*(mengikuti|ikut|mengerjakan|telah ujian|sudah ujian)|siswa.*(telah|sudah).*mengikuti.*ujian)/iu', $normalized)) {
            $db = Database::getInstance();
            $activeStudents = [];
            if ($db->isConnected()) {
                $activeStudents = $db->query("
                    SELECT u.full_name, s.nis, c.name AS class_name
                    FROM students s
                    JOIN users u ON s.user_id = u.id
                    LEFT JOIN classes c ON s.class_id = c.id
                    WHERE s.id IN (SELECT DISTINCT student_id FROM exam_participants WHERE status IN ('in_progress', 'submitted'))
                    ORDER BY u.full_name ASC
                ")->resultSet() ?: [];
            }

            $count = count($activeStudents);
            if ($count > 0) {
                $lines = ["Siswa yang telah mengikuti ujian di sistem berjumlah **{$count} siswa**:"];
                foreach ($activeStudents as $st) {
                    $cls = $st['class_name'] ? ", Kelas: {$st['class_name']}" : '';
                    $lines[] = "- **{$st['full_name']}** (NIS: `{$st['nis']}`{$cls})";
                }
                $replyText = implode("\n", $lines);
            } else {
                $replyText = "Belum ada siswa yang tercatat mengikuti sesi ujian di sistem.";
            }

            return [
                'status' => 'success',
                'source' => 'database_info',
                'reply' => $replyText,
                'suggestions' => ['Ada berapa siswa yang telah submit ujian', 'Status peserta ujian', 'Sesi ujian aktif'],
                'action' => null
            ];
        }

        // 3.2.3 Jumlah Siswa di Setiap Kelas (Tabel classes, students, users)
        if (preg_match('/(siswa.*(setiap|tiap|per|masing[- ]masing).*kelas|siswa.*kelas.*nya|jumlah siswa.*(di|pada)?.*kelas|berapa siswa.*per kelas|banyak siswa.*di setiap kelas)/iu', $normalized)) {
            $db = Database::getInstance();
            $classStudents = [];
            if ($db->isConnected()) {
                $classStudents = $db->query("
                    SELECT c.name AS class_name, c.level, c.academic_year, COUNT(s.id) AS total_students,
                           GROUP_CONCAT(u.full_name SEPARATOR ', ') AS student_names
                    FROM classes c
                    LEFT JOIN students s ON c.id = s.class_id
                    LEFT JOIN users u ON s.user_id = u.id
                    GROUP BY c.id, c.name, c.level, c.academic_year
                    ORDER BY c.name ASC
                ")->resultSet() ?: [];
            }

            if (!empty($classStudents)) {
                $lines = ["Jumlah siswa di setiap kelas:"];
                $totalAll = 0;
                foreach ($classStudents as $cs) {
                    $totalAll += (int)$cs['total_students'];
                    $names = $cs['student_names'] ? " ({$cs['student_names']})" : '';
                    $lines[] = "- **{$cs['class_name']}**: **{$cs['total_students']} siswa**{$names}";
                }
                $lines[] = "*(Total keseluruhan: {$totalAll} siswa)*";
                $replyText = implode("\n", $lines);
            } else {
                $replyText = "Belum ada data rombel kelas atau siswa yang tercatat di database.";
            }

            return [
                'status' => 'success',
                'source' => 'database_info',
                'reply' => $replyText,
                'suggestions' => ['NIS setiap siswa', 'Ada berapa kelas', 'Ada berapa siswa'],
                'action' => null
            ];
        }

        // 3.2.4 Nomor Induk Siswa (NIS) Setiap Siswa (Tabel students, users, classes)
        if (preg_match('/(nis.*siswa|nomor induk siswa|nis.*masing|nis.*setiap|daftar nis)/iu', $normalized)) {
            $db = Database::getInstance();
            $nisList = [];
            if ($db->isConnected()) {
                $nisList = $db->query("
                    SELECT s.nis, s.nisn, u.full_name, c.name AS class_name
                    FROM students s
                    JOIN users u ON s.user_id = u.id
                    LEFT JOIN classes c ON s.class_id = c.id
                    ORDER BY s.nis ASC
                ")->resultSet() ?: [];
            }

            if (!empty($nisList)) {
                $lines = ["Nomor Induk Siswa (NIS) setiap siswa:"];
                foreach ($nisList as $ns) {
                    $cls = $ns['class_name'] ? " (Kelas: {$ns['class_name']})" : '';
                    $lines[] = "- **{$ns['full_name']}**: NIS `{$ns['nis']}`{$cls}";
                }
                $replyText = implode("\n", $lines);
            } else {
                $replyText = "Belum ada data NIS siswa yang terdaftar di database.";
            }

            return [
                'status' => 'success',
                'source' => 'database_info',
                'reply' => $replyText,
                'suggestions' => ['Jumlah siswa di setiap kelas nya', 'Ada berapa siswa', 'Ada berapa kelas'],
                'action' => null
            ];
        }

        // 3.2.5 Jumlah Soal di Setiap Mata Pelajaran (Tabel subjects, questions)
        if (preg_match('/(soal.*(setiap|tiap|per|masing[- ]masing).*(mapel|mata pelajaran|pelajaran)|berapa soal.*mapel|jumlah soal.*mapel|soal.*setiap.*mata pelajaran)/iu', $normalized)) {
            $db = Database::getInstance();
            $soalPerMapel = [];
            if ($db->isConnected()) {
                $soalPerMapel = $db->query("
                    SELECT s.name AS subject_name, s.code, COUNT(q.id) AS total_questions
                    FROM subjects s
                    LEFT JOIN questions q ON s.id = q.subject_id
                    GROUP BY s.id, s.name, s.code
                    ORDER BY s.name ASC
                ")->resultSet() ?: [];
            }

            if (!empty($soalPerMapel)) {
                $lines = ["Jumlah butir soal di setiap mata pelajaran:"];
                $totalQ = 0;
                foreach ($soalPerMapel as $spm) {
                    $totalQ += (int)$spm['total_questions'];
                    $lines[] = "- **{$spm['subject_name']}** (`{$spm['code']}`): **{$spm['total_questions']} butir soal**";
                }
                $lines[] = "*(Total bank soal keseluruhan: {$totalQ} butir)*";
                $replyText = implode("\n", $lines);
            } else {
                $replyText = "Belum ada data butir soal per mata pelajaran di database.";
            }

            return [
                'status' => 'success',
                'source' => 'database_info',
                'reply' => $replyText,
                'suggestions' => ['Ada berapa mata pelajaran', 'Bahas soal nomor 1', 'Panduan membuat bank soal'],
                'action' => ($role === 'teacher') ? ['label' => 'Kelola Bank Soal', 'url' => BASE_URL . '/teacher/questions', 'icon' => 'bi-folder-check'] : null
            ];
        }

        // 3.2.6 Siapa Guru Mata Pelajaran Tertentu (Tabel teachers, users, subjects)
        if (preg_match('/(siapa.*guru\s+([a-zA-Z\.\s]+)|guru\s+([a-zA-Z\.\s]+)\s+siapa|guru\s+(matematika|biologi|inggris|b\.\s*inggris|indonesia|bahasa indonesia))/iu', $normalized, $mMatch)) {
            $searchMapel = '';
            if (!empty($mMatch[4])) {
                $searchMapel = trim($mMatch[4]);
            } elseif (!empty($mMatch[2])) {
                $searchMapel = trim($mMatch[2]);
            } elseif (!empty($mMatch[3])) {
                $searchMapel = trim($mMatch[3]);
            }
            $searchMapel = trim(preg_replace('/\b(di|ini|sekolah|apa|saya|kami|yang)\b/i', '', $searchMapel));

            $db = Database::getInstance();
            $teachers = [];
            if ($db->isConnected()) {
                $param = '%' . $searchMapel . '%';
                $teachers = $db->query("
                    SELECT u.full_name, t.nip, t.specialization
                    FROM teachers t
                    JOIN users u ON t.user_id = u.id
                    WHERE t.specialization LIKE :p1 OR u.full_name LIKE :p2
                    ORDER BY u.full_name ASC
                ")->bind(':p1', $param)->bind(':p2', $param)->resultSet() ?: [];
            }

            if (!empty($teachers)) {
                $lines = [];
                if (count($teachers) === 1) {
                    $t = $teachers[0];
                    $spec = $t['specialization'] ? ", Bidang: {$t['specialization']}" : '';
                    $lines[] = "Guru pengajar **" . ucwords($searchMapel) . "** di sistem adalah **{$t['full_name']}** (NIP: `{$t['nip']}`{$spec}).";
                } else {
                    $lines[] = "Guru pengajar **" . ucwords($searchMapel) . "** di sistem:";
                    foreach ($teachers as $t) {
                        $spec = $t['specialization'] ? " (Bidang: {$t['specialization']})" : '';
                        $lines[] = "- **{$t['full_name']}** - NIP: `{$t['nip']}`{$spec}";
                    }
                }
                $replyText = implode("\n", $lines);
            } else {
                $replyText = "Belum ditemukan data guru untuk mata pelajaran **" . htmlspecialchars($searchMapel) . "** di sistem.";
            }

            return [
                'status' => 'success',
                'source' => 'database_info',
                'reply' => $replyText,
                'suggestions' => ['Ada berapa guru', 'Ada berapa mata pelajaran', 'Daftar mata pelajaran'],
                'action' => null
            ];
        }

        // 3.2.7.a Rincian / Daftar Guru (Jika eksplisit meminta daftar/rincian)
        if (preg_match('/(daftar guru|rincian guru|detail guru|siapa saja guru|siapa pengajar|siapa saja pengajar)/iu', $normalized)) {
            $_SESSION['chatbot_last_topic'] = 'teachers';
            return self::getTeacherDetailsResponse();
        }

        // 3.2.7.b Ada Berapa Guru / Total Guru (Jawaban Singkat)
        if (preg_match('/(berapa guru|jumlah guru|ada berapa guru|total guru|ada berapa total guru)/iu', $normalized)) {
            $_SESSION['chatbot_last_topic'] = 'teachers';
            $db = Database::getInstance();
            $count = 0;
            if ($db->isConnected()) {
                $count = (int)($db->query("SELECT COUNT(*) AS total FROM teachers")->single()['total'] ?? 0);
            }

            return [
                'status' => 'success',
                'source' => 'database_info',
                'reply' => "Saat ini terdapat {$count} guru yang terdaftar di database.",
                'suggestions' => ['Siapa saja?', 'Ada berapa total user', 'Ada berapa mata pelajaran'],
                'action' => null
            ];
        }

        // 3.2.8.a Rincian / Daftar Kelas (Jika eksplisit meminta daftar/rincian)
        if (preg_match('/(daftar kelas|rincian kelas|detail kelas|apa saja kelas|kelas apa saja|daftar rombel)/iu', $normalized)) {
            $_SESSION['chatbot_last_topic'] = 'classes';
            return self::getClassDetailsResponse();
        }

        // 3.2.8.b Ada Berapa Kelas / Total Kelas (Jawaban Singkat)
        if (preg_match('/(berapa kelas|jumlah kelas|ada berapa kelas|total kelas|ada berapa total kelas)/iu', $normalized)) {
            $_SESSION['chatbot_last_topic'] = 'classes';
            $db = Database::getInstance();
            $count = 0;
            if ($db->isConnected()) {
                $count = (int)($db->query("SELECT COUNT(*) AS total FROM classes")->single()['total'] ?? 0);
            }

            return [
                'status' => 'success',
                'source' => 'database_info',
                'reply' => "Saat ini terdapat {$count} kelas yang terdaftar di sistem.",
                'suggestions' => ['Apa saja?', 'Ada berapa total siswa', 'Ada berapa mata pelajaran'],
                'action' => null
            ];
        }

        // 3.2.9.a Rincian / Daftar Siswa (Jika eksplisit meminta daftar/rincian)
        if (preg_match('/(daftar siswa|rincian siswa|detail siswa|siapa saja siswa|siapa murid|siapa saja murid)/iu', $normalized)) {
            $_SESSION['chatbot_last_topic'] = 'students';
            return self::getStudentDetailsResponse();
        }

        // 3.2.9.b Ada Berapa Siswa / Total Siswa (Jawaban Singkat)
        if (preg_match('/(berapa siswa|jumlah siswa|ada berapa siswa|total siswa|ada berapa total siswa|berapa murid)/iu', $normalized)) {
            $_SESSION['chatbot_last_topic'] = 'students';
            $db = Database::getInstance();
            $count = 0;
            if ($db->isConnected()) {
                $count = (int)($db->query("SELECT COUNT(*) AS total FROM students")->single()['total'] ?? 0);
            }

            return [
                'status' => 'success',
                'source' => 'database_info',
                'reply' => "Saat ini terdapat {$count} siswa yang terdaftar di sistem.",
                'suggestions' => ['Siapa saja?', 'Ada berapa total guru', 'Ada berapa kelas'],
                'action' => null
            ];
        }

        // 3.2.10.a Rincian / Daftar Ujian (Jika eksplisit meminta daftar/rincian)
        if (preg_match('/(daftar ujian|rincian ujian|detail ujian|apa saja ujian|jadwal ujian)/iu', $normalized) && !preg_match('/(peserta|siswa|saya|nilai)/iu', $normalized)) {
            $_SESSION['chatbot_last_topic'] = 'exams';
            return self::getExamDetailsResponse($role);
        }

        // 3.2.10.b Sesi Ujian, Jumlah Ujian (Jawaban Singkat)
        if (preg_match('/(berapa.*ujian|ujian.*berapa|total.*ujian|jumlah.*ujian|banyak.*ujian|ada.*ujian|sesi.*ujian)/iu', $normalized) && !preg_match('/(peserta|siswa|saya|nilai)/iu', $normalized)) {
            $_SESSION['chatbot_last_topic'] = 'exams';
            $db = Database::getInstance();
            $totalExams = 0;
            if ($db->isConnected()) {
                $totalExams = (int)($db->query("SELECT COUNT(*) AS total FROM exams")->single()['total'] ?? 0);
            }

            return [
                'status' => 'success',
                'source' => 'database_info',
                'reply' => "Saat ini terdapat total {$totalExams} sesi ujian yang tercatat di database.",
                'suggestions' => ['Apa saja?', 'Ada berapa total peserta', 'Passing grade ujian'],
                'action' => ($role === 'guest') ? ['label' => 'Login Siswa', 'url' => BASE_URL . '/auth/login', 'icon' => 'bi-box-arrow-in-right'] : ['label' => 'Buka Daftar Ujian', 'url' => BASE_URL . '/student/exams', 'icon' => 'bi-journal-check']
            ];
        }

        // 3.2.11.a Rincian / Daftar Mapel (Jika eksplisit meminta daftar/rincian)
        if (preg_match('/(daftar mapel|daftar mata pelajaran|rincian mapel|detail mapel|apa saja mapel|mapel apa saja|pelajaran apa saja)/iu', $normalized)) {
            $_SESSION['chatbot_last_topic'] = 'subjects';
            return self::getSubjectDetailsResponse();
        }

        // 3.2.11.b Ada Berapa Mata Pelajaran (Jawaban Singkat)
        if (preg_match('/(ada berapa mata pelajaran|berapa mata pelajaran|jumlah mata pelajaran|total mata pelajaran|ada berapa total mata pelajaran|ada berapa mapel|berapa mapel|jumlah mapel|total mapel|ada berapa total mapel)/iu', $normalized)) {
            $_SESSION['chatbot_last_topic'] = 'subjects';
            $db = Database::getInstance();
            $count = 0;
            if ($db->isConnected()) {
                $count = (int)($db->query("SELECT COUNT(*) AS total FROM subjects")->single()['total'] ?? 0);
            }

            return [
                'status' => 'success',
                'source' => 'database_info',
                'reply' => "Saat ini terdapat {$count} mata pelajaran yang terdaftar di database.",
                'suggestions' => ['Apa saja?', 'Ada berapa total guru', 'Ada berapa soal'],
                'action' => null
            ];
        }

        // 3.2.12.a Rincian / Daftar Pengguna (Jika eksplisit meminta rincian/siapa saja)
        if (preg_match('/(rincian user|detail user|daftar user|siapa saja user|siapa saja usernya|rincian pengguna|detail pengguna|daftar pengguna|siapa saja pengguna)/iu', $normalized)) {
            $_SESSION['chatbot_last_topic'] = 'users';
            return self::getUserDetailsResponse($role);
        }

        // 3.2.12.b Ada Berapa Total User (Jawaban Singkat)
        if (preg_match('/(berapa pengguna|jumlah pengguna|total pengguna|jumlah user|total user|berapa user|ada berapa user|ada berapa total user)/iu', $normalized)) {
            $_SESSION['chatbot_last_topic'] = 'users';
            $db = Database::getInstance();
            $total = 0;
            if ($db->isConnected()) {
                $total = (int)($db->query("SELECT COUNT(*) AS total FROM users")->single()['total'] ?? 0);
            }

            return [
                'status' => 'success',
                'source' => 'database_info',
                'reply' => "Saat ini terdapat {$total} pengguna (user) pada sistem ujian online.",
                'suggestions' => ['Siapa saja usernya?', 'Ada berapa total guru', 'Ada berapa total siswa'],
                'action' => ($role === 'admin') ? ['label' => 'Buka Manajemen Pengguna', 'url' => BASE_URL . '/admin/users', 'icon' => 'bi-people'] : null
            ];
        }

        // 3.6 Fitur & Gambaran Umum Sistem
        if (preg_match('/(fitur apa saja|fitur sistem|apa fungsi|keunggulan|apa itu ai exam|cara kerja sistem|gambaran sistem)/i', $normalized)) {
            return [
                'status' => 'success',
                'source' => 'local',
                'reply' => "🚀 **Fitur Utama AI Online Exam & Proctoring System (AI-Exam)**:\n\n" .
                    "1. 🛡️ **AI Proctoring Real-Time**: Pengawasan otomatis berbasis webcam meliputi deteksi wajah (face tracking), pencegahan tab switch, dan penghitungan Trust Index.\n" .
                    "2. 💾 **Resilient Auto-Save**: Setiap jawaban siswa tersimpan otomatis secara berkala, aman dari risiko listrik mati atau koneksi putus.\n" .
                    "3. 📚 **Bank Soal & Pembahasan Database**: Mendukung soal Pilihan Ganda (A-E) dan Essay dengan kunci jawaban serta pembahasan komprehensif.\n" .
                    "4. 🤖 **AI Academic Tutor & Explainer**: Membantu siswa mengevaluasi kesalahan dan memahami konsep kunci tiap butir soal setelah ujian selesai.\n" .
                    "5. 👨‍🏫 **Live Monitoring Guru**: Dashboard pemantauan ujian langsung dengan snapshot peringatan kecurangan dan verifikasi pelanggaran.",
                'suggestions' => ['Bagaimana cara kerja AI Proctoring?', 'Bagaimana alur pengerjaan siswa?', 'Akun demo untuk uji coba'],
                'action' => null
            ];
        }

        // 3.7 Rekapitulasi Pelanggaran & Log AI Proctoring (Tabel proctoring_events)
        if (preg_match('/(berapa pelanggaran|log proctoring|event proctoring|catatan kecurangan|data proctoring|riwayat pelanggaran|rekap proctoring|total pelanggaran|pelanggaran tercatat|pelanggaran siswa|kejadian proctoring)/i', $normalized)) {
            $db = Database::getInstance();
            $procStats = [];
            if ($db->isConnected()) {
                $procStats = $db->query("
                    SELECT COUNT(*) AS total,
                           SUM(CASE WHEN severity = 'CRITICAL' THEN 1 ELSE 0 END) AS critical_cnt,
                           SUM(CASE WHEN severity = 'WARNING' THEN 1 ELSE 0 END) AS warning_cnt,
                           SUM(CASE WHEN event_type = 'FACE_NOT_DETECTED' THEN 1 ELSE 0 END) AS face_missing,
                           SUM(CASE WHEN event_type = 'MULTIPLE_FACE' THEN 1 ELSE 0 END) AS multi_face,
                           SUM(CASE WHEN event_type = 'FACE_CHANGED' THEN 1 ELSE 0 END) AS face_changed,
                           SUM(CASE WHEN event_type = 'CAMERA_DISCONNECTED' THEN 1 ELSE 0 END) AS cam_dc
                    FROM proctoring_events
                ")->single();
            }

            if (!empty($procStats) && (int)$procStats['total'] > 0) {
                $replyText = "🛡️ **Rekapitulasi Pelanggaran AI Proctoring (Database)**:\n\n" .
                    "- Total Catatan: **{$procStats['total']} kejadian** ({$procStats['critical_cnt']} Kritis, {$procStats['warning_cnt']} Peringatan)\n" .
                    "- Wajah Tidak Terdeteksi: **{$procStats['face_missing']}** kali\n" .
                    "- Wajah Lebih Dari 1 (Multiple Faces): **{$procStats['multi_face']}** kali\n" .
                    "- Pergantian Wajah: **{$procStats['face_changed']}** kali | Kamera Terputus: **{$procStats['cam_dc']}** kali";
            } else {
                $replyText = "🛡️ **AI Proctoring**: Belum ada log kejadian pelanggaran tercatat di database.";
            }

            return [
                'status' => 'success',
                'source' => 'database_info',
                'reply' => $replyText,
                'suggestions' => ['Bagaimana cara kerja AI Proctoring?', 'Status peserta ujian', 'Rekap nilai kelulusan'],
                'action' => ($role === 'teacher') ? ['label' => 'Buka Live Monitoring', 'url' => BASE_URL . '/teacher/monitoring', 'icon' => 'bi-camera-video'] : null
            ];
        }

        // 3.8 Rekapitulasi Hasil & Nilai Kelulusan (Tabel exam_results)
        if (preg_match('/(nilai tertinggi|rekap nilai|rata-rata nilai|berapa.*lulus|peserta.*lulus|kelulusan peserta|rekap hasil ujian|statistik nilai|peringkat nilai)/i', $normalized) && !preg_match('/(nilai saya)/i', $normalized)) {
            $db = Database::getInstance();
            $resStats = [];
            if ($db->isConnected()) {
                $resStats = $db->query("
                    SELECT COUNT(*) AS total,
                           SUM(CASE WHEN is_passed = 1 THEN 1 ELSE 0 END) AS passed,
                           MAX(final_score) AS highest,
                           MIN(final_score) AS lowest,
                           AVG(final_score) AS average
                    FROM exam_results
                ")->single();
            }

            if (!empty($resStats) && (int)$resStats['total'] > 0) {
                $failed = (int)$resStats['total'] - (int)$resStats['passed'];
                $avgFormatted = number_format((float)$resStats['average'], 1);
                $replyText = "📊 **Rekapitulasi Nilai & Kelulusan (Database)**:\n\n" .
                    "- Total Hasil Dinilai: **{$resStats['total']} peserta**\n" .
                    "- Status Kelulusan: **{$resStats['passed']} Lulus** | **{$failed} Belum Lulus**\n" .
                    "- Skor Tertinggi: **{$resStats['highest']}** | Rata-rata: **{$avgFormatted}**";
            } else {
                $replyText = "📊 **Hasil Ujian**: Belum ada rekapitulasi nilai ujian yang diterbitkan di database.";
            }

            return [
                'status' => 'success',
                'source' => 'database_info',
                'reply' => $replyText,
                'suggestions' => ['Status peserta ujian', 'Log pelanggaran proctoring', 'Sesi ujian aktif'],
                'action' => ($role === 'student') ? ['label' => 'Buka Nilai Saya', 'url' => BASE_URL . '/student/exams', 'icon' => 'bi-journal-check'] : null
            ];
        }

        // 3.9 Status Partisipasi Peserta Ujian & Peserta Didik (Tabel exam_participants, students, classes)
        if (preg_match('/(berapa.*peserta|peserta.*berapa|total.*peserta|jumlah.*peserta|banyak.*peserta|status.*peserta|daftar.*peserta|peserta.*submitted|peserta.*mengerjakan|partisipan|peserta.*ujian|peserta.*didik)/iu', $normalized) && !preg_match('/(jadwal|nilai saya)/iu', $normalized)) {
            $db = Database::getInstance();
            $pStats = [];
            $sStats = [];
            $classBreakdown = [];
            if ($db->isConnected()) {
                $pStats = $db->query("
                    SELECT COUNT(*) AS total,
                           COUNT(DISTINCT student_id) AS unique_students,
                           SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) AS submitted,
                           SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress,
                           SUM(CASE WHEN status = 'not_started' THEN 1 ELSE 0 END) AS not_started
                    FROM exam_participants
                ")->single() ?: [];

                $sStats = $db->query("
                    SELECT COUNT(*) AS total_students,
                           COUNT(DISTINCT class_id) AS total_classes
                    FROM students
                ")->single() ?: [];

                $classBreakdown = $db->query("
                    SELECT c.name AS class_name, COUNT(s.id) AS count
                    FROM classes c
                    LEFT JOIN students s ON c.id = s.class_id
                    GROUP BY c.id, c.name
                    ORDER BY c.name ASC
                ")->resultSet() ?: [];
            }

            $totalParticipants = (int)($pStats['total'] ?? 0);
            $totalStudents = (int)($sStats['total_students'] ?? 0);

            if ($totalParticipants > 0 || $totalStudents > 0) {
                $lines = ["👥 **Informasi Jumlah Peserta (Data Database)**:\n"];

                if ($totalParticipants > 0) {
                    $lines[] = "📋 **1. Partisipasi / Penugasan Ujian (Exam Participants)**:";
                    $lines[] = "- Total Penugasan Ujian: **{$totalParticipants} peserta**";
                    $lines[] = "- Selesai Dikumpulkan (*Submitted*): **" . (int)($pStats['submitted'] ?? 0) . " peserta**";
                    $lines[] = "- Sedang Mengerjakan (*In Progress*): **" . (int)($pStats['in_progress'] ?? 0) . " peserta**";
                    $lines[] = "- Belum Memulai: **" . (int)($pStats['not_started'] ?? 0) . " peserta**\n";
                }

                if ($totalStudents > 0) {
                    $clsDetails = [];
                    foreach ($classBreakdown as $cb) {
                        $clsDetails[] = "{$cb['class_name']}: {$cb['count']} siswa";
                    }
                    $clsStr = !empty($clsDetails) ? " (" . implode(', ', $clsDetails) . ")" : "";
                    $lines[] = "🎓 **2. Peserta Didik (Siswa Terdaftar)**:";
                    $lines[] = "- Total Siswa: **{$totalStudents} peserta didik**{$clsStr}";
                    $lines[] = "- Tersebar dalam **" . (int)($sStats['total_classes'] ?? 0) . " rombel kelas** aktif.";
                }

                $replyText = implode("\n", $lines);
            } else {
                $replyText = "👥 **Peserta Ujian**: Belum ada data peserta ujian atau siswa yang tercatat di database.";
            }

            return [
                'status' => 'success',
                'source' => 'database_info',
                'reply' => $replyText,
                'suggestions' => ['Ada berapa total ujian', 'Rekap nilai kelulusan', 'Log pelanggaran proctoring'],
                'action' => ($role === 'teacher') ? ['label' => 'Buka Live Monitoring', 'url' => BASE_URL . '/teacher/monitoring', 'icon' => 'bi-camera-video'] : null
            ];
        }

        // 3.10 Komposisi & Statistik Bank Soal (Tabel questions & question_options)
        if (preg_match('/(berapa soal|jumlah soal|butir soal|berapa butir|soal essay|soal pg|soal pilihan ganda|soal sulit|soal mudah|soal medium|soal hard|kategori soal|total soal|komposisi soal|statistik bank soal|bank soal.*berapa|berapa.*bank soal)/i', $normalized) && !preg_match('/(pembahasan|bahas\b|kunci)/i', $normalized)) {
            $db = Database::getInstance();
            $qStats = [];
            if ($db->isConnected()) {
                $qStats = $db->query("
                    SELECT COUNT(*) AS total,
                           SUM(CASE WHEN question_type = 'multiple_choice' THEN 1 ELSE 0 END) AS total_pg,
                           SUM(CASE WHEN question_type = 'essay' THEN 1 ELSE 0 END) AS total_essay,
                           SUM(CASE WHEN difficulty = 'easy' THEN 1 ELSE 0 END) AS total_easy,
                           SUM(CASE WHEN difficulty = 'medium' THEN 1 ELSE 0 END) AS total_medium,
                           SUM(CASE WHEN difficulty = 'hard' THEN 1 ELSE 0 END) AS total_hard
                    FROM questions
                ")->single();
            }

            if (!empty($qStats) && (int)$qStats['total'] > 0) {
                $replyText = "📚 **Komposisi & Statistik Bank Soal (Database)**:\n\n" .
                    "- Total Butir Soal: **{$qStats['total']} butir**\n" .
                    "- Tipe Soal: **{$qStats['total_pg']} Pilihan Ganda (PG)** & **{$qStats['total_essay']} Essay**\n" .
                    "- Tingkat Kesulitan: **{$qStats['total_easy']} Mudah**, **{$qStats['total_medium']} Sedang**, **{$qStats['total_hard']} Sulit**\n\n" .
                    "*(Ketik misalnya \"Bahas soal nomor 1\" untuk melihat pembahasan butir soal)*";
            } else {
                $replyText = "📚 **Bank Soal**: Belum ada butir soal yang diunggah ke database.";
            }

            return [
                'status' => 'success',
                'source' => 'database_info',
                'reply' => $replyText,
                'suggestions' => ['Bahas soal nomor 1', 'Daftar mata pelajaran', 'Panduan membuat bank soal'],
                'action' => ($role === 'teacher') ? ['label' => 'Kelola Bank Soal', 'url' => BASE_URL . '/teacher/questions', 'icon' => 'bi-folder-check'] : null
            ];
        }

        // 3.11 Demografi Gender Siswa (Tabel students, classes, users)
        if (preg_match('/(demografi siswa|siswa laki-laki|siswa perempuan|gender siswa|komposisi gender siswa)/i', $normalized)) {
            $db = Database::getInstance();
            $sStats = [];
            $sList = [];
            if ($db->isConnected()) {
                $sStats = $db->query("
                    SELECT COUNT(*) AS total,
                           SUM(CASE WHEN gender = 'L' THEN 1 ELSE 0 END) AS male,
                           SUM(CASE WHEN gender = 'P' THEN 1 ELSE 0 END) AS female
                    FROM students
                ")->single();

                $sList = $db->query("
                    SELECT u.full_name, s.nis, c.name AS class_name
                    FROM students s
                    JOIN users u ON s.user_id = u.id
                    LEFT JOIN classes c ON s.class_id = c.id
                    ORDER BY u.full_name ASC
                    LIMIT 5
                ")->resultSet();
            }

            if (!empty($sStats) && (int)$sStats['total'] > 0) {
                $sampleStudents = !empty($sList) ? implode(', ', array_map(fn($st) => "{$st['full_name']} ({$st['class_name']})", $sList)) : '-';
                $replyText = "🎓 **Data Siswa Terdaftar di Database**:\n\n" .
                    "- Total Siswa: **{$sStats['total']} peserta** ({$sStats['male']} Laki-laki, {$sStats['female']} Perempuan)\n" .
                    "- Contoh Siswa: {$sampleStudents}";
            } else {
                $replyText = "🎓 **Data Siswa**: Belum ada siswa terdaftar di database.";
            }

            return [
                'status' => 'success',
                'source' => 'database_info',
                'reply' => $replyText,
                'suggestions' => ['Daftar kelas & rombel', 'Status peserta ujian', 'Akun demo untuk login'],
                'action' => null
            ];
        }

        // 3.12 Token & Detail Pengaturan Ujian (Tabel exams)
        if (preg_match('/(token ujian|durasi ujian|kkm ujian|passing score|fitur proctoring ujian)/i', $normalized)) {
            $db = Database::getInstance();
            $latestExam = null;
            if ($db->isConnected()) {
                $latestExam = $db->query("
                    SELECT e.title, e.duration_minutes, e.passing_score, e.enable_proctoring, s.name AS subject_name
                    FROM exams e
                    JOIN subjects s ON e.subject_id = s.id
                    ORDER BY e.id DESC
                    LIMIT 1
                ")->single();
            }

            if ($latestExam) {
                $proctorBadge = $latestExam['enable_proctoring'] ? '🟢 Aktif (Webcam & Tab Monitor)' : '🔴 Nonaktif';
                $replyText = "📋 **Pengaturan Sesi Ujian (Database)**:\n\n" .
                    "- Ujian: **{$latestExam['title']}** ({$latestExam['subject_name']})\n" .
                    "- Durasi Waktu: **{$latestExam['duration_minutes']} menit**\n" .
                    "- Passing Score (KKM): **{$latestExam['passing_score']}**\n" .
                    "- Pengawasan AI Proctoring: {$proctorBadge}";
            } else {
                $replyText = "📋 **Pengaturan Ujian**: Belum ada sesi ujian terdaftar di database.";
            }

            return [
                'status' => 'success',
                'source' => 'database_info',
                'reply' => $replyText,
                'suggestions' => ['Jadwal sesi ujian', 'Apa itu AI Proctoring?', 'Akun demo siswa'],
                'action' => null
            ];
        }

        // 4. Pembahasan & Kunci Jawaban Soal Sesuai Database
        $isDiscussionQuery = (
            preg_match('/(pembahasan|bahas\b|kunci jawaban|penjelasan soal|jelaskan soal|ulas soal|bedah soal|soal nomor|soal no|soal id|soal #|soal limit|soal kuadrat|soal integral|soal dadu|soal turunan|soal 25|soal bcrypt|25\s*[x\*]\s*4|bcrypt|limit|integral|dadu|turunan|fungsi kuadrat|rekap.*soal|kunci.*pembahasan)/i', $normalized)
            || ($role === 'student' && preg_match('/(pembahasan ujian|bahas ujian|evaluasi ujian|soal.*salah|soal yang salah|ujian matematika)/i', $normalized))
            || preg_match('/(?:soal\s+(?:nomor|no|id|#)?\s*[0-9]+|(?:nomor|no|#)\s*[0-9]+)/i', $normalized)
        ) && !preg_match('/(panduan guru|buat bank soal|panduan membuat|cara membuat|tambah soal)/i', $normalized);

        if ($isDiscussionQuery) {
            $matchedQuestions = !empty($preMatchedQuestions) ? $preMatchedQuestions : self::findQuestionsInDatabase($rawMessage, $user);
            if (!empty($matchedQuestions)) {
                if (count($matchedQuestions) === 1) {
                    $replyText = self::formatDatabaseQuestionDiscussion($matchedQuestions[0], $rawMessage);
                } else {
                    $replyText = self::formatMultipleQuestionsSummary($matchedQuestions, 'Rekapitulasi Pembahasan Soal (Berdasarkan Database)');
                }

                $suggestions = [
                    'Bisa jelaskan langkah penyelesaiannya lebih detail?',
                    'Apa rumus atau konsep materi kunci soal ini?',
                    'Tips agar tidak keliru pada soal serupa'
                ];
                if ($role === 'student') {
                    $suggestions[] = 'Jadwal ujian saya yang tersedia';
                }

                $action = ($role === 'student')
                    ? ['label' => 'Buka Evaluasi Ujian Saya', 'url' => BASE_URL . '/student/exams', 'icon' => 'bi-journal-check']
                    : ($role === 'teacher' ? ['label' => 'Buka Bank Soal', 'url' => BASE_URL . '/teacher/questions', 'icon' => 'bi-folder-check'] : null);

                return [
                    'status' => 'success',
                    'source' => 'database_explainer',
                    'reply' => $replyText,
                    'suggestions' => $suggestions,
                    'action' => $action
                ];
            } else {
                $availableList = self::getAvailableDatabaseQuestionsList();
                return [
                    'status' => 'success',
                    'source' => 'database_explainer',
                    'reply' => "📚 **Pencarian Soal & Pembahasan Database**\n\nSistem tidak menemukan butir soal dengan kata kunci tersebut di basis data aktif.\n\n⚠️ **Ketentuan Pembahasan**: Seluruh pembahasan dalam sistem ujian ini **harus dan selalu bersumber 100% dari database soal resmi proyek ini**.\n\n**Daftar Butir Soal yang Tersedia di Database Saat Ini**:\n{$availableList}\n\nSilakan ketik nomor soal atau topik yang ingin dibahas dari daftar di atas (misal: *\"Bahas soal nomor 1\"* atau *\"Pembahasan soal limit\"*).",
                    'suggestions' => [
                        'Bahas soal nomor 1',
                        'Pembahasan soal fungsi kuadrat',
                        'Pembahasan soal limit',
                        'Bahas soal integral'
                    ],
                    'action' => null
                ];
            }
        }

        // 5. Role Scoped Query: Jadwal & Nilai Siswa
        if ($role === 'student' && preg_match('/(jadwal|ujian saya|nilai saya|hasil ujian|kapan ujian|daftar ujian|status ujian|ujian yang tersedia|skor saya|riwayat ujian|lulus|kelulusan)/i', $normalized)) {
            $exams = $scopedContext['details']['exams'] ?? [];
            if (empty($exams)) {
                return [
                    'status' => 'success',
                    'source' => 'local',
                    'reply' => "📋 **Informasi Ujian Anda**\n\nSaat ini belum ada jadwal ujian aktif yang ditugaskan ke kelas Anda. Silakan koordinasi dengan guru mata pelajaran Anda.",
                    'suggestions' => ['Bagaimana alur pengerjaan ujian?', 'Solusi kamera tidak muncul'],
                    'action' => ['label' => 'Buka Menu Ujian Saya', 'url' => BASE_URL . '/student/exams', 'icon' => 'bi-journal-check']
                ];
            }

            $listText = "📋 **Ringkasan Jadwal & Hasil Ujian Anda (Scoped)**:\n\n";
            foreach ($exams as $ex) {
                $statusBadge = match($ex['participant_status']) {
                    'submitted' => '✅ Selesai dikerjakan',
                    'in_progress' => '⏳ Sedang berlangsung',
                    default => '📅 Siap dikerjakan'
                };
                $scoreText = ($ex['participant_status'] === 'submitted' && (int)$ex['show_result'] === 1 && $ex['final_score'] !== null)
                    ? " | Skor: **{$ex['final_score']}** (" . ($ex['is_passed'] ? 'Lulus' : 'Belum Lulus') . ")"
                    : "";

                $listText .= "- **{$ex['title']}** ({$ex['subject_name']})\n  Status: {$statusBadge}{$scoreText}\n";
            }

            return [
                'status' => 'success',
                'source' => 'local',
                'reply' => $listText,
                'suggestions' => ['Aturan AI Proctoring', 'Bagaimana jika koneksi terputus?'],
                'action' => ['label' => 'Lihat Halaman Ujian', 'url' => BASE_URL . '/student/exams', 'icon' => 'bi-journal-text']
            ];
        }

        // 6. Role Scoped Query: Guru (Statistik Ujian & Monitoring)
        if ($role === 'teacher' && preg_match('/(ujian saya|status ujian saya|monitoring saya|rekapitulasi ujian saya|ujian yang saya ampu)/iu', $normalized)) {
            $examStats = $scopedContext['details']['exam_stats'] ?? [];
            if (empty($examStats)) {
                return [
                    'status' => 'success',
                    'source' => 'local',
                    'reply' => "👨‍🏫 **Dashboard Guru**: Anda belum membuat sesi ujian. Anda dapat mulai dengan menambahkan Bank Soal atau membuat Jadwal Ujian baru.",
                    'suggestions' => ['Panduan membuat bank soal', 'Cara pantau Live Monitoring'],
                    'action' => ['label' => 'Buat Ujian Baru', 'url' => BASE_URL . '/teacher/examCreate', 'icon' => 'bi-plus-circle']
                ];
            }

            $report = "📊 **Rekapitulasi Ujian yang Anda Ampu**:\n\n";
            foreach ($examStats as $es) {
                $report .= "- **{$es['title']}** ({$es['subject_name']})\n  Status: **{$es['status']}** | Terkumpul: **{$es['total_submitted']}** dari {$es['total_assigned']} peserta\n";
            }

            return [
                'status' => 'success',
                'source' => 'local',
                'reply' => $report,
                'suggestions' => ['Panduan Live Monitoring', 'Cara koreksi soal essay'],
                'action' => ['label' => 'Buka Live Monitoring', 'url' => BASE_URL . '/teacher/monitoring', 'icon' => 'bi-camera-video']
            ];
        }

        // 6.1 Role Scoped Query: Guru (Bank Soal yang Dikelola Guru)
        if ($role === 'teacher' && preg_match('/(bank soal|soal saya|daftar soal|soal yang saya buat|koleksi soal|berapa soal)/i', $normalized)) {
            $bankQuestions = $scopedContext['details']['bank_questions'] ?? [];
            if (empty($bankQuestions) && isset($user['id'])) {
                $db = Database::getInstance();
                $tRow = $db->query("SELECT id FROM teachers WHERE user_id = :uid LIMIT 1")->bind(':uid', $user['id'])->single();
                if ($tRow) {
                    $bankQuestions = $db->query("
                        SELECT q.id, q.question_text, q.question_type, q.difficulty, q.explanation, s.name AS subject_name
                        FROM questions q
                        JOIN subjects s ON q.subject_id = s.id
                        WHERE q.teacher_id = :tid
                        ORDER BY q.id ASC
                        LIMIT 10
                    ")->bind(':tid', $tRow['id'])->resultSet();
                }
            }

            if (empty($bankQuestions)) {
                return [
                    'status' => 'success',
                    'source' => 'local',
                    'reply' => "👨‍🏫 **Bank Soal Anda**\n\nAnda belum memiliki butir soal yang tersimpan di Bank Soal. Anda dapat mulai menambahkan butir soal pilihan ganda atau essay melalui menu Bank Soal.",
                    'suggestions' => ['Panduan membuat bank soal', 'Format soal yang didukung'],
                    'action' => ['label' => 'Tambah Soal Baru', 'url' => BASE_URL . '/teacher/questionCreate', 'icon' => 'bi-plus-circle']
                ];
            }

            $totalCount = count($bankQuestions);
            $lines = ["📚 **Daftar Bank Soal yang Anda Kelola (Database Terbaru)**:\nTotal butir soal terdaftar: **{$totalCount} butir**\n"];
            foreach ($bankQuestions as $bq) {
                $cleanQ = mb_substr(strip_tags($bq['question_text']), 0, 70);
                $type = ($bq['question_type'] ?? '') === 'multiple_choice' ? 'PG' : 'Essay';
                $subj = $bq['subject_name'] ?? 'Mata Pelajaran';
                $lines[] = "- **[ID {$bq['id']}]** ({$subj} | *{$type}*): \"{$cleanQ}...\"\n  *Pembahasan DB*: {$bq['explanation']}";
            }
            $lines[] = "\n*(Ketik misalnya \"Bahas soal nomor {$bankQuestions[0]['id']}\" untuk melihat pembahasan lengkap)*";

            return [
                'status' => 'success',
                'source' => 'local',
                'reply' => implode("\n", $lines),
                'suggestions' => ['Rekapitulasi ujian yang saya buat', 'Panduan membuat bank soal', 'Buka Live Monitoring'],
                'action' => ['label' => 'Kelola Bank Soal', 'url' => BASE_URL . '/teacher/questions', 'icon' => 'bi-folder-check']
            ];
        }

        // 6.2 Role Scoped Query: Super Admin (Ringkasan Data Master & Monitoring Server)
        if ($role === 'admin' && preg_match('/(ringkasan data|master data|ringkasan master|status server|rekapitulasi data|data master|overview platform|ringkasan.*pengguna.*kelas|pengguna terdaftar|statistik sistem)/i', $normalized)) {
            $db = Database::getInstance();
            $studentCount = 0; $teacherCount = 0; $adminCount = 0; $examCount = 0; $classCount = 0; $questionCount = 0;
            if ($db->isConnected()) {
                $studentCount = $db->query("SELECT COUNT(*) AS total FROM users WHERE role = 'student'")->single()['total'] ?? 0;
                $teacherCount = $db->query("SELECT COUNT(*) AS total FROM users WHERE role = 'teacher'")->single()['total'] ?? 0;
                $adminCount = $db->query("SELECT COUNT(*) AS total FROM users WHERE role = 'admin'")->single()['total'] ?? 0;
                $examCount = $db->query("SELECT COUNT(*) AS total FROM exams")->single()['total'] ?? 0;
                $classCount = $db->query("SELECT COUNT(*) AS total FROM classes")->single()['total'] ?? 0;
                $questionCount = $db->query("SELECT COUNT(*) AS total FROM questions")->single()['total'] ?? 0;
            }

            $totalUsers = $studentCount + $teacherCount + $adminCount;
            $reply = "🛡️ **Ringkasan Master Data (Database Terbaru)**:\n" .
                "- **Pengguna Terdaftar**: **{$totalUsers} Total** ({$studentCount} Siswa, {$teacherCount} Guru, {$adminCount} Admin)\n" .
                "- **Rombongan Belajar**: **{$classCount} Kelas** | Bank Soal: **{$questionCount} Butir** | Ujian: **{$examCount} Sesi**\n" .
                "- **Status AI Proctoring**: Berjalan optimal.";

            return [
                'status' => 'success',
                'source' => 'local',
                'reply' => $reply,
                'suggestions' => ['Panduan manajemen akun guru dan siswa', 'Status server & integritas sistem', 'Daftar mata pelajaran'],
                'action' => ['label' => 'Buka Dashboard Admin', 'url' => BASE_URL . '/admin/dashboard', 'icon' => 'bi-speedometer2']
            ];
        }

        // 7. Guest asking for personal exams
        if ($role === 'guest' && preg_match('/(nilai saya|jadwal saya|ujian saya|hasil saya|skor saya|evaluasi saya)/i', $normalized)) {
            return [
                'status' => 'success',
                'source' => 'local',
                'reply' => "🔒 **Akses Terbatas**: Anda sedang mengakses sistem sebagai pengunjung (belum login).\n\nUntuk melihat jadwal ujian, status pengerjaan, atau nilai pribadi Anda, silakan masuk ke akun Siswa Anda terlebih dahulu.",
                'suggestions' => ['Akun demo untuk uji coba', 'Apa itu AI Proctoring?'],
                'action' => ['label' => 'Login Siswa', 'url' => BASE_URL . '/auth/login', 'icon' => 'bi-box-arrow-in-right']
            ];
        }

        // 8. Troubleshooting Kamera & Izin Perangkat
        if (preg_match('/(izin kamera|kamera ditolak|kamera tidak muncul|kamera error|kamera mati|kamera.*izin|izin.*kamera|webcam.*tidak)/i', $normalized)) {
            return [
                'status' => 'success',
                'source' => 'local',
                'reply' => "📷 **Solusi Jika Kamera Tidak Muncul / Izin Ditolak**:\n\n1. Klik ikon **Gembok (Tune / Site Settings)** di samping URL address bar browser Anda.\n2. Pastikan izin Kamera diatur ke **Izinkan (Allow)**.\n3. Muat ulang halaman (tekan F5 atau Ctrl+R).\n4. Tutup aplikasi lain (Zoom, Teams, Meet) yang mungkin sedang memakai kamera.",
                'suggestions' => ['Bagaimana alur pengerjaan siswa?', 'Apa itu AI Proctoring?'],
                'action' => null
            ];
        }

        // 9. AI Proctoring & Anti-Curang
        if (preg_match('/(proctoring|curang|anti curang|kamera|deteksi|integritas|trust index|risk score|wajah)/i', $normalized)) {
            return [
                'status' => 'success',
                'source' => 'local',
                'reply' => "🛡️ **Fitur Keamanan AI Proctoring**:\n\n1. **Deteksi Wajah (Face Tracking)**: Mendeteksi apakah peserta tetap berada di depan kamera, berpaling (menoleh berlebihan), atau terdapat lebih dari 1 wajah.\n2. **Deteksi Tab & Layar Penuh (Tab & Window Monitor)**: Mencatat bila peserta membuka tab lain atau meminimalkan jendela ujian.\n3. **Anti Copy-Paste**: Membatasi penyalinan soal ke luar sistem.\n4. **Risk Score & Trust Index**: Menghitung skor integritas untuk verifikasi objektif oleh guru.",
                'suggestions' => ['Bagaimana alur pengerjaan siswa?', 'Bagaimana jika kamera error?'],
                'action' => null
            ];
        }

        // 10. Alur Pengerjaan Siswa
        if (preg_match('/(alur siswa|cara ujian|mengerjakan ujian|alur pengerjaan|autosave|ragu)/i', $normalized)) {
            return [
                'status' => 'success',
                'source' => 'local',
                'reply' => "📝 **Alur Siswa Saat Mengerjakan Ujian**:\n\n1. **Login & Pilih Ujian**: Masuk ke dashboard dan klik Mulai Ujian.\n2. **System Check**: Verifikasi izin kamera dan kesiapan browser.\n3. **Ruang Ujian**: Soal tampil interaktif, dilengkapi fitur **Auto-save** tiap jawaban, timer hitung mundur, dan penanda ragu-ragu.\n4. **Kumpulkan Ujian**: Konfirmasi pengumpulan atau sistem otomatis mengumpulkan saat waktu habis.",
                'suggestions' => ['Apa itu AI Proctoring?', 'Bagaimana jika koneksi terputus?'],
                'action' => null
            ];
        }

        // 11. Panduan Guru (Bank Soal & Live Monitoring)
        if (preg_match('/(panduan guru|buat bank soal|panduan.*bank soal|kelola bank soal|manajemen bank soal|cara.*bank soal|tambah bank soal|live monitoring|koreksi essay)/i', $normalized)) {
            return [
                'status' => 'success',
                'source' => 'local',
                'reply' => "👨‍🏫 **Panduan Guru & Penguji**:\n\n1. **Bank Soal**: Tambahkan soal pilihan ganda (A-E) atau essay dengan kunci jawaban & pembahasan otomatis.\n2. **Jadwal Ujian**: Tentukan waktu mulai, durasi, passing grade (KKM), dan opsi acak soal.\n3. **Live Monitoring**: Pantau status pengerjaan seluruh peserta secara real-time disertai snapshot peringatan bila ada indikasi mencurigakan.\n4. **Penilaian Hasil**: Tinjau hasil pengerjaan, nilai soal essay, dan terbitkan hasil.",
                'suggestions' => ['Format soal yang didukung', 'Akun demo untuk uji coba'],
                'action' => null
            ];
        }

        // 12. Akun Demo
        if (preg_match('/(demo|akun demo|coba login|password demo|akun uji coba)/i', $normalized)) {
            return [
                'status' => 'success',
                'source' => 'local',
                'reply' => "🔑 **Akun Demo untuk Uji Coba Sistem**\n*(Semua password: `password123`)*:\n\n- **Super Admin**: `admin@sekolah.sch.id`\n- **Guru**: `guru_budi` (atau `budi.santoso@sekolah.sch.id`)\n- **Siswa 1**: `siswa_ani` (atau `ani.lestari@sekolah.sch.id`)\n- **Siswa 2**: `siswa_bimo`\n- **Siswa 3**: `siswa_citra`",
                'suggestions' => ['Bagaimana cara siswa ujian?', 'Apa itu AI Proctoring?'],
                'action' => [
                    'label' => 'Buka Halaman Login Demo',
                    'url' => BASE_URL . '/auth/login',
                    'icon' => 'bi-box-arrow-in-right'
                ]
            ];
        }

        // 13. Troubleshooting Jaringan Terputus
        if (preg_match('/(koneksi|internet|putus|offline|jaringan lambat)/i', $normalized)) {
            return [
                'status' => 'success',
                'source' => 'local',
                'reply' => "🌐 **Keamanan Jawaban Saat Internet Terputus**:\n\n- Sistem memiliki mekanisme penyimpanan lokal dan auto-save bertahap.\n- Jika koneksi terputus di tengah ujian, jawaban yang sudah Anda klik tetap aman.\n- Segera sambungkan kembali internet Anda, lalu refresh halaman ujian.",
                'suggestions' => ['Solusi kamera tidak muncul', 'Aturan pengerjaan ujian'],
                'action' => null
            ];
        }

        // 14. Fallback: Filter out-of-scope questions outside the exam system
        if (!self::isExamOrSystemRelevant($normalized)) {
            return [
                'status' => 'success',
                'source' => 'guardrail',
                'reply' => "⚠️ **Pertanyaan di Luar Ruang Lingkup Sistem Ujian**\n\nMaaf, pertanyaan tersebut berada di luar sistem ujian ini. ExamBot berfokus membantu pembahasan butir soal database, informasi jadwal/nilai ujian, panduan kamera AI Proctoring, serta kendala teknis ujian.\n\nSilakan pilih salah satu topik bantuan ujian berikut:",
                'suggestions' => self::getSuggestionsForRole($role),
                'action' => self::getDefaultActionForRole($role)
            ];
        }

        return [
            'status' => 'success',
            'source' => 'local',
            'reply' => "Saya memahami pertanyaan Anda seputar topik ujian: \"{$rawMessage}\".\n\nUntuk mendapatkan informasi lebih detail mengenai fitur ujian online, silakan pilih salah satu topik panduan berikut:",
            'suggestions' => self::getContextualSuggestions($rawMessage, $role),
            'action' => self::detectAction($rawMessage, $role)
        ];
    }

    /**
     * Local Pedagogical Fallback Explanation for Questions (Chatbot Tipe 2: Penjelasan Tiap Soal)
     * Fully aligned with database question, options, correct answer, and explanation.
     *
     * @param array $q
     * @param string $message
     * @return array
     */
    public static function replyWithLocalQuestionExplainer(array $q, string $message): array
    {
        $qText = $q['question_text'] ?? '';
        $studentAns = $q['student_answer_label'] ?? $q['student_answer'] ?? '(Belum dijawab)';
        $correctAns = $q['correct_answer_label'] ?? $q['correct_answer'] ?? '';
        $explanation = !empty($q['explanation']) ? trim($q['explanation']) : 'Kunci jawaban ini didasarkan pada konsep keilmuan kurikulum yang tersimpan di basis data.';
        $isCorrect = !empty($q['is_correct']);
        $normalizedMsg = mb_strtolower(trim($message), 'UTF-8');

        // 1. Student asking for tips
        if (preg_match('/(tips|trik|strategi|saran|pesan)/i', $normalizedMsg)) {
            $reply = "### 💡 Tips Menjawab Soal Serupa (Sesuai Database)\n\n" .
                "1. **Rujukan Utama**: Cermati pembahasan database: *\"{$explanation}\"*.\n" .
                "2. **Ketelitian Aljabar**: Periksa tanda positif/negatif saat substitusi nilai atau pemfaktoran.\n" .
                "3. **Eliminasi & Verifikasi**: Singkirkan opsi yang tidak rasional dan lakukan verifikasi balik ke persamaan awal.";
        }
        // 2. Student asking why their answer is wrong
        elseif (preg_match('/(mengapa|kenapa|alasan|penyebab|salah|keliru|salahkah)/i', $normalizedMsg) && !$isCorrect) {
            $reply = "### 💡 Evaluasi Jawaban Siswa (Berdasarkan Database)\n\n" .
                "- **Pilihan Jawaban Anda**: **{$studentAns}** ❌ *(Belum tepat)*\n" .
                "- **Kunci Jawaban Resmi Database**: **{$correctAns}** ✅\n\n" .
                "#### 🔍 Mengapa Pilihan Anda Kurang Tepat?\n" .
                "Pilihan **{$studentAns}** belum sesuai dengan kaidah perhitungan konsep materi butir soal ini.\n\n" .
                "#### 📖 Rujukan Pembahasan Resmi Database Guru:\n" .
                "> **\"{$explanation}\"**\n\n" .
                "#### 📌 Kesimpulan Perbaikan:\n" .
                "Berdasarkan kaidah pada pembahasan resmi di atas, jawaban yang benar adalah **{$correctAns}**.";
        }
        // 3. Student asking for step-by-step resolution
        elseif (preg_match('/(langkah|cara|metode|step|tahap|penyelesaian|mengerjakan)/i', $normalizedMsg)) {
            $breakdown = self::generateConceptualBreakdown($qText, $explanation, $correctAns);
            $reply = "### 📝 Langkah Sistematis Penyelesaian Soal (Sesuai Database)\n\n" .
                "🎯 **Kunci Jawaban Resmi**: **{$correctAns}**\n\n" .
                "#### 📖 Pembahasan Resmi Database Guru:\n" .
                "> **\"{$explanation}\"**\n\n" .
                "#### 📌 Rincian Langkah Kerja:\n" .
                "{$breakdown}";
        }
        // 4. Student asking for core formula or theory
        elseif (preg_match('/(rumus|formula|teorema|konsep|teori|materi)/i', $normalizedMsg)) {
            $reply = "### 📐 Konsep & Rumus Kunci (Sesuai Database)\n\n" .
                "- **Kunci Jawaban Resmi**: **{$correctAns}**\n" .
                "- **Rujukan Formula Database**:\n" .
                "> **\"{$explanation}\"**\n\n" .
                "Pahami kaidah rumus di atas untuk menyelesaikan soal serupa dengan akurat.";
        }
        // 5. Default / Initial Explanation
        else {
            $evaluation = $isCorrect
                ? "🎉 **Jawaban Anda Tepat!** (Pilihan: **{$studentAns}**)"
                : "💡 **Evaluasi Jawaban**: Anda memilih **{$studentAns}**, sedangkan kunci resmi adalah **{$correctAns}**.";

            $breakdown = self::generateConceptualBreakdown($qText, $explanation, $correctAns);

            $reply = "### 📚 Pembahasan Soal Akademik (Berdasarkan Database)\n\n" .
                "{$evaluation}\n\n" .
                "#### 🎯 Kunci Jawaban Resmi Database:\n" .
                "**{$correctAns}**\n\n" .
                "#### 🔍 Pembahasan Resmi Database Guru:\n" .
                "> \"{$explanation}\"\n\n" .
                "#### 📌 Langkah Pembahasan:\n" .
                "{$breakdown}";
        }

        return [
            'status' => 'success',
            'source' => 'local_tutor',
            'reply' => $reply,
            'suggestions' => [
                'Bisa jelaskan langkah penyelesaiannya lebih detail?',
                'Apa rumus atau konsep materi kunci soal ini?',
                'Tips agar tidak keliru pada soal serupa'
            ]
        ];
    }

    /**
     * Find questions in database by ID, topic, keyword, or student exam context
     *
     * @param string $query
     * @param array|null $user
     * @return array
     */
    public static function findQuestionsInDatabase(string $query, ?array $user = null): array
    {
        $db = Database::getInstance();
        if (!$db->isConnected()) {
            return [];
        }

        $raw = trim($query);
        $normalized = mb_strtolower($raw, 'UTF-8');
        $role = $user['role'] ?? 'guest';

        // 1. Check if student asks for their submitted exam review
        if ($role === 'student' && preg_match('/(ujian saya|hasil saya|ujian yang lalu|ujian matematika saya|evaluasi ujian|soal.*salah|soal yang salah|pembahasan ujian|bahas ujian)/i', $normalized)) {
            $studentId = $user['student_id'] ?? null;
            if (!$studentId && isset($user['id'])) {
                $stRow = $db->query("SELECT id FROM students WHERE user_id = :uid LIMIT 1")->bind(':uid', $user['id'])->single();
                $studentId = $stRow['id'] ?? null;
            }

            if ($studentId) {
                $examRows = $db->query("
                    SELECT ep.id AS participant_id, ep.exam_id, e.title, sub.name AS subject_name, er.final_score
                    FROM exam_participants ep
                    JOIN exams e ON ep.exam_id = e.id
                    JOIN subjects sub ON e.subject_id = sub.id
                    LEFT JOIN exam_results er ON ep.id = er.exam_participant_id
                    WHERE ep.student_id = :sid AND ep.status = 'submitted' AND e.show_result = 1
                    ORDER BY ep.submit_time DESC
                    LIMIT 1
                ")->bind(':sid', $studentId)->resultSet();

                if (!empty($examRows)) {
                    $exam = $examRows[0];
                    $qList = $db->query("
                        SELECT eq.question_order, q.id, q.question_text, q.question_type, q.difficulty, q.explanation,
                               sub.name AS subject_name, ea.selected_option_id, ea.essay_answer, ea.is_correct
                        FROM exam_questions eq
                        JOIN questions q ON eq.question_id = q.id
                        JOIN subjects sub ON q.subject_id = sub.id
                        LEFT JOIN exam_answers ea ON eq.question_id = ea.question_id AND ea.exam_participant_id = :pid
                        WHERE eq.exam_id = :eid
                        ORDER BY eq.question_order ASC
                    ")->bind(':pid', $exam['participant_id'])->bind(':eid', $exam['exam_id'])->resultSet();

                    $formattedQuestions = [];
                    foreach ($qList as $q) {
                        $options = [];
                        $correctLabel = '-';
                        $studentLabel = '(Tidak dijawab)';
                        if ($q['question_type'] === 'multiple_choice') {
                            $opts = $db->query("SELECT id, option_label, option_text, is_correct FROM question_options WHERE question_id = :qid ORDER BY option_label ASC")->bind(':qid', $q['id'])->resultSet();
                            foreach ($opts as $opt) {
                                $options[] = $opt;
                                if ((int)$opt['is_correct'] === 1) {
                                    $correctLabel = $opt['option_label'] . '. ' . $opt['option_text'];
                                }
                                if ((int)$opt['id'] === (int)($q['selected_option_id'] ?? 0)) {
                                    $studentLabel = $opt['option_label'] . '. ' . $opt['option_text'];
                                }
                            }
                        } else {
                            $correctLabel = 'Jawaban Terbuka (Rubrik Penilaian Guru)';
                            $studentLabel = $q['essay_answer'] ?: '(Kosong)';
                        }

                        $formattedQuestions[] = [
                            'id' => $q['id'],
                            'question_order' => $q['question_order'],
                            'question_text' => $q['question_text'],
                            'question_type' => $q['question_type'],
                            'difficulty' => $q['difficulty'],
                            'explanation' => $q['explanation'],
                            'subject_name' => $q['subject_name'],
                            'options' => $options,
                            'correct_answer_label' => $correctLabel,
                            'student_answer_label' => $studentLabel,
                            'is_correct' => ($q['is_correct'] == 1),
                            'exam_title' => $exam['title']
                        ];
                    }
                    return $formattedQuestions;
                }
            }
        }

        // 2. Specific question ID check
        $targetId = null;
        if (preg_match('/(?:soal\s+(?:nomor|no|id|#)?\s*([0-9]+)|(?:nomor|no|#)\s*([0-9]+))/i', $normalized, $m)) {
            $targetId = (int)(!empty($m[1]) ? $m[1] : $m[2]);
        }

        if ($targetId !== null) {
            $singleQ = $db->query("
                SELECT q.id, q.question_text, q.question_type, q.difficulty, q.explanation, s.name AS subject_name
                FROM questions q
                JOIN subjects s ON q.subject_id = s.id
                WHERE q.id = :qid
                LIMIT 1
            ")->bind(':qid', $targetId)->single();

            if ($singleQ) {
                $options = [];
                $correctLabel = '-';
                if ($singleQ['question_type'] === 'multiple_choice') {
                    $opts = $db->query("SELECT id, option_label, option_text, is_correct FROM question_options WHERE question_id = :qid ORDER BY option_label ASC")->bind(':qid', $singleQ['id'])->resultSet();
                    foreach ($opts as $opt) {
                        $options[] = $opt;
                        if ((int)$opt['is_correct'] === 1) {
                            $correctLabel = $opt['option_label'] . '. ' . $opt['option_text'];
                        }
                    }
                } else {
                    $correctLabel = 'Jawaban Uraian / Rubrik Guru';
                }

                $studentAnswerLabel = null;
                $isCorrectStudent = null;
                if ($role === 'student' && isset($user['id'])) {
                    $stRow = $db->query("SELECT id FROM students WHERE user_id = :uid LIMIT 1")->bind(':uid', $user['id'])->single();
                    if ($stRow) {
                        $ansRow = $db->query("
                            SELECT ea.selected_option_id, ea.essay_answer, ea.is_correct
                            FROM exam_answers ea
                            JOIN exam_participants ep ON ea.exam_participant_id = ep.id
                            JOIN exams e ON ep.exam_id = e.id
                            WHERE ea.question_id = :qid AND ep.student_id = :sid AND ep.status = 'submitted' AND e.show_result = 1
                            ORDER BY ep.submit_time DESC
                            LIMIT 1
                        ")->bind(':qid', $singleQ['id'])->bind(':sid', $stRow['id'])->single();
                        if ($ansRow) {
                            $isCorrectStudent = ((int)$ansRow['is_correct'] === 1);
                            if ($singleQ['question_type'] === 'multiple_choice') {
                                foreach ($options as $opt) {
                                    if ((int)$opt['id'] === (int)$ansRow['selected_option_id']) {
                                        $studentAnswerLabel = $opt['option_label'] . '. ' . $opt['option_text'];
                                        break;
                                    }
                                }
                            } else {
                                $studentAnswerLabel = $ansRow['essay_answer'] ?: '(Kosong)';
                            }
                        }
                    }
                }

                $item = [
                    'id' => $singleQ['id'],
                    'question_text' => $singleQ['question_text'],
                    'question_type' => $singleQ['question_type'],
                    'difficulty' => $singleQ['difficulty'],
                    'explanation' => $singleQ['explanation'],
                    'subject_name' => $singleQ['subject_name'],
                    'options' => $options,
                    'correct_answer_label' => $correctLabel
                ];
                if ($studentAnswerLabel !== null) {
                    $item['student_answer_label'] = $studentAnswerLabel;
                    $item['is_correct'] = $isCorrectStudent;
                }

                return [$item];
            }
        }

        // 3. Keyword / Concept matching
        $matched = [];
        $terms = ['fungsi kuadrat', 'titik balik', 'minimum', 'limit', 'turunan', 'interval', 'dadu', 'peluang', 'integral', 'teorema limit pusat', 'bcrypt', 'enkripsi', '25 x 4', '25 * 4', '100'];
        $searchKey = '';
        foreach ($terms as $t) {
            if (stripos($normalized, $t) !== false) {
                $searchKey = $t;
                break;
            }
        }

        // Clean query words if no exact term found
        if ($searchKey === '') {
            $clean = preg_replace('/(apakah|apa|pembahasan|bahas|soal|nomor|no|kunci|jawaban|dari|tentang|bagaimana|dan|tolong|tampilkan|kisi|cara)/iu', '', $normalized);
            $clean = trim(preg_replace('/[^a-zA-Z0-9\s]/u', ' ', $clean));
            if (mb_strlen($clean) >= 3) {
                $searchKey = $clean;
            }
        }

        $whereClause = "WHERE 1=1";
        $params = [];
        if ($searchKey !== '') {
            $whereClause .= " AND (q.question_text LIKE :kw1 OR q.explanation LIKE :kw2 OR s.name LIKE :kw3)";
            $params[':kw1'] = '%' . $searchKey . '%';
            $params[':kw2'] = '%' . $searchKey . '%';
            $params[':kw3'] = '%' . $searchKey . '%';
        }

        $rows = $db->query("
            SELECT q.id, q.question_text, q.question_type, q.difficulty, q.explanation, s.name AS subject_name
            FROM questions q
            JOIN subjects s ON q.subject_id = s.id
            {$whereClause}
            ORDER BY q.id ASC
            LIMIT 6
        ");
        foreach ($params as $k => $v) {
            $rows->bind($k, $v);
        }
        $resList = $rows->resultSet();

        foreach ($resList as $r) {
            $options = [];
            $correctLabel = '-';
            if ($r['question_type'] === 'multiple_choice') {
                $opts = $db->query("SELECT id, option_label, option_text, is_correct FROM question_options WHERE question_id = :qid ORDER BY option_label ASC")->bind(':qid', $r['id'])->resultSet();
                foreach ($opts as $opt) {
                    $options[] = $opt;
                    if ((int)$opt['is_correct'] === 1) {
                        $correctLabel = $opt['option_label'] . '. ' . $opt['option_text'];
                    }
                }
            } else {
                $correctLabel = 'Jawaban Terbuka (Rubrik Penilaian Guru)';
            }

            $matched[] = [
                'id' => $r['id'],
                'question_text' => $r['question_text'],
                'question_type' => $r['question_type'],
                'difficulty' => $r['difficulty'],
                'explanation' => $r['explanation'],
                'subject_name' => $r['subject_name'],
                'options' => $options,
                'correct_answer_label' => $correctLabel
            ];
        }

        return $matched;
    }

    /**
     * Get dynamic list of available exam questions directly from database
     *
     * @return string
     */
    public static function getAvailableDatabaseQuestionsList(): string
    {
        $db = Database::getInstance();
        if (!$db->isConnected()) {
            return "- **Fungsi Kuadrat** (Titik balik minimum kurva \$f(x) = x^2 - 6x + 8\$)\n" .
                   "- **Limit Fungsi Aljabar** (Faktorisasi limit \$\\frac{x^2 - 4}{x - 2}\$ mendekati 2)\n" .
                   "- **Turunan Fungsi Aljabar** (Interval fungsi naik \$f(x) = 2x^3 - 9x^2 + 12x + 5\$)\n" .
                   "- **Teori Peluang** (Peluang pelemparan dadu prima ganjil)\n" .
                   "- **Integral Tentu** (Evaluasi integral \$\\int (3x^2 + 2x - 1) dx\$ dari 0 sampai 2)\n" .
                   "- **Aritmatika Dasar** (Perkalian 25 x 4)\n" .
                   "- **Keamanan Siber** (Enkripsi satu arah Bcrypt)";
        }

        $rows = $db->query("
            SELECT q.id, q.question_text, s.name AS subject_name, q.explanation
            FROM questions q
            JOIN subjects s ON q.subject_id = s.id
            ORDER BY q.id ASC
            LIMIT 10
        ")->resultSet();

        if (empty($rows)) {
            return "Saat ini belum ada butir soal yang diunggah ke basis data sistem.";
        }

        $lines = [];
        foreach ($rows as $r) {
            $cleanText = trim(preg_replace('/\s+/', ' ', strip_tags($r['question_text'])));
            if (mb_strlen($cleanText) > 75) {
                $cleanText = mb_substr($cleanText, 0, 72) . '...';
            }
            $lines[] = "- **Soal #{$r['id']} ({$r['subject_name']})**: \"{$cleanText}\"";
        }

        return implode("\n", $lines);
    }

    /**
     * Check if a message has genuine relevance to the exam system, academics, proctoring, or technical setup
     *
     * @param string $normalized
     * @return bool
     */
    public static function isExamOrSystemRelevant(string $normalized): bool
    {
        $relevantPatterns = [
            '/\b(ujian|soal|exam|test|quiz|ulangan|tryout|materi|tugas|koreksi|nilai|skor|hasil|kkm|kelulusan|jadwal|waktu|durasi|passing grade)\b/i',
            '/\b(kamera|webcam|izin|browser|peramban|offline|koneksi|internet|lag|error|kendala|gangguan|refresh|reload|submit|kumpulkan|simpan|autosave|ragu)\b/i',
            '/\b(proctoring|pengawasan|curang|kecurangan|tab|jendela|fullscreen|layar|wajah|face|trust|risk|integritas)\b/i',
            '/\b(siswa|murid|peserta|kelas|rombel|nis|nisn|guru|pengajar|pendidik|nip|pengawas|admin|administrator|akun|login|masuk|password|demo)\b/i',
            '/\b(mata pelajaran|mapel|subject|kurikulum|sekolah|akademik|jenjang|tahun ajaran)\b/i',
            '/\b(matematika|aljabar|kuadrat|limit|turunan|integral|dadu|peluang|prima|statistik|bcrypt|enkripsi|perkalian|25 x 4|titik balik|titik puncak|biologi|fisika|kimia|sejarah|geografi|ekonomi|sosiologi|bahasa|inggris|indonesia|ipa|ips|pkn|agama|seni|komputer|informatika|pemrograman|algoritma)\b/i',
            '/\b(sistem|aplikasi|platform|web|fitur|menu|panduan|bantuan|tata tertib|aturan|cara|alur|fungsi)\b/i',
            '/\b(siapa|siapakah|identitas|tentang kamu|tentang anda|profil|exambot|bot|asisten|pembuat|namamu|nama anda|kamu siapa|anda siapa)\b/i',
            '/\b(halo|hai|hi|hello|selamat|assalamualaikum|pagi|siang|sore|malam|tes|test|cek|ping|terima kasih|makasih|thanks)\b/i'
        ];

        foreach ($relevantPatterns as $pattern) {
            if (preg_match($pattern, $normalized)) {
                return true;
            }
        }

        // Dynamic check against registered subjects in database
        try {
            $db = Database::getInstance();
            if ($db->isConnected()) {
                $subRows = $db->query("SELECT name FROM subjects")->resultSet();
                foreach ($subRows as $sr) {
                    $sName = mb_strtolower(trim($sr['name']), 'UTF-8');
                    if ($sName !== '' && stripos($normalized, $sName) !== false) {
                        return true;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignore DB errors during relevance check
        }

        return false;
    }

    /**
     * Format single question discussion based strictly on database records
     *
     * @param array $q
     * @param string|null $userMessage
     * @return string
     */
    public static function formatDatabaseQuestionDiscussion(array $q, ?string $userMessage = null): string
    {
        $subject = htmlspecialchars($q['subject_name'] ?? 'Mata Pelajaran');
        $qTypeLabel = ($q['question_type'] ?? 'multiple_choice') === 'multiple_choice' ? 'Pilihan Ganda' : 'Essay / Uraian';
        $difficulty = ucfirst($q['difficulty'] ?? 'Medium');
        $qText = $q['question_text'] ?? '';
        $explanation = !empty($q['explanation']) ? trim($q['explanation']) : 'Kunci jawaban ini didasarkan pada konsep kurikulum resmi yang tersimpan di basis data.';
        $correctKey = $q['correct_answer_label'] ?? '-';
        $hasStudentAns = isset($q['student_answer_label']);

        $optionsBlock = '';
        if (!empty($q['options']) && is_array($q['options'])) {
            $optionsBlock .= "#### 🔠 Pilihan Jawaban:\n";
            foreach ($q['options'] as $opt) {
                $lbl = $opt['option_label'];
                $txt = $opt['option_text'];
                $isCorrect = ((int)($opt['is_correct'] ?? 0) === 1);
                if ($isCorrect) {
                    $optionsBlock .= "- **{$lbl}. {$txt}** ✅ *(Kunci Jawaban Benar)*\n";
                } else {
                    $optionsBlock .= "- {$lbl}. {$txt}\n";
                }
            }
            $optionsBlock .= "\n";
        }

        $studentEvaluationBlock = '';
        if ($hasStudentAns) {
            $isCorrect = !empty($q['is_correct']);
            $studentAns = $q['student_answer_label'];
            $badge = $isCorrect
                ? "🎉 **Jawaban Anda Tepat!**\nAnda memilih **{$studentAns}**, yang merupakan opsi yang benar."
                : "💡 **Evaluasi Jawaban Anda**:\nAnda memilih **{$studentAns}**, sedangkan jawaban yang benar adalah **{$correctKey}**.";
            $studentEvaluationBlock = "{$badge}\n\n";
        }

        $elaboration = self::generateConceptualBreakdown($qText, $explanation, $correctKey);

        $userMsgLower = mb_strtolower(trim((string)$userMessage), 'UTF-8');
        $isSpecificKeyQuery = (
            preg_match('/^(kunci|kunci jawaban|apa kunci|apa jawaban|jawaban)\b/i', $userMsgLower)
            && !preg_match('/(langkah|cara|rumus|detail|penjabaran|kenapa|mengapa)/i', $userMsgLower)
        );

        if ($isSpecificKeyQuery) {
            return "### 📚 Pembahasan Soal Akademik (Berdasarkan Database)\n\n" .
                "📌 **{$subject}** | *Tipe: {$qTypeLabel}*\n\n" .
                ($hasStudentAns ? $studentEvaluationBlock : "") .
                "> \"{$qText}\"\n\n" .
                "- **Kunci Jawaban Resmi**: **{$correctKey}**\n" .
                "- **Pembahasan Resmi Database Guru**:\n  > \"{$explanation}\"\n\n" .
                "*(Ketik \"Bagaimana langkah penyelesaiannya?\" untuk melihat rincian langkah kerja)*";
        }

        return "### 📚 Pembahasan Soal Akademik (Berdasarkan Database)\n\n" .
            "📌 **{$subject}** | *Tipe: {$qTypeLabel}* | *Tingkat: {$difficulty}*\n\n" .
            ($hasStudentAns ? $studentEvaluationBlock : "") .
            "#### 📝 Butir Soal:\n" .
            "> \"{$qText}\"\n\n" .
            $optionsBlock .
            "#### 🎯 Kunci Jawaban Resmi:\n" .
            "**{$correctKey}**\n\n" .
            "#### 💡 Pembahasan Resmi Database Guru:\n" .
            "> **\"{$explanation}\"**\n\n" .
            "#### 🔍 Penjabaran Langkah & Konsep:\n" .
            "{$elaboration}";
    }

    /**
     * Format multiple questions summary from database
     *
     * @param array $questions
     * @param string $title
     * @return string
     */
    public static function formatMultipleQuestionsSummary(array $questions, string $title = 'Rekapitulasi Pembahasan Soal (Database)'): string
    {
        $count = count($questions);
        $lines = ["### 📚 {$title}\n", "Ditemukan **{$count} butir soal** dengan pembahasan resmi dari database:\n"];

        foreach ($questions as $idx => $q) {
            $num = $q['question_order'] ?? ($idx + 1);
            $qText = $q['question_text'];
            $correctKey = $q['correct_answer_label'] ?? '-';
            $explanation = !empty($q['explanation']) ? trim($q['explanation']) : 'Pembahasan kurikulum resmi basis data.';
            $subject = $q['subject_name'] ?? 'Mata Pelajaran';

            $studentStatus = '';
            if (isset($q['student_answer_label'])) {
                $statusIcon = (!empty($q['is_correct'])) ? '✅ Benar' : '❌ Salah';
                $studentStatus = "\n  - **Jawaban Anda**: {$q['student_answer_label']} ({$statusIcon})";
            }

            $lines[] = "---";
            $lines[] = "#### 🔹 Soal #{$num} ({$subject})";
            $lines[] = "> \"{$qText}\"";
            if ($studentStatus) {
                $lines[] = $studentStatus;
            }
            $lines[] = "- **Kunci Jawaban Resmi**: **{$correctKey}**";
            $lines[] = "- **Pembahasan Resmi Database**:\n  > \"{$explanation}\"";
        }

        $lines[] = "\n*(ℹ️ Ingin pembahasan lebih mendalam untuk salah satu soal? Ketik misalnya: \"Bahas soal nomor 1\" atau klik tombol pertanyaan di bawah)*";

        return implode("\n", $lines);
    }

    /**
     * Generate step-by-step conceptual breakdown strictly faithful to database explanation
     *
     * @param string $qText
     * @param string $explanation
     * @param string $correctKey
     * @return string
     */
    public static function generateConceptualBreakdown(string $qText, string $explanation, string $correctKey): string
    {
        $textLower = mb_strtolower($qText . ' ' . $explanation, 'UTF-8');
        $cleanExplanation = trim($explanation);

        // Fungsi Kuadrat spesifik seed (f(x) = x² - 6x + 8 -> titik puncak (3, -1))
        if ((strpos($textLower, 'fungsi kuadrat') !== false || strpos($textLower, 'titik balik') !== false)
            && (strpos($textLower, '-6x') !== false || strpos($cleanExplanation, '6/2 = 3') !== false || strpos($cleanExplanation, '(3, -1)') !== false)) {
            return "1. **Bentuk Umum**: Fungsi kuadrat \$f(x) = ax^2 + bx + c\$ dengan \$a=1, b=-6, c=8\$.\n" .
                   "2. **Absis Titik Puncak (\$x_p\$)**: \$x_p = -b/(2a) = -(-6)/(2 \\cdot 1) = 6/2 = 3\$.\n" .
                   "3. **Ordinat Titik Puncak (\$y_p\$)**: \$y_p = f(3) = (3)^2 - 6(3) + 8 = 9 - 18 + 8 = -1\$.\n" .
                   "4. **Kesimpulan**: Titik balik minimum berada pada koordinat \$(3, -1)\$ sesuai kunci jawaban resmi database.";
        }

        // Limit x->2 untuk (x² - 4)/(x - 2) -> 4
        if (strpos($textLower, 'limit') !== false && (strpos($textLower, 'x - 2') !== false || strpos($textLower, 'x² - 4') !== false || strpos($textLower, 'x^2 - 4') !== false) && strpos($cleanExplanation, '4') !== false) {
            return "1. **Identifikasi Bentuk**: Substitusi langsung \$x = 2\$ menghasilkan bentuk tak tentu 0/0.\n" .
                   "2. **Faktorisasi Pembilang**: \$x^2 - 4 = (x - 2)(x + 2)\$.\n" .
                   "3. **Penyederhanaan**: \$\\frac{(x - 2)(x + 2)}{x - 2} = x + 2\$ untuk \$x \\neq 2\$.\n" .
                   "4. **Substitusi Nilai**: Masukkan \$x = 2\$, diperoleh \$2 + 2 = 4\$ sesuai kunci jawaban resmi database.";
        }

        // Turunan f(x) = 2x³ - 9x² + 12x + 5
        if ((strpos($textLower, 'turunan') !== false || strpos($textLower, 'interval') !== false) && (strpos($cleanExplanation, '6x² - 18x + 12') !== false || strpos($cleanExplanation, '6(x - 1)(x - 2)') !== false || strpos($cleanExplanation, 'x < 1 atau x > 2') !== false)) {
            return "1. **Syarat Fungsi Naik**: Suatu fungsi naik saat turunan pertamanya bernilai positif: \$f'(x) > 0\$.\n" .
                   "2. **Turunan Fungsi**: \$f'(x) = 6x^2 - 18x + 12\$.\n" .
                   "3. **Faktorisasi**: \$6(x^2 - 3x + 2) = 6(x - 1)(x - 2) > 0\$.\n" .
                   "4. **Uji Interval**: Nilai pembuat nol \$x = 1\$ dan \$x = 2\$. Daerah bernilai positif berada pada \$x < 1\$ atau \$x > 2\$ sesuai kunci jawaban resmi database.";
        }

        // Dadu prima ganjil {3, 5}
        if ((strpos($textLower, 'dadu') !== false || strpos($textLower, 'peluang') !== false) && (strpos($cleanExplanation, '{3, 5}') !== false || strpos($cleanExplanation, '1/3') !== false)) {
            return "1. **Ruang Sampel (\$S\$)**: Dadu 6 sisi memiliki ruang sampel \$S = \\{1, 2, 3, 4, 5, 6\\}\$, dengan \$n(S) = 6\$.\n" .
                   "2. **Kejadian Muncul Prima Ganjil (\$A\$)**: Angka prima pada dadu adalah \\{2, 3, 5\\}. Yang ganjil adalah \\{3, 5\\}, dengan \$n(A) = 2\$.\n" .
                   "3. **Perhitungan Peluang**: \$P(A) = \\frac{n(A)}{n(S)} = \\frac{2}{6} = \\frac{1}{3}\$ sesuai kunci jawaban resmi database.";
        }

        // Integral batas 0 sampai 2 -> 10
        if (strpos($textLower, 'integral') !== false && (strpos($cleanExplanation, '8 + 4 - 2') !== false || strpos($cleanExplanation, '10') !== false)) {
            return "1. **Tentukan Anti-Turunan**: \$\\int (3x^2 + 2x - 1) dx = \\left[ x^3 + x^2 - x \\right]\$.\n" .
                   "2. **Evaluasi Batas Atas (\$x = 2\$)**: \$(2)^3 + (2)^2 - 2 = 8 + 4 - 2 = 10\$.\n" .
                   "3. **Evaluasi Batas Bawah (\$x = 0\$)**: \$0^3 + 0^2 - 0 = 0\$.\n" .
                   "4. **Hasil Akhir**: \$10 - 0 = 10\$ sesuai kunci jawaban resmi database.";
        }

        // 25 x 4 -> 100
        if ((strpos($textLower, '25 x 4') !== false || strpos($textLower, '25 * 4') !== false || strpos($textLower, '25 dikali 4') !== false) && strpos($cleanExplanation, '100') !== false) {
            return "1. **Operasi Perkalian**: \$25 \\times 4 = 25 + 25 + 25 + 25 = 100\$.\n" .
                   "2. **Verifikasi**: \$100 / 4 = 25\$, sehingga nilai yang tepat adalah 100 sesuai kunci jawaban resmi database.";
        }

        // Bcrypt -> Blowfish & salt
        if (strpos($textLower, 'bcrypt') !== false && strpos($cleanExplanation, 'blowfish') !== false) {
            return "1. **Algoritma**: Bcrypt dirancang berbasis cipher Blowfish dengan fungsi adaptif work factor.\n" .
                   "2. **Garam Acak (Salt)**: Menghasilkan string unik setiap enkripsi untuk menangkal rainbow table attack.\n" .
                   "3. **Keamanan Satu Arah**: Password tidak didekripsi kembali, melainkan diverifikasi melalui pencocokan hash.";
        }

        // Teorema Limit Pusat
        if (strpos($textLower, 'limit pusat') !== false) {
            return "1. **Teorema Limit Pusat (Central Limit Theorem)**: Salah satu teorema fundamental dalam probabilitas dan statistika.\n" .
                   "2. **Distribusi Sampel**: Menyatakan bahwa distribusi rata-rata sampel dari variabel acak independen akan berdistribusi mendekati normal seiring bertambahnya ukuran sampel.\n" .
                   "3. **Penerapan**: Menjadi landasan uji hipotesis dan estimasi interval kepercayaan populasi.";
        }

        // DYNAMIC DATABASE GENERATOR (MENGACU LANGSUNG KE DATA DATABASE TERBARU JIKA SOAL DIEDIT / BERUBAH)
        $rawSentences = preg_split('/(?<=[.!?])\s+|\n+/', $cleanExplanation, -1, PREG_SPLIT_NO_EMPTY);
        $steps = [];
        $stepNum = 1;
        $steps[] = "{$stepNum}. **Identifikasi & Konsep Soal**: Cermati butir soal yang tercatat pada basis data sistem ujian.";
        $stepNum++;

        if (!empty($rawSentences)) {
            foreach ($rawSentences as $sentence) {
                $trimmed = trim($sentence);
                if (mb_strlen($trimmed) > 3) {
                    $steps[] = "{$stepNum}. **Langkah Pembahasan Database**: {$trimmed}";
                    $stepNum++;
                }
            }
        } else {
            $steps[] = "{$stepNum}. **Rujukan Pembahasan Database**: *\"{$cleanExplanation}\"*";
            $stepNum++;
        }

        $steps[] = "{$stepNum}. **Kesimpulan Jawaban**: Berdasarkan data resmi database, jawaban yang benar adalah **{$correctKey}**.";

        return implode("\n", $steps);
    }

    /**
     * Sanitize output to prevent accidental leakage of sensitive tokens or credentials
     *
     * @param string $text
     * @return string
     */
    public static function sanitizeOutput(string $text): string
    {
        // Strip any accidental API key patterns
        $text = preg_replace('/AQ\.[A-Za-z0-9_-]{30,}/', '[PROTECTED_API_KEY]', $text);
        $text = preg_replace('/AIza[0-9A-Za-z-_]{35}/', '[PROTECTED_API_KEY]', $text);
        // Strip any password hash signatures
        $text = preg_replace('/\$2y\$10\$[A-Za-z0-9.\/]{50,}/', '[PROTECTED_CREDENTIAL]', $text);
        return $text;
    }

    /**
     * Personalized welcome message based on user session & role
     *
     * @param array|null $user
     * @param array $scopedData
     * @return string
     */
    public static function getPersonalizedWelcome(?array $user, array $scopedData): string
    {
        $role = $user['role'] ?? 'guest';
        $name = $user['full_name'] ?? 'Pengunjung';

        if ($role === 'student') {
            $class = $scopedData['details']['student']['class_name'] ?? '';
            $classBadge = $class ? " ({$class})" : '';
            return "Halo **{$name}**{$classBadge}! 👋 Saya **ExamBot**, asisten AI Anda.\n\nAda yang bisa saya bantu terkait jadwal ujian, cara kerja kamera proctoring, atau persiapan pengerjaan?";
        } elseif ($role === 'teacher') {
            return "Selamat datang, Bapak/Ibu **{$name}**! 👨‍🏫\n\nSaya siap membantu seputar pembuatan bank soal, jadwal ujian, maupun pemantauan live monitoring peserta didik.";
        } elseif ($role === 'admin') {
            return "Halo Administrator **{$name}**! 🛡️\n\nExamBot siap membantu rekapitulasi data master, status sistem ujian, dan integritas proctoring.";
        }

        return "Halo! 👋 Saya **ExamBot**, asisten AI sistem ujian online ini.\n\nAda yang bisa saya bantu terkait jadwal ujian, cara login, fitur keamanan kamera, atau panduan guru?";
    }

    /**
     * Get suggestions based on user role
     *
     * @param string $role
     * @return array
     */
    public static function getSuggestionsForRole(string $role): array
    {
        return match($role) {
            'student' => [
                'Jadwal ujian saya yang tersedia',
                'Berapa nilai ujian saya yang selesai?',
                'Bagaimana cara kerja AI Proctoring?',
                'Solusi jika kamera error saat ujian'
            ],
            'teacher' => [
                'Rekapitulasi ujian yang saya buat',
                'Bagaimana cara pantau Live Monitoring?',
                'Panduan membuat bank soal pilihan ganda & essay',
                'Aturan verifikasi pelanggaran proctoring'
            ],
            'admin' => [
                'Ringkasan data pengguna & kelas',
                'Panduan manajemen akun guru dan siswa',
                'Status server & integritas sistem'
            ],
            default => [
                'Apa itu AI Proctoring?',
                'Bagaimana alur siswa saat ujian?',
                'Panduan guru membuat soal',
                'Akun demo untuk uji coba',
                'Solusi kamera tidak terdeteksi'
            ]
        };
    }

    /**
     * Default suggestions for public
     *
     * @return array
     */
    public static function getDefaultSuggestions(): array
    {
        return self::getSuggestionsForRole('guest');
    }

    /**
     * Get contextual suggestions based on message & role
     *
     * @param string $message
     * @param string $role
     * @return array
     */
    public static function getContextualSuggestions(string $message, string $role = 'guest'): array
    {
        $msg = mb_strtolower($message, 'UTF-8');
        if (strpos($msg, 'guru') !== false || strpos($msg, 'soal') !== false) {
            return [
                'Cara melihat Live Monitoring',
                'Format soal yang didukung',
                'Panduan koreksi jawaban essay'
            ];
        }
        if (strpos($msg, 'siswa') !== false || strpos($msg, 'ujian') !== false) {
            return [
                'Bagaimana jika kamera error?',
                'Bagaimana jika koneksi putus?',
                'Tata tertib ruang ujian AI'
            ];
        }
        return self::getSuggestionsForRole($role);
    }

    /**
     * Detect action button based on message & role
     *
     * @param string $message
     * @param string $role
     * @return array|null
     */
    public static function detectAction(string $message, string $role = 'guest'): ?array
    {
        $msg = mb_strtolower($message, 'UTF-8');
        if ($role === 'guest' && (strpos($msg, 'demo') !== false || strpos($msg, 'login') !== false || strpos($msg, 'masuk') !== false)) {
            return [
                'label' => 'Buka Halaman Login Sekarang',
                'url' => BASE_URL . '/auth/login',
                'icon' => 'bi-box-arrow-in-right'
            ];
        }
        if ($role === 'student' && (strpos($msg, 'ujian') !== false || strpos($msg, 'jadwal') !== false || strpos($msg, 'nilai') !== false)) {
            return [
                'label' => 'Buka Daftar Ujian Saya',
                'url' => BASE_URL . '/student/exams',
                'icon' => 'bi-journal-check'
            ];
        }
        if ($role === 'teacher' && (strpos($msg, 'monitoring') !== false || strpos($msg, 'pantau') !== false)) {
            return [
                'label' => 'Buka Live Monitoring',
                'url' => BASE_URL . '/teacher/monitoring',
                'icon' => 'bi-camera-video'
            ];
        }
        if (strpos($msg, 'kamera') !== false || strpos($msg, 'webcam') !== false) {
            return [
                'label' => 'Uji Izin Peramban',
                'url' => BASE_URL . '/auth/login',
                'icon' => 'bi-camera-video'
            ];
        }
        return null;
    }

    /**
     * Default action based on role
     *
     * @param string $role
     * @return array|null
     */
    public static function getDefaultActionForRole(string $role): ?array
    {
        return match($role) {
            'student' => [
                'label' => 'Buka Ujian Saya',
                'url' => BASE_URL . '/student/exams',
                'icon' => 'bi-journal-check'
            ],
            'teacher' => [
                'label' => 'Buka Live Monitoring',
                'url' => BASE_URL . '/teacher/monitoring',
                'icon' => 'bi-camera-video'
            ],
            'guest' => [
                'label' => 'Coba Login Demo',
                'url' => BASE_URL . '/auth/login',
                'icon' => 'bi-box-arrow-in-right'
            ],
            default => null
        };
    }

    /**
     * Return detailed list of all system users from database
     */
    public static function getUserDetailsResponse(string $role): array
    {
        $db = Database::getInstance();
        $users = [];
        if ($db->isConnected()) {
            $users = $db->query("SELECT username, full_name, role, is_active FROM users ORDER BY FIELD(role, 'admin', 'teacher', 'student'), full_name ASC")->resultSet() ?: [];
        }

        $admins = array_filter($users, fn($u) => $u['role'] === 'admin');
        $teachers = array_filter($users, fn($u) => $u['role'] === 'teacher');
        $students = array_filter($users, fn($u) => $u['role'] === 'student');

        $lines = [];

        if (!empty($admins)) {
            $lines[] = "👑 Administrator (" . count($admins) . " akun):";
            foreach ($admins as $adm) {
                $st = !empty($adm['is_active']) ? 'Aktif' : 'Nonaktif';
                $lines[] = "{$adm['full_name']} ({$adm['username']}) - Status: {$st}";
            }
            $lines[] = "";
        }

        if (!empty($teachers)) {
            $lines[] = "👨‍🏫 Guru (" . count($teachers) . " akun):";
            foreach ($teachers as $tc) {
                $st = !empty($tc['is_active']) ? 'Aktif' : 'Nonaktif';
                $lines[] = "{$tc['full_name']} ({$tc['username']}) - Status: {$st}";
            }
            $lines[] = "";
        }

        if (!empty($students)) {
            $lines[] = "🎓 Siswa (" . count($students) . " akun):";
            foreach ($students as $st) {
                $status = !empty($st['is_active']) ? 'Aktif' : 'Nonaktif';
                $lines[] = "{$st['full_name']} ({$st['username']}) - Status: {$status}";
            }
        }

        return [
            'status' => 'success',
            'source' => 'database_info',
            'reply' => trim(implode("\n", $lines)),
            'suggestions' => ['Ada berapa total guru', 'Ada berapa kelas', 'Ada berapa mata pelajaran'],
            'action' => ($role === 'admin') ? ['label' => 'Buka Manajemen Pengguna', 'url' => BASE_URL . '/admin/users', 'icon' => 'bi-people'] : null
        ];
    }

    /**
     * Return detailed list of all teachers from database
     */
    public static function getTeacherDetailsResponse(): array
    {
        $db = Database::getInstance();
        $teachers = [];
        if ($db->isConnected()) {
            $teachers = $db->query("
                SELECT u.full_name, t.nip, t.specialization
                FROM teachers t
                JOIN users u ON t.user_id = u.id
                ORDER BY u.full_name ASC
            ")->resultSet() ?: [];
        }

        $lines = [];
        foreach ($teachers as $tc) {
            $spec = $tc['specialization'] ? " (Bidang: {$tc['specialization']})" : '';
            $lines[] = "{$tc['full_name']} - NIP: {$tc['nip']}{$spec}";
        }

        return [
            'status' => 'success',
            'source' => 'database_info',
            'reply' => implode("\n", $lines),
            'suggestions' => ['Ada berapa total user', 'Ada berapa mata pelajaran', 'Ada berapa kelas'],
            'action' => null
        ];
    }

    /**
     * Return detailed list of all students from database
     */
    public static function getStudentDetailsResponse(): array
    {
        $db = Database::getInstance();
        $students = [];
        if ($db->isConnected()) {
            $students = $db->query("
                SELECT u.full_name, s.nis, c.name AS class_name
                FROM students s
                JOIN users u ON s.user_id = u.id
                LEFT JOIN classes c ON s.class_id = c.id
                ORDER BY u.full_name ASC
            ")->resultSet() ?: [];
        }

        $count = count($students);
        if ($count > 0) {
            $lines = ["🎓 **Rincian Data Siswa Terdaftar (Database)**:\n\nSaat ini terdapat **{$count} siswa** yang terdaftar di sistem:"];
            $i = 1;
            foreach ($students as $st) {
                $cls = $st['class_name'] ? ", Kelas: {$st['class_name']}" : '';
                $lines[] = "{$i}. **{$st['full_name']}** (NIS: `{$st['nis']}`{$cls})";
                $i++;
            }
            $replyText = implode("\n", $lines);
        } else {
            $replyText = "🎓 **Data Siswa**: Belum ada siswa terdaftar di database.";
        }

        return [
            'status' => 'success',
            'source' => 'database_info',
            'reply' => $replyText,
            'suggestions' => ['NIS setiap siswa', 'Jumlah siswa di setiap kelas nya', 'Ada berapa kelas'],
            'action' => null
        ];
    }

    /**
     * Return detailed list of all classes from database
     */
    public static function getClassDetailsResponse(): array
    {
        $db = Database::getInstance();
        $classes = [];
        if ($db->isConnected()) {
            $classes = $db->query("SELECT id, name, level, academic_year FROM classes ORDER BY name ASC")->resultSet() ?: [];
        }

        $count = count($classes);
        if ($count > 0) {
            $lines = ["🏫 **Rincian Rombel Kelas (Database)**:\n\nSaat ini terdapat **{$count} kelas** yang terdaftar di sistem:"];
            $i = 1;
            foreach ($classes as $cl) {
                $lines[] = "{$i}. **{$cl['name']}** (Tingkat: {$cl['level']}, Tahun Ajaran: {$cl['academic_year']})";
                $i++;
            }
            $replyText = implode("\n", $lines);
        } else {
            $replyText = "🏫 **Data Kelas**: Belum ada rombel kelas yang terdaftar di basis data.";
        }

        return [
            'status' => 'success',
            'source' => 'database_info',
            'reply' => $replyText,
            'suggestions' => ['Jumlah siswa di setiap kelas nya', 'Ada berapa siswa', 'Daftar mata pelajaran'],
            'action' => null
        ];
    }

    /**
     * Return detailed list of all subjects from database
     */
    public static function getSubjectDetailsResponse(): array
    {
        $db = Database::getInstance();
        $subjects = [];
        if ($db->isConnected()) {
            $subjects = $db->query("SELECT id, code, name, description FROM subjects ORDER BY name ASC")->resultSet() ?: [];
        }

        $count = count($subjects);
        if ($count > 0) {
            $lines = ["📚 **Rincian Mata Pelajaran (Database)**:\n\nSaat ini terdapat **{$count} mata pelajaran** yang terdaftar di database:"];
            $i = 1;
            foreach ($subjects as $sb) {
                $desc = $sb['description'] ? " - " . $sb['description'] : '';
                $lines[] = "{$i}. **{$sb['name']}** (`{$sb['code']}`){$desc}";
                $i++;
            }
            $replyText = implode("\n", $lines);
        } else {
            $replyText = "📚 **Mata Pelajaran Sistem**: Belum ada data mata pelajaran yang tercatat di database.";
        }

        return [
            'status' => 'success',
            'source' => 'database_info',
            'reply' => $replyText,
            'suggestions' => ['Berapa soal di setiap mata pelajaran', 'Siapa guru matematika', 'Bahas soal nomor 1'],
            'action' => null
        ];
    }

    /**
     * Return detailed list of all exams from database
     */
    public static function getExamDetailsResponse(string $role): array
    {
        $db = Database::getInstance();
        $exams = [];
        $totalExams = 0;
        if ($db->isConnected()) {
            $totalExams = (int)($db->query("SELECT COUNT(*) AS total FROM exams")->single()['total'] ?? 0);
            $exams = $db->query("
                SELECT e.id, e.title, s.name AS subject_name, e.duration_minutes, e.passing_score, e.status, e.start_time
                FROM exams e
                JOIN subjects s ON e.subject_id = s.id
                ORDER BY e.start_time DESC
            ")->resultSet() ?: [];
        }

        if (!empty($exams) || $totalExams > 0) {
            $lines = [
                "📋 **Rincian Lengkap Sesi Ujian (Data Database / database)**:\n",
                "Saat ini terdapat total **{$totalExams} sesi ujian** yang tercatat di sistem:"
            ];
            foreach ($exams as $ex) {
                $statusLabel = match($ex['status']) {
                    'running' => '🟢 Sedang Berlangsung',
                    'published' => '📅 Terjadwal',
                    default => '🏁 Selesai'
                };
                $lines[] = "- **{$ex['title']}** ({$ex['subject_name']})\n  Status: {$statusLabel} | Durasi: {$ex['duration_minutes']} menit | KKM: **{$ex['passing_score']}**";
            }
            $replyText = implode("\n", $lines);
        } else {
            $replyText = "📋 **Jadwal Ujian**: Belum ada sesi ujian aktif di basis data.";
        }

        return [
            'status' => 'success',
            'source' => 'database_info',
            'reply' => $replyText,
            'suggestions' => ['Ada berapa peserta', 'Siapa siswa yang telah mengikuti ujian'],
            'action' => ($role === 'guest') ? ['label' => 'Login Siswa', 'url' => BASE_URL . '/auth/login', 'icon' => 'bi-box-arrow-in-right'] : ['label' => 'Buka Daftar Ujian', 'url' => BASE_URL . '/student/exams', 'icon' => 'bi-journal-check']
        ];
    }

    /**
     * Helper to return time-based greeting in Indonesian
     *
     * @return string
     */
    private static function getGreetingTime(): string
    {
        $hour = (int)date('H');
        if ($hour >= 4 && $hour < 11) {
            return 'Selamat Pagi';
        } elseif ($hour >= 11 && $hour < 15) {
            return 'Selamat Siang';
        } elseif ($hour >= 15 && $hour < 18) {
            return 'Selamat Sore';
        } else {
            return 'Selamat Malam';
        }
    }
}
