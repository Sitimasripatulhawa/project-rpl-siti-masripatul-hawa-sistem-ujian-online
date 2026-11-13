<?php
/**
 * Exam Engine Model (Mesin Ujian Peserta Didik)
 * AI Online Exam & Proctoring System
 */

class ExamEngineModel extends Model
{
    /**
     * Get list of exams assigned to a student with status & subject details
     *
     * @param int $studentId
     * @param string $filter 'all', 'available', 'completed'
     * @return array
     */
    public function getAssignedExamsForStudent(int $studentId, string $filter = 'all'): array
    {
        $params = [':sid' => $studentId];
        $where = ["ep.student_id = :sid", "e.status IN ('published', 'running', 'finished')"];

        if ($filter === 'available') {
            $where[] = "ep.status IN ('not_started', 'in_progress')";
            $where[] = "e.status IN ('published', 'running')";
        } elseif ($filter === 'completed') {
            $where[] = "ep.status = 'submitted'";
        }

        $whereClause = "WHERE " . implode(' AND ', $where);

        $sql = "SELECT ep.id AS participant_id, ep.status AS participant_status, 
                       ep.start_time AS student_start_time, ep.submit_time AS student_submit_time,
                       e.id AS exam_id, e.title, e.description, e.subject_id, e.class_id,
                       e.duration_minutes, e.start_time, e.end_time, e.token,
                       e.is_random_question, e.is_random_option, e.enable_proctoring,
                       e.status AS exam_status, e.total_score, e.passing_score, e.show_result,
                       s.code AS subject_code, s.name AS subject_name,
                       u.full_name AS teacher_name,
                       c.name AS class_name,
                       er.final_score, er.is_passed,
                       COUNT(DISTINCT eq.question_id) AS total_questions
                FROM exam_participants ep
                JOIN exams e ON ep.exam_id = e.id
                JOIN subjects s ON e.subject_id = s.id
                JOIN classes c ON e.class_id = c.id
                JOIN teachers t ON e.teacher_id = t.id
                JOIN users u ON t.user_id = u.id
                LEFT JOIN exam_questions eq ON e.id = eq.exam_id
                LEFT JOIN exam_results er ON ep.id = er.exam_participant_id
                {$whereClause}
                GROUP BY ep.id
                ORDER BY e.start_time ASC, e.id DESC";

        $query = $this->db->query($sql);
        foreach ($params as $param => $val) {
            $query->bind($param, $val);
        }

        return $query->resultSet();
    }

    /**
     * Get participant record with complete exam and class metadata
     *
     * @param int $examId
     * @param int $studentId
     * @return array|null
     */
    public function getExamParticipant(int $examId, int $studentId): ?array
    {
        $sql = "SELECT ep.id AS participant_id, ep.exam_id, ep.student_id, ep.status AS participant_status,
                       ep.start_time AS participant_start_time, ep.submit_time AS participant_submit_time,
                       ep.risk_score, ep.ip_address, ep.user_agent,
                       e.title, e.description, e.subject_id, e.teacher_id, e.class_id,
                       e.duration_minutes, e.start_time, e.end_time, e.token,
                       e.is_random_question, e.is_random_option, e.enable_proctoring,
                       e.status AS exam_status, e.total_score, e.passing_score, e.show_result,
                       s.code AS subject_code, s.name AS subject_name,
                       u.full_name AS teacher_name,
                       c.name AS class_name
                FROM exam_participants ep
                JOIN exams e ON ep.exam_id = e.id
                JOIN subjects s ON e.subject_id = s.id
                JOIN classes c ON e.class_id = c.id
                JOIN teachers t ON e.teacher_id = t.id
                JOIN users u ON t.user_id = u.id
                WHERE ep.exam_id = :eid AND ep.student_id = :sid
                LIMIT 1";

        return $this->db->query($sql)
            ->bind(':eid', $examId)
            ->bind(':sid', $studentId)
            ->single();
    }

    /**
     * Verify student access eligibility for starting an exam
     *
     * @param int $examId
     * @param int $studentId
     * @param string|null $inputToken
     * @return array ['valid' => bool, 'message' => string, 'participant' => array|null]
     */
    public function verifyExamAccess(int $examId, int $studentId, ?string $inputToken = null): array
    {
        $participant = $this->getExamParticipant($examId, $studentId);

        if (!$participant) {
            return [
                'valid' => false,
                'message' => 'Anda tidak terdaftar sebagai peserta pada sesi ujian ini.',
                'participant' => null
            ];
        }

        // Check exam status
        if (!in_array($participant['exam_status'], ['published', 'running'])) {
            return [
                'valid' => false,
                'message' => 'Sesi ujian belum dibuka atau sudah ditutup oleh penguji.',
                'participant' => $participant
            ];
        }

        // Check participant status
        if ($participant['participant_status'] === 'submitted') {
            return [
                'valid' => false,
                'message' => 'Anda telah menyelesaikan dan mengumpulkan ujian ini.',
                'participant' => $participant
            ];
        }

        if ($participant['participant_status'] === 'terminated') {
            return [
                'valid' => false,
                'message' => 'Sesi ujian Anda telah dihentikan oleh pengawas sistem.',
                'participant' => $participant
            ];
        }

        $now = time();
        $startTime = strtotime($participant['start_time']);
        $endTime = strtotime($participant['end_time']);

        if ($now < $startTime) {
            return [
                'valid' => false,
                'message' => 'Ujian belum dimulai. Jadwal mulai: ' . date('d M Y H:i', $startTime) . ' WIB.',
                'participant' => $participant
            ];
        }

        if ($now > $endTime) {
            return [
                'valid' => false,
                'message' => 'Waktu pelaksanaan ujian telah berakhir pada ' . date('d M Y H:i', $endTime) . ' WIB.',
                'participant' => $participant
            ];
        }

        // Verify token if required and provided
        if (!empty($participant['token'])) {
            if ($inputToken === null) {
                // Needs token validation
                return [
                    'valid' => false,
                    'message' => 'Token ujian wajib dimasukkan.',
                    'needs_token' => true,
                    'participant' => $participant
                ];
            }

            if (strtoupper(trim($inputToken)) !== strtoupper(trim($participant['token']))) {
                return [
                    'valid' => false,
                    'message' => 'Token ujian yang Anda masukkan tidak valid.',
                    'participant' => $participant
                ];
            }
        }

        return [
            'valid' => true,
            'message' => 'Akses terverifikasi.',
            'participant' => $participant
        ];
    }

    /**
     * Begin exam attempt for participant (not_started -> in_progress)
     *
     * @param int $participantId
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @return bool
     */
    public function startExam(int $participantId, ?string $ipAddress = null, ?string $userAgent = null): bool
    {
        $participant = $this->db->query("SELECT id, status, start_time FROM exam_participants WHERE id = :id LIMIT 1")
            ->bind(':id', $participantId)
            ->single();

        if (!$participant) {
            return false;
        }

        // Only set start_time on first start
        if ($participant['status'] === 'not_started' || empty($participant['start_time'])) {
            $sql = "UPDATE exam_participants 
                    SET status = 'in_progress', 
                        start_time = NOW(), 
                        ip_address = :ip, 
                        user_agent = :ua 
                    WHERE id = :id";

            return $this->db->query($sql)
                ->bind(':id', $participantId)
                ->bind(':ip', $ipAddress)
                ->bind(':ua', $userAgent)
                ->execute();
        }

        return true;
    }

    /**
     * Calculate remaining time in seconds based on server source of truth
     *
     * @param array $exam
     * @param array $participant
     * @return int
     */
    public function calculateRemainingSeconds(array $exam, array $participant): int
    {
        if (empty($participant['participant_start_time'])) {
            return (int)($exam['duration_minutes'] * 60);
        }

        $now = time();
        $startTime = strtotime($participant['participant_start_time']);
        $durationSeconds = (int)$exam['duration_minutes'] * 60;
        $maxEndByDuration = $startTime + $durationSeconds;

        $maxEndBySchedule = strtotime($exam['end_time']);

        $deadline = min($maxEndByDuration, $maxEndBySchedule);
        $remaining = $deadline - $now;

        return max(0, $remaining);
    }

    /**
     * Fetch questions and options for exam room with deterministic shuffle per student
     * SECURITY: Never returns `is_correct` or `explanation`!
     *
     * @param int $examId
     * @param int $participantId
     * @param bool $randomQuestion
     * @param bool $randomOption
     * @return array
     */
    public function getExamQuestionsAndSavedAnswers(int $examId, int $participantId, bool $randomQuestion, bool $randomOption): array
    {
        // 1. Fetch exam questions (without answers/explanations)
        $qSql = "SELECT eq.question_order, eq.question_score,
                        q.id, q.question_text, q.question_type, q.difficulty
                 FROM exam_questions eq
                 JOIN questions q ON eq.question_id = q.id
                 WHERE eq.exam_id = :eid
                 ORDER BY eq.question_order ASC";

        $questions = $this->db->query($qSql)->bind(':eid', $examId)->resultSet();

        // 2. Fetch saved student answers
        $aSql = "SELECT question_id, selected_option_id, essay_answer, is_flagged_doubt, answered_at
                 FROM exam_answers
                 WHERE exam_participant_id = :pid";

        $savedAnswers = $this->db->query($aSql)->bind(':pid', $participantId)->resultSet();
        $answersMap = [];
        foreach ($savedAnswers as $ans) {
            $answersMap[(int)$ans['question_id']] = $ans;
        }

        // 3. Attach options and saved answers
        foreach ($questions as &$q) {
            $qid = (int)$q['id'];
            $q['saved_answer'] = $answersMap[$qid] ?? null;

            if ($q['question_type'] === 'multiple_choice') {
                // Note: is_correct is intentionally OMITTED for student security!
                $optSql = "SELECT id, option_label, option_text
                           FROM question_options
                           WHERE question_id = :qid
                           ORDER BY option_label ASC";

                $options = $this->db->query($optSql)->bind(':qid', $qid)->resultSet();

                // Deterministic option shuffle per question per student
                if ($randomOption && count($options) > 1) {
                    mt_srand((int)crc32($participantId . '_opt_' . $qid));
                    for ($i = count($options) - 1; $i > 0; $i--) {
                        $j = mt_rand(0, $i);
                        $tmp = $options[$i];
                        $options[$i] = $options[$j];
                        $options[$j] = $tmp;
                    }
                }

                $q['options'] = $options;
            } else {
                $q['options'] = [];
            }
        }
        unset($q);

        // Deterministic question shuffle per student
        if ($randomQuestion && count($questions) > 1) {
            mt_srand((int)crc32($participantId . '_exam_' . $examId));
            for ($i = count($questions) - 1; $i > 0; $i--) {
                $j = mt_rand(0, $i);
                $tmp = $questions[$i];
                $questions[$i] = $questions[$j];
                $questions[$j] = $tmp;
            }
        }

        // Reset random seed to system time
        mt_srand();

        return $questions;
    }

    /**
     * Save or update an answer automatically via AJAX
     *
     * @param int $participantId
     * @param int $questionId
     * @param int|null $optionId
     * @param string|null $essayAnswer
     * @param int $isDoubt
     * @return array ['success' => bool, 'message' => string]
     */
    public function saveAnswer(int $participantId, int $questionId, ?int $optionId, ?string $essayAnswer, int $isDoubt = 0): array
    {
        // 1. Verify participant status and remaining time
        $sql = "SELECT ep.id, ep.exam_id, ep.status, ep.start_time,
                       e.duration_minutes, e.end_time
                FROM exam_participants ep
                JOIN exams e ON ep.exam_id = e.id
                WHERE ep.id = :pid
                LIMIT 1";

        $part = $this->db->query($sql)->bind(':pid', $participantId)->single();

        if (!$part) {
            return ['success' => false, 'message' => 'Peserta tidak ditemukan.'];
        }

        if ($part['status'] !== 'in_progress') {
            return ['success' => false, 'message' => 'Ujian sudah diselesaikan atau tidak dalam status aktif.'];
        }

        $now = time();
        $startTime = strtotime($part['start_time']);
        $durationSeconds = (int)$part['duration_minutes'] * 60;
        $deadline = min($startTime + $durationSeconds, strtotime($part['end_time']));

        if ($now > $deadline + 15) { // 15 seconds grace period for network latency
            return ['success' => false, 'message' => 'Waktu pengerjaan ujian telah habis.', 'timeout' => true];
        }

        // 2. SECURITY HARDENING: Validate question ownership for this exam
        $qCheck = $this->db->query("
            SELECT q.question_type 
            FROM exam_questions eq
            JOIN questions q ON eq.question_id = q.id
            WHERE eq.exam_id = :eid AND eq.question_id = :qid
            LIMIT 1
        ")
        ->bind(':eid', $part['exam_id'])
        ->bind(':qid', $questionId)
        ->single();

        if (!$qCheck) {
            return ['success' => false, 'message' => 'Soal tidak valid atau bukan bagian dari sesi ujian ini.'];
        }

        // 3. SECURITY HARDENING: Validate option ownership for multiple choice questions
        if ($optionId !== null) {
            $optCheck = $this->db->query("
                SELECT id 
                FROM question_options 
                WHERE id = :opt AND question_id = :qid 
                LIMIT 1
            ")
            ->bind(':opt', $optionId)
            ->bind(':qid', $questionId)
            ->single();

            if (!$optCheck) {
                return ['success' => false, 'message' => 'Pilihan jawaban tidak valid untuk butir soal ini.'];
            }
        }

        // 4. Upsert answer
        $upsertSql = "INSERT INTO exam_answers (exam_participant_id, question_id, selected_option_id, essay_answer, is_flagged_doubt, answered_at)
                      VALUES (:pid, :qid, :opt, :essay, :doubt, NOW())
                      ON DUPLICATE KEY UPDATE
                          selected_option_id = VALUES(selected_option_id),
                          essay_answer = VALUES(essay_answer),
                          is_flagged_doubt = VALUES(is_flagged_doubt),
                          answered_at = NOW()";

        $success = $this->db->query($upsertSql)
            ->bind(':pid', $participantId)
            ->bind(':qid', $questionId)
            ->bind(':opt', $optionId)
            ->bind(':essay', $essayAnswer)
            ->bind(':doubt', $isDoubt ? 1 : 0)
            ->execute();

        return [
            'success' => (bool)$success,
            'message' => $success ? 'Jawaban berhasil disimpan.' : 'Gagal menyimpan jawaban.',
            'answered_at' => date('H:i:s')
        ];
    }

    /**
     * Submit exam, prevent duplicates, and calculate final scores atomically
     *
     * @param int $participantId
     * @return array ['success' => bool, 'message' => string, 'result' => array|null]
     * @throws Exception
     */
    public function submitExamAndCalculateScore(int $participantId): array
    {
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();

        try {
            // Lock participant row for update to prevent race conditions / duplicate submit
            $pStmt = $pdo->prepare("
                SELECT ep.id, ep.exam_id, ep.student_id, ep.status, ep.start_time,
                       e.total_score, e.passing_score
                FROM exam_participants ep
                JOIN exams e ON ep.exam_id = e.id
                WHERE ep.id = :pid
                FOR UPDATE
            ");
            $pStmt->execute([':pid' => $participantId]);
            $participant = $pStmt->fetch(PDO::FETCH_ASSOC);

            if (!$participant) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Data ujian peserta tidak ditemukan.'];
            }

            if ($participant['status'] === 'submitted') {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Ujian ini sudah pernah dikumpulkan sebelumnya.', 'already_submitted' => true];
            }

            // 1. Mark participant as submitted
            $upStmt = $pdo->prepare("
                UPDATE exam_participants 
                SET status = 'submitted', submit_time = NOW() 
                WHERE id = :pid
            ");
            $upStmt->execute([':pid' => $participantId]);

            // 2. Fetch all exam questions with correct options
            $qStmt = $pdo->prepare("
                SELECT eq.question_id, eq.question_score, q.question_type
                FROM exam_questions eq
                JOIN questions q ON eq.question_id = q.id
                WHERE eq.exam_id = :eid
            ");
            $qStmt->execute([':eid' => (int)$participant['exam_id']]);
            $examQuestions = $qStmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch correct options for multiple choice questions
            $correctOptionsStmt = $pdo->prepare("
                SELECT qo.question_id, qo.id AS correct_option_id
                FROM question_options qo
                JOIN exam_questions eq ON qo.question_id = eq.question_id
                WHERE eq.exam_id = :eid AND qo.is_correct = 1
            ");
            $correctOptionsStmt->execute([':eid' => (int)$participant['exam_id']]);
            $correctOptions = [];
            foreach ($correctOptionsStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $correctOptions[(int)$row['question_id']] = (int)$row['correct_option_id'];
            }

            // Fetch student answers
            $ansStmt = $pdo->prepare("
                SELECT question_id, selected_option_id, essay_answer
                FROM exam_answers
                WHERE exam_participant_id = :pid
            ");
            $ansStmt->execute([':pid' => $participantId]);
            $studentAnswers = [];
            foreach ($ansStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $studentAnswers[(int)$row['question_id']] = $row;
            }

            // 3. Evaluate answers
            $totalCorrect = 0;
            $totalIncorrect = 0;
            $totalUnanswered = 0;
            $finalScore = 0.00;

            $updateAnswerStmt = $pdo->prepare("
                UPDATE exam_answers 
                SET is_correct = :corr, awarded_score = :score 
                WHERE exam_participant_id = :pid AND question_id = :qid
            ");

            $insertEmptyAnswerStmt = $pdo->prepare("
                INSERT INTO exam_answers (exam_participant_id, question_id, is_correct, awarded_score, answered_at)
                VALUES (:pid, :qid, 0, 0.00, NOW())
                ON DUPLICATE KEY UPDATE is_correct = 0, awarded_score = 0.00
            ");

            foreach ($examQuestions as $eq) {
                $qid = (int)$eq['question_id'];
                $qType = $eq['question_type'];
                $qScore = (float)$eq['question_score'];

                $ans = $studentAnswers[$qid] ?? null;

                if ($qType === 'multiple_choice') {
                    if (!$ans || empty($ans['selected_option_id'])) {
                        // Unanswered
                        $totalUnanswered++;
                        $insertEmptyAnswerStmt->execute([
                            ':pid' => $participantId,
                            ':qid' => $qid
                        ]);
                    } else {
                        $selected = (int)$ans['selected_option_id'];
                        $correct = $correctOptions[$qid] ?? null;

                        if ($correct !== null && $selected === $correct) {
                            $totalCorrect++;
                            $finalScore += $qScore;
                            $updateAnswerStmt->execute([
                                ':corr' => 1,
                                ':score' => $qScore,
                                ':pid' => $participantId,
                                ':qid' => $qid
                            ]);
                        } else {
                            $totalIncorrect++;
                            $updateAnswerStmt->execute([
                                ':corr' => 0,
                                ':score' => 0.00,
                                ':pid' => $participantId,
                                ':qid' => $qid
                            ]);
                        }
                    }
                } else {
                    // Essay question: pending manual teacher review
                    if (!$ans || trim((string)$ans['essay_answer']) === '') {
                        $totalUnanswered++;
                        $insertEmptyAnswerStmt->execute([
                            ':pid' => $participantId,
                            ':qid' => $qid
                        ]);
                    } else {
                        // Mark as pending: is_correct is NULL, score is 0.00 until graded by teacher
                        $updateAnswerStmt->execute([
                            ':corr' => null,
                            ':score' => 0.00,
                            ':pid' => $participantId,
                            ':qid' => $qid
                        ]);
                    }
                }
            }

            // 4. Calculate pass status
            $passingScore = (float)$participant['passing_score'];
            $isPassed = ($finalScore >= $passingScore) ? 1 : 0;

            // 5. Insert or update exam_results
            $resStmt = $pdo->prepare("
                INSERT INTO exam_results (exam_participant_id, total_correct, total_incorrect, total_unanswered, final_score, is_passed, proctoring_risk_level, calculated_at)
                VALUES (:pid, :tc, :ti, :tu, :fs, :pass, 'low', NOW())
                ON DUPLICATE KEY UPDATE
                    total_correct = VALUES(total_correct),
                    total_incorrect = VALUES(total_incorrect),
                    total_unanswered = VALUES(total_unanswered),
                    final_score = VALUES(final_score),
                    is_passed = VALUES(is_passed),
                    calculated_at = NOW()
            ");

            $resStmt->execute([
                ':pid' => $participantId,
                ':tc' => $totalCorrect,
                ':ti' => $totalIncorrect,
                ':tu' => $totalUnanswered,
                ':fs' => round($finalScore, 2),
                ':pass' => $isPassed
            ]);

            $pdo->commit();

            return [
                'success' => true,
                'message' => 'Ujian berhasil dikumpulkan.',
                'result' => [
                    'total_correct' => $totalCorrect,
                    'total_incorrect' => $totalIncorrect,
                    'total_unanswered' => $totalUnanswered,
                    'final_score' => round($finalScore, 2),
                    'is_passed' => $isPassed
                ]
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Get exam result summary and breakdown
     *
     * @param int $participantId
     * @return array|null
     */
    public function getExamResult(int $participantId): ?array
    {
        $sql = "SELECT er.id AS result_id, er.total_correct, er.total_incorrect, er.total_unanswered,
                       er.final_score, er.is_passed, er.proctoring_risk_level, er.calculated_at,
                       ep.id AS participant_id, ep.status AS participant_status,
                       ep.start_time AS participant_start_time, ep.submit_time AS participant_submit_time,
                       e.id AS exam_id, e.title, e.description, e.duration_minutes, e.total_score, e.passing_score,
                       s.code AS subject_code, s.name AS subject_name,
                       u.full_name AS teacher_name,
                       c.name AS class_name,
                       COUNT(DISTINCT eq.question_id) AS total_questions,
                       SUM(CASE WHEN q.question_type = 'essay' THEN 1 ELSE 0 END) AS total_essay_questions
                FROM exam_results er
                JOIN exam_participants ep ON er.exam_participant_id = ep.id
                JOIN exams e ON ep.exam_id = e.id
                JOIN subjects s ON e.subject_id = s.id
                JOIN classes c ON e.class_id = c.id
                JOIN teachers t ON e.teacher_id = t.id
                JOIN users u ON t.user_id = u.id
                LEFT JOIN exam_questions eq ON e.id = eq.exam_id
                LEFT JOIN questions q ON eq.question_id = q.id
                WHERE er.exam_participant_id = :pid
                GROUP BY er.id
                LIMIT 1";

        return $this->db->query($sql)
            ->bind(':pid', $participantId)
            ->single();
    }
}
