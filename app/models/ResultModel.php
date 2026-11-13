<?php
/**
 * Result Model (Manajemen & Analitik Hasil Ujian)
 * AI Online Exam & Proctoring System
 */

class ResultModel extends Model
{
    /**
     * Get summary of all exams owned by a teacher with result analytics
     *
     * @param int $teacherId
     * @param string $search
     * @return array
     */
    public function getTeacherExamsSummary(int $teacherId, string $search = ''): array
    {
        $params = [':tid' => $teacherId];
        $where = ["e.teacher_id = :tid"];

        if (!empty($search)) {
            $where[] = "(e.title LIKE :s1 OR s.name LIKE :s2 OR c.name LIKE :s3)";
            $params[':s1'] = '%' . $search . '%';
            $params[':s2'] = '%' . $search . '%';
            $params[':s3'] = '%' . $search . '%';
        }

        $whereClause = "WHERE " . implode(' AND ', $where);

        $sql = "SELECT e.id AS exam_id, e.title, e.description, e.status AS exam_status,
                       e.duration_minutes, e.start_time, e.end_time, e.total_score, e.passing_score,
                       e.show_result,
                       s.code AS subject_code, s.name AS subject_name,
                       c.name AS class_name,
                       COUNT(DISTINCT ep.id) AS total_assigned,
                       SUM(CASE WHEN ep.status = 'submitted' THEN 1 ELSE 0 END) AS total_submitted,
                       MAX(er.final_score) AS highest_score,
                       MIN(er.final_score) AS lowest_score,
                       AVG(er.final_score) AS avg_score,
                       SUM(CASE WHEN er.is_passed = 1 THEN 1 ELSE 0 END) AS total_passed,
                       (
                           SELECT COUNT(DISTINCT ep2.id)
                           FROM exam_participants ep2
                           JOIN exam_answers ea2 ON ep2.id = ea2.exam_participant_id
                           JOIN questions q2 ON ea2.question_id = q2.id
                           WHERE ep2.exam_id = e.id 
                             AND ep2.status = 'submitted'
                             AND q2.question_type = 'essay'
                             AND ea2.is_correct IS NULL
                       ) AS pending_essay_participants
                FROM exams e
                JOIN subjects s ON e.subject_id = s.id
                JOIN classes c ON e.class_id = c.id
                LEFT JOIN exam_participants ep ON e.id = ep.exam_id
                LEFT JOIN exam_results er ON ep.id = er.exam_participant_id
                {$whereClause}
                GROUP BY e.id
                ORDER BY e.id DESC";

        $query = $this->db->query($sql);
        foreach ($params as $param => $val) {
            $query->bind($param, $val);
        }

        $results = $query->resultSet();

        // Format numeric statistics
        foreach ($results as &$r) {
            $r['total_assigned'] = (int)$r['total_assigned'];
            $r['total_submitted'] = (int)$r['total_submitted'];
            $r['total_passed'] = (int)$r['total_passed'];
            $r['highest_score'] = $r['highest_score'] !== null ? round((float)$r['highest_score'], 2) : 0.00;
            $r['lowest_score'] = $r['lowest_score'] !== null ? round((float)$r['lowest_score'], 2) : 0.00;
            $r['avg_score'] = $r['avg_score'] !== null ? round((float)$r['avg_score'], 2) : 0.00;
            $r['passing_rate'] = $r['total_submitted'] > 0 ? round(($r['total_passed'] / $r['total_submitted']) * 100, 1) : 0.0;
            $r['pending_essay_participants'] = (int)$r['pending_essay_participants'];
            $r['show_result'] = (int)($r['show_result'] ?? 1);
        }
        unset($r);

        return $results;
    }

    /**
     * Get detailed results and student participant list for a specific exam
     *
     * @param int $examId
     * @param int|null $teacherId If provided, enforces teacher ownership
     * @param string $filter 'all', 'passed', 'failed', 'pending_essay'
     * @param string $search
     * @return array|null ['exam' => array, 'stats' => array, 'participants' => array]
     */
    public function getExamResultDetail(int $examId, ?int $teacherId = null, string $filter = 'all', string $search = ''): ?array
    {
        // 1. Fetch Exam Meta & verify ownership
        $examSql = "SELECT e.id AS exam_id, e.title, e.description, e.status AS exam_status,
                           e.duration_minutes, e.start_time, e.end_time, e.token,
                           e.total_score, e.passing_score, e.show_result,
                           s.code AS subject_code, s.name AS subject_name,
                           c.name AS class_name,
                           u.full_name AS teacher_name, e.teacher_id,
                           COUNT(DISTINCT eq.question_id) AS total_questions,
                           SUM(CASE WHEN q.question_type = 'essay' THEN 1 ELSE 0 END) AS total_essay_questions
                    FROM exams e
                    JOIN subjects s ON e.subject_id = s.id
                    JOIN classes c ON e.class_id = c.id
                    JOIN teachers t ON e.teacher_id = t.id
                    JOIN users u ON t.user_id = u.id
                    LEFT JOIN exam_questions eq ON e.id = eq.exam_id
                    LEFT JOIN questions q ON eq.question_id = q.id
                    WHERE e.id = :eid " . ($teacherId !== null ? "AND e.teacher_id = :tid " : "") . "
                    GROUP BY e.id
                    LIMIT 1";

        $examQuery = $this->db->query($examSql)->bind(':eid', $examId);
        if ($teacherId !== null) {
            $examQuery->bind(':tid', $teacherId);
        }
        $exam = $examQuery->single();

        if (!$exam) {
            return null;
        }

        // 2. Fetch Participants and Results
        $params = [':eid' => $examId];
        $where = ["ep.exam_id = :eid"];

        if (!empty($search)) {
            $where[] = "(u.full_name LIKE :s1 OR s.nis LIKE :s2 OR s.nisn LIKE :s3)";
            $params[':s1'] = '%' . $search . '%';
            $params[':s2'] = '%' . $search . '%';
            $params[':s3'] = '%' . $search . '%';
        }

        if ($filter === 'passed') {
            $where[] = "er.is_passed = 1";
        } elseif ($filter === 'failed') {
            $where[] = "ep.status = 'submitted' AND er.is_passed = 0";
        } elseif ($filter === 'pending_essay') {
            $where[] = "ep.status = 'submitted' AND (
                SELECT COUNT(*) 
                FROM exam_answers ea 
                JOIN questions q ON ea.question_id = q.id 
                WHERE ea.exam_participant_id = ep.id 
                  AND q.question_type = 'essay' 
                  AND ea.is_correct IS NULL
            ) > 0";
        }

        $whereClause = "WHERE " . implode(' AND ', $where);

        $pSql = "SELECT ep.id AS participant_id, ep.exam_id, ep.student_id, ep.status AS participant_status,
                        ep.start_time AS participant_start_time, ep.submit_time AS participant_submit_time,
                        ep.risk_score,
                        s.nis, s.nisn, s.gender,
                        u.full_name AS student_name, u.email AS student_email,
                        er.id AS result_id, er.total_correct, er.total_incorrect, er.total_unanswered,
                        er.final_score, er.is_passed, er.calculated_at,
                        (
                            SELECT COUNT(*)
                            FROM exam_answers ea
                            JOIN questions q ON ea.question_id = q.id
                            WHERE ea.exam_participant_id = ep.id
                              AND q.question_type = 'essay'
                              AND ea.is_correct IS NULL
                        ) AS pending_essay_count
                 FROM exam_participants ep
                 JOIN students s ON ep.student_id = s.id
                 JOIN users u ON s.user_id = u.id
                 LEFT JOIN exam_results er ON ep.id = er.exam_participant_id
                 {$whereClause}
                 ORDER BY u.full_name ASC";

        $pQuery = $this->db->query($pSql);
        foreach ($params as $param => $val) {
            $pQuery->bind($param, $val);
        }
        $participants = $pQuery->resultSet();

        // 3. Compute Aggregate Statistics
        $totalAssigned = count($participants);
        $totalSubmitted = 0;
        $totalPassed = 0;
        $highestScore = null;
        $lowestScore = null;
        $sumScore = 0.0;
        $pendingEssayTotal = 0;

        foreach ($participants as $p) {
            if ($p['participant_status'] === 'submitted' && $p['result_id'] !== null) {
                $totalSubmitted++;
                $score = (float)$p['final_score'];
                $sumScore += $score;

                if ($highestScore === null || $score > $highestScore) {
                    $highestScore = $score;
                }
                if ($lowestScore === null || $score < $lowestScore) {
                    $lowestScore = $score;
                }
                if ((int)$p['is_passed'] === 1) {
                    $totalPassed++;
                }
                if ((int)$p['pending_essay_count'] > 0) {
                    $pendingEssayTotal++;
                }
            }
        }

        $avgScore = $totalSubmitted > 0 ? round($sumScore / $totalSubmitted, 2) : 0.00;
        $passingRate = $totalSubmitted > 0 ? round(($totalPassed / $totalSubmitted) * 100, 1) : 0.0;

        $stats = [
            'total_assigned' => $totalAssigned,
            'total_submitted' => $totalSubmitted,
            'total_passed' => $totalPassed,
            'highest_score' => $highestScore !== null ? round($highestScore, 2) : 0.00,
            'lowest_score' => $lowestScore !== null ? round($lowestScore, 2) : 0.00,
            'avg_score' => $avgScore,
            'passing_rate' => $passingRate,
            'pending_essay_total' => $pendingEssayTotal
        ];

        return [
            'exam' => $exam,
            'stats' => $stats,
            'participants' => $participants
        ];
    }

    /**
     * Get complete answer review for a student attempt
     *
     * @param int $participantId
     * @param int|null $teacherId If provided, enforces teacher ownership
     * @return array|null ['participant' => array, 'exam' => array, 'questions' => array]
     */
    public function getAnswerReview(int $participantId, ?int $teacherId = null): ?array
    {
        // 1. Participant and Exam header info
        $sql = "SELECT ep.id AS participant_id, ep.exam_id, ep.student_id, ep.status AS participant_status,
                       ep.start_time AS participant_start_time, ep.submit_time AS participant_submit_time,
                       s.nis, s.nisn, s.gender,
                       u.full_name AS student_name, u.email AS student_email,
                       c.name AS class_name,
                       e.title AS exam_title, e.subject_id, e.teacher_id, e.total_score, e.passing_score, e.show_result,
                       sub.code AS subject_code, sub.name AS subject_name,
                       tu.full_name AS teacher_name,
                       er.final_score, er.is_passed, er.total_correct, er.total_incorrect, er.total_unanswered, er.calculated_at
                FROM exam_participants ep
                JOIN students s ON ep.student_id = s.id
                JOIN users u ON s.user_id = u.id
                JOIN classes c ON s.class_id = c.id
                JOIN exams e ON ep.exam_id = e.id
                JOIN subjects sub ON e.subject_id = sub.id
                JOIN teachers t ON e.teacher_id = t.id
                JOIN users tu ON t.user_id = tu.id
                LEFT JOIN exam_results er ON ep.id = er.exam_participant_id
                WHERE ep.id = :pid " . ($teacherId !== null ? "AND e.teacher_id = :tid " : "") . "
                LIMIT 1";

        $query = $this->db->query($sql)->bind(':pid', $participantId);
        if ($teacherId !== null) {
            $query->bind(':tid', $teacherId);
        }
        $header = $query->single();

        if (!$header) {
            return null;
        }

        // 2. Fetch all questions assigned to this exam
        $qSql = "SELECT eq.question_order, eq.question_score,
                        q.id AS question_id, q.question_text, q.question_type, q.difficulty, q.explanation
                 FROM exam_questions eq
                 JOIN questions q ON eq.question_id = q.id
                 WHERE eq.exam_id = :eid
                 ORDER BY eq.question_order ASC";

        $questions = $this->db->query($qSql)->bind(':eid', $header['exam_id'])->resultSet();

        // 3. Fetch all student answers for this participant
        $aSql = "SELECT ea.question_id, ea.selected_option_id, ea.essay_answer, 
                        ea.is_correct, ea.awarded_score, ea.is_flagged_doubt, ea.answered_at
                 FROM exam_answers ea
                 WHERE ea.exam_participant_id = :pid";

        $answers = $this->db->query($aSql)->bind(':pid', $participantId)->resultSet();
        $answersMap = [];
        foreach ($answers as $a) {
            $answersMap[(int)$a['question_id']] = $a;
        }

        // 4. Attach options and evaluate each question
        foreach ($questions as &$q) {
            $qid = (int)$q['question_id'];
            $ans = $answersMap[$qid] ?? null;
            $q['answer'] = $ans;

            if ($q['question_type'] === 'multiple_choice') {
                $optSql = "SELECT id, option_label, option_text, is_correct
                           FROM question_options
                           WHERE question_id = :qid
                           ORDER BY option_label ASC";

                $options = $this->db->query($optSql)->bind(':qid', $qid)->resultSet();
                $q['options'] = $options;

                // Determine selected option & correct option
                $selectedOptId = $ans ? (int)$ans['selected_option_id'] : null;
                $correctOptId = null;

                foreach ($options as $opt) {
                    if ((int)$opt['is_correct'] === 1) {
                        $correctOptId = (int)$opt['id'];
                    }
                }

                $q['selected_option_id'] = $selectedOptId;
                $q['correct_option_id'] = $correctOptId;
                $q['is_answered'] = ($selectedOptId !== null && $selectedOptId > 0);
            } else {
                // Essay
                $q['options'] = [];
                $q['is_answered'] = ($ans && trim((string)$ans['essay_answer']) !== '');
                $q['essay_status'] = ($ans && $ans['is_correct'] === null) ? 'pending' : 'graded';
            }
        }
        unset($q);

        return [
            'header' => $header,
            'questions' => $questions
        ];
    }

    /**
     * Teacher awards manual grade to student's essay question and updates final scores atomically
     *
     * @param int $participantId
     * @param int $questionId
     * @param float $score
     * @param int $teacherId
     * @return array ['success' => bool, 'message' => string, 'new_final_score' => float, 'is_passed' => int]
     * @throws Exception
     */
    public function gradeEssayAnswer(int $participantId, int $questionId, float $score, int $teacherId): array
    {
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();

        try {
            // 1. Verify exam ownership and question score limit
            $checkSql = "SELECT ep.id, ep.exam_id, ep.status, e.total_score, e.passing_score,
                                eq.question_score, q.question_type
                         FROM exam_participants ep
                         JOIN exams e ON ep.exam_id = e.id
                         JOIN exam_questions eq ON e.id = eq.exam_id AND eq.question_id = :qid
                         JOIN questions q ON eq.question_id = q.id
                         WHERE ep.id = :pid AND e.teacher_id = :tid
                         LIMIT 1";

            $stmt = $pdo->prepare($checkSql);
            $stmt->execute([
                ':pid' => $participantId,
                ':qid' => $questionId,
                ':tid' => $teacherId
            ]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Soal atau ujian tidak ditemukan, atau Anda tidak memiliki hak akses.'];
            }

            if ($row['question_type'] !== 'essay') {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Hanya soal bertipe essay yang dapat dinilai secara manual.'];
            }

            $maxScore = (float)$row['question_score'];
            if ($score < 0 || $score > $maxScore) {
                $pdo->rollBack();
                return ['success' => false, 'message' => "Nilai harus berada di antara 0 dan bobot maksimum ({$maxScore} poin)."];
            }

            // 2. Update exam_answers record
            // is_correct: 1 if score > 0, 0 if score == 0 (graded)
            $isCorrect = ($score > 0) ? 1 : 0;

            $upAnsStmt = $pdo->prepare("
                UPDATE exam_answers
                SET awarded_score = :score,
                    is_correct = :corr,
                    answered_at = IFNULL(answered_at, NOW())
                WHERE exam_participant_id = :pid AND question_id = :qid
            ");
            $upAnsStmt->execute([
                ':score' => round($score, 2),
                ':corr' => $isCorrect,
                ':pid' => $participantId,
                ':qid' => $questionId
            ]);

            // 3. Recalculate participant's exam_results
            $scoreSumStmt = $pdo->prepare("
                SELECT SUM(awarded_score) AS total_score,
                       SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) AS count_correct,
                       SUM(CASE WHEN is_correct = 0 THEN 1 ELSE 0 END) AS count_incorrect,
                       SUM(CASE WHEN selected_option_id IS NULL AND (essay_answer IS NULL OR TRIM(essay_answer) = '') THEN 1 ELSE 0 END) AS count_unanswered
                FROM exam_answers
                WHERE exam_participant_id = :pid
            ");
            $scoreSumStmt->execute([':pid' => $participantId]);
            $calc = $scoreSumStmt->fetch(PDO::FETCH_ASSOC);

            $newFinalScore = round((float)($calc['total_score'] ?? 0), 2);
            $countCorrect = (int)($calc['count_correct'] ?? 0);
            $countIncorrect = (int)($calc['count_incorrect'] ?? 0);
            $countUnanswered = (int)($calc['count_unanswered'] ?? 0);
            $isPassed = ($newFinalScore >= (float)$row['passing_score']) ? 1 : 0;

            $upResStmt = $pdo->prepare("
                INSERT INTO exam_results (exam_participant_id, total_correct, total_incorrect, total_unanswered, final_score, is_passed, calculated_at)
                VALUES (:pid, :tc, :ti, :tu, :fs, :pass, NOW())
                ON DUPLICATE KEY UPDATE
                    total_correct = VALUES(total_correct),
                    total_incorrect = VALUES(total_incorrect),
                    total_unanswered = VALUES(total_unanswered),
                    final_score = VALUES(final_score),
                    is_passed = VALUES(is_passed),
                    calculated_at = NOW()
            ");
            $upResStmt->execute([
                ':pid' => $participantId,
                ':tc' => $countCorrect,
                ':ti' => $countIncorrect,
                ':tu' => $countUnanswered,
                ':fs' => $newFinalScore,
                ':pass' => $isPassed
            ]);

            $pdo->commit();

            return [
                'success' => true,
                'message' => 'Nilai essay berhasil disimpan dan nilai akhir peserta telah diperbarui.',
                'new_final_score' => $newFinalScore,
                'is_passed' => $isPassed,
                'awarded_score' => round($score, 2)
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Toggle or set result visibility to students for an exam
     *
     * @param int $examId
     * @param int $teacherId
     * @param int|null $forcedValue
     * @return array ['success' => bool, 'show_result' => int, 'message' => string]
     */
    public function toggleResultVisibility(int $examId, int $teacherId, ?int $forcedValue = null): array
    {
        $exam = $this->db->query("SELECT id, show_result FROM exams WHERE id = :eid AND teacher_id = :tid LIMIT 1")
            ->bind(':eid', $examId)
            ->bind(':tid', $teacherId)
            ->single();

        if (!$exam) {
            return ['success' => false, 'message' => 'Ujian tidak ditemukan atau Anda tidak memiliki hak akses.'];
        }

        $newValue = ($forcedValue !== null) ? (int)$forcedValue : (1 - (int)$exam['show_result']);

        $this->db->query("UPDATE exams SET show_result = :val WHERE id = :eid")
            ->bind(':val', $newValue)
            ->bind(':eid', $examId)
            ->execute();

        $statusLabel = ($newValue === 1) ? 'dipublikasikan ke peserta' : 'disembunyikan dari peserta';

        return [
            'success' => true,
            'show_result' => $newValue,
            'message' => "Hasil ujian berhasil {$statusLabel}."
        ];
    }

    /**
     * Get school-wide exam results overview for Super Admin
     *
     * @param string $search
     * @return array ['stats' => array, 'exams' => array]
     */
    public function getAdminResultsOverview(string $search = ''): array
    {
        $params = [];
        $where = ["e.status IN ('published', 'running', 'finished')"];

        if (!empty($search)) {
            $where[] = "(e.title LIKE :s1 OR s.name LIKE :s2 OR c.name LIKE :s3 OR u.full_name LIKE :s4)";
            $params[':s1'] = '%' . $search . '%';
            $params[':s2'] = '%' . $search . '%';
            $params[':s3'] = '%' . $search . '%';
            $params[':s4'] = '%' . $search . '%';
        }

        $whereClause = "WHERE " . implode(' AND ', $where);

        $sql = "SELECT e.id AS exam_id, e.title, e.status AS exam_status, e.start_time, e.end_time,
                       e.total_score, e.passing_score, e.show_result,
                       s.code AS subject_code, s.name AS subject_name,
                       c.name AS class_name,
                       u.full_name AS teacher_name,
                       COUNT(DISTINCT ep.id) AS total_assigned,
                       SUM(CASE WHEN ep.status = 'submitted' THEN 1 ELSE 0 END) AS total_submitted,
                       MAX(er.final_score) AS highest_score,
                       MIN(er.final_score) AS lowest_score,
                       AVG(er.final_score) AS avg_score,
                       SUM(CASE WHEN er.is_passed = 1 THEN 1 ELSE 0 END) AS total_passed
                FROM exams e
                JOIN subjects s ON e.subject_id = s.id
                JOIN classes c ON e.class_id = c.id
                JOIN teachers t ON e.teacher_id = t.id
                JOIN users u ON t.user_id = u.id
                LEFT JOIN exam_participants ep ON e.id = ep.exam_id
                LEFT JOIN exam_results er ON ep.id = er.exam_participant_id
                {$whereClause}
                GROUP BY e.id
                ORDER BY e.id DESC";

        $query = $this->db->query($sql);
        foreach ($params as $p => $v) {
            $query->bind($p, $v);
        }
        $exams = $query->resultSet();

        // Calculate overall global statistics
        $totalExams = count($exams);
        $totalSubmissions = 0;
        $totalPassedGlobal = 0;
        $globalScoreSum = 0.0;
        $globalCountWithScore = 0;

        foreach ($exams as &$ex) {
            $subCount = (int)$ex['total_submitted'];
            $totalSubmissions += $subCount;
            $passCount = (int)$ex['total_passed'];
            $totalPassedGlobal += $passCount;

            $ex['total_assigned'] = (int)$ex['total_assigned'];
            $ex['total_submitted'] = $subCount;
            $ex['total_passed'] = $passCount;
            $ex['highest_score'] = $ex['highest_score'] !== null ? round((float)$ex['highest_score'], 2) : 0.00;
            $ex['lowest_score'] = $ex['lowest_score'] !== null ? round((float)$ex['lowest_score'], 2) : 0.00;
            $ex['avg_score'] = $ex['avg_score'] !== null ? round((float)$ex['avg_score'], 2) : 0.00;
            $ex['passing_rate'] = $subCount > 0 ? round(($passCount / $subCount) * 100, 1) : 0.0;

            if ($ex['avg_score'] > 0 && $subCount > 0) {
                $globalScoreSum += ($ex['avg_score'] * $subCount);
                $globalCountWithScore += $subCount;
            }
        }
        unset($ex);

        $schoolAvgScore = $globalCountWithScore > 0 ? round($globalScoreSum / $globalCountWithScore, 2) : 0.00;
        $schoolPassingRate = $totalSubmissions > 0 ? round(($totalPassedGlobal / $totalSubmissions) * 100, 1) : 0.0;

        $stats = [
            'total_exams' => $totalExams,
            'total_submissions' => $totalSubmissions,
            'school_avg_score' => $schoolAvgScore,
            'school_passing_rate' => $schoolPassingRate
        ];

        return [
            'stats' => $stats,
            'exams' => $exams
        ];
    }

    /**
     * Check if a student has permission to view exam results based on show_result flag
     *
     * @param int $examId
     * @param int $studentId
     * @return array ['allowed' => bool, 'show_result' => int, 'message' => string, 'exam' => array|null]
     */
    public function checkStudentResultVisibility(int $examId, int $studentId): array
    {
        $sql = "SELECT e.id AS exam_id, e.title, e.show_result, ep.id AS participant_id, ep.status AS participant_status
                FROM exams e
                JOIN exam_participants ep ON e.id = ep.exam_id
                WHERE e.id = :eid AND ep.student_id = :sid
                LIMIT 1";

        $row = $this->db->query($sql)
            ->bind(':eid', $examId)
            ->bind(':sid', $studentId)
            ->single();

        if (!$row) {
            return [
                'allowed' => false,
                'show_result' => 0,
                'message' => 'Anda tidak terdaftar pada sesi ujian ini.',
                'exam' => null
            ];
        }

        if ($row['participant_status'] !== 'submitted') {
            return [
                'allowed' => false,
                'show_result' => (int)($row['show_result'] ?? 1),
                'message' => 'Anda belum menyelesaikan dan mengumpulkan ujian ini.',
                'exam' => $row
            ];
        }

        $showResult = (int)($row['show_result'] ?? 1);

        if ($showResult === 0) {
            return [
                'allowed' => false,
                'show_result' => 0,
                'message' => 'Hasil ujian ini belum dipublikasikan oleh guru penguji.',
                'exam' => $row
            ];
        }

        return [
            'allowed' => true,
            'show_result' => 1,
            'message' => 'Hasil ujian tersedia.',
            'exam' => $row
        ];
    }
}
