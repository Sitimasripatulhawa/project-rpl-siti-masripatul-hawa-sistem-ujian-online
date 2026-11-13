<?php
/**
 * Exam Model (Manajemen Ujian)
 * AI Online Exam & Proctoring System
 */

class ExamModel extends Model
{
    /**
     * Get all exams owned by teacher with search and filters
     *
     * @param int $teacherId
     * @param string $search
     * @param int|null $subjectId
     * @param string $status
     * @return array
     */
    public function getByTeacher(int $teacherId, string $search = '', ?int $subjectId = null, string $status = ''): array
    {
        $params = [':tid' => $teacherId];
        $where = ["e.teacher_id = :tid"];

        if (!empty($search)) {
            $where[] = "(e.title LIKE :s1 OR e.description LIKE :s2 OR s.name LIKE :s3 OR c.name LIKE :s4)";
            $params[':s1'] = '%' . $search . '%';
            $params[':s2'] = '%' . $search . '%';
            $params[':s3'] = '%' . $search . '%';
            $params[':s4'] = '%' . $search . '%';
        }

        if (!empty($subjectId)) {
            $where[] = "e.subject_id = :sid";
            $params[':sid'] = $subjectId;
        }

        if (!empty($status)) {
            $where[] = "e.status = :status";
            $params[':status'] = $status;
        }

        $whereClause = "WHERE " . implode(' AND ', $where);

        $sql = "SELECT e.id, e.title, e.description, e.subject_id, e.teacher_id, e.class_id,
                       e.duration_minutes, e.start_time, e.end_time, e.token,
                       e.is_random_question, e.is_random_option, e.enable_proctoring,
                       e.status, e.total_score, e.passing_score, e.created_at, e.updated_at,
                       s.code AS subject_code, s.name AS subject_name,
                       c.name AS class_name, c.level AS class_level,
                       COUNT(DISTINCT eq.question_id) AS total_questions,
                       COUNT(DISTINCT ep.student_id) AS total_participants 
                FROM exams e 
                JOIN subjects s ON e.subject_id = s.id 
                JOIN classes c ON e.class_id = c.id 
                LEFT JOIN exam_questions eq ON e.id = eq.exam_id 
                LEFT JOIN exam_participants ep ON e.id = ep.exam_id 
                {$whereClause} 
                GROUP BY e.id 
                ORDER BY e.id DESC";

        $query = $this->db->query($sql);
        foreach ($params as $param => $val) {
            $query->bind($param, $val);
        }

        return $query->resultSet();
    }

    /**
     * Find exam by ID and teacher, including assigned questions and participants
     *
     * @param int $id
     * @param int $teacherId
     * @return array|null
     */
    public function findByIdAndTeacher(int $id, int $teacherId): ?array
    {
        $sql = "SELECT e.id, e.title, e.description, e.subject_id, e.teacher_id, e.class_id,
                       e.duration_minutes, e.start_time, e.end_time, e.token,
                       e.is_random_question, e.is_random_option, e.enable_proctoring,
                       e.status, e.total_score, e.passing_score, e.created_at, e.updated_at,
                       s.code AS subject_code, s.name AS subject_name,
                       c.name AS class_name, c.level AS class_level, c.academic_year 
                FROM exams e 
                JOIN subjects s ON e.subject_id = s.id 
                JOIN classes c ON e.class_id = c.id 
                WHERE e.id = :eid AND e.teacher_id = :tid 
                LIMIT 1";

        $exam = $this->db->query($sql)
            ->bind(':eid', $id)
            ->bind(':tid', $teacherId)
            ->single();

        if (!$exam) {
            return null;
        }

        // Fetch assigned questions with options
        $qSql = "SELECT eq.question_order, eq.question_score,
                        q.id, q.question_text, q.question_type, q.difficulty, q.explanation 
                 FROM exam_questions eq 
                 JOIN questions q ON eq.question_id = q.id 
                 WHERE eq.exam_id = :eid 
                 ORDER BY eq.question_order ASC";

        $questions = $this->db->query($qSql)->bind(':eid', $id)->resultSet();

        foreach ($questions as &$q) {
            if ($q['question_type'] === 'multiple_choice') {
                $optSql = "SELECT id, option_label, option_text, is_correct 
                           FROM question_options 
                           WHERE question_id = :qid 
                           ORDER BY option_label ASC";
                $q['options'] = $this->db->query($optSql)->bind(':qid', $q['id'])->resultSet();
            } else {
                $q['options'] = [];
            }
        }
        unset($q);

        // Fetch participants from this class/exam
        $pSql = "SELECT ep.id AS participant_id, ep.status, ep.start_time, ep.submit_time, ep.risk_score,
                        s.id AS student_id, s.nis, s.nisn, u.full_name, u.email 
                 FROM exam_participants ep 
                 JOIN students s ON ep.student_id = s.id 
                 JOIN users u ON s.user_id = u.id 
                 WHERE ep.exam_id = :eid 
                 ORDER BY u.full_name ASC";

        $participants = $this->db->query($pSql)->bind(':eid', $id)->resultSet();

        $exam['questions'] = $questions;
        $exam['participants'] = $participants;

        return $exam;
    }

    /**
     * Create exam with assigned questions and class students atomically
     *
     * @param int $teacherId
     * @param array $examData
     * @param array $questionIds
     * @param int $classId
     * @return int Exam ID
     * @throws Exception
     */
    public function createExamWithQuestions(int $teacherId, array $examData, array $questionIds, int $classId): int
    {
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();

        try {
            // Generate 6-character unique exam token if empty
            $token = !empty($examData['token']) ? strtoupper(trim($examData['token'])) : strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 6));

            // 1. Insert Exam record
            $sql = "INSERT INTO exams (title, description, subject_id, teacher_id, class_id, duration_minutes, 
                                      start_time, end_time, token, is_random_question, is_random_option, 
                                      enable_proctoring, status, total_score, passing_score) 
                    VALUES (:title, :desc, :sid, :tid, :cid, :duration, :start, :end, :token, 
                            :rq, :ro, :ep, :status, :tscore, :pscore)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':title' => trim($examData['title']),
                ':desc' => trim($examData['description'] ?? ''),
                ':sid' => (int)$examData['subject_id'],
                ':tid' => $teacherId,
                ':cid' => $classId,
                ':duration' => (int)$examData['duration_minutes'],
                ':start' => $examData['start_time'],
                ':end' => $examData['end_time'],
                ':token' => $token,
                ':rq' => !empty($examData['is_random_question']) ? 1 : 0,
                ':ro' => !empty($examData['is_random_option']) ? 1 : 0,
                ':ep' => !empty($examData['enable_proctoring']) ? 1 : 0,
                ':status' => $examData['status'] ?? 'draft',
                ':tscore' => !empty($examData['total_score']) ? (float)$examData['total_score'] : 100.00,
                ':pscore' => !empty($examData['passing_score']) ? (float)$examData['passing_score'] : 75.00
            ]);

            $examId = (int)$pdo->lastInsertId();

            // 2. Insert assigned questions into exam_questions
            $eqStmt = $pdo->prepare("
                INSERT INTO exam_questions (exam_id, question_id, question_order, question_score) 
                VALUES (:eid, :qid, :order, :score)
            ");

            // Determine score per question
            $totalQuestions = count($questionIds);
            $scorePerQuestion = $totalQuestions > 0 ? (100.00 / $totalQuestions) : 0;

            $order = 1;
            foreach ($questionIds as $qid) {
                $eqStmt->execute([
                    ':eid' => $examId,
                    ':qid' => (int)$qid,
                    ':order' => $order++,
                    ':score' => round($scorePerQuestion, 2)
                ]);
            }

            // 3. Assign all students from class into exam_participants
            $studentsStmt = $pdo->prepare("SELECT id FROM students WHERE class_id = :cid");
            $studentsStmt->execute([':cid' => $classId]);
            $students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

            $epStmt = $pdo->prepare("
                INSERT INTO exam_participants (exam_id, student_id, status) 
                VALUES (:eid, :sid, 'not_started')
            ");

            foreach ($students as $s) {
                $epStmt->execute([
                    ':eid' => $examId,
                    ':sid' => (int)$s['id']
                ]);
            }

            $pdo->commit();
            return $examId;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Update exam and sync assigned questions & participants
     *
     * @param int $examId
     * @param int $teacherId
     * @param array $examData
     * @param array $questionIds
     * @param int $classId
     * @return bool
     * @throws Exception
     */
    public function updateExamWithQuestions(int $examId, int $teacherId, array $examData, array $questionIds, int $classId): bool
    {
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();

        try {
            // Verify ownership
            $checkStmt = $pdo->prepare("SELECT id, class_id FROM exams WHERE id = :eid AND teacher_id = :tid LIMIT 1");
            $checkStmt->execute([':eid' => $examId, ':tid' => $teacherId]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$existing) {
                throw new Exception("Ujian tidak ditemukan atau Anda tidak memiliki hak akses.");
            }

            // 1. Update exam fields
            $sql = "UPDATE exams 
                    SET title = :title, description = :desc, subject_id = :sid, class_id = :cid,
                        duration_minutes = :duration, start_time = :start, end_time = :end,
                        is_random_question = :rq, is_random_option = :ro, enable_proctoring = :ep,
                        status = :status, total_score = :tscore, passing_score = :pscore 
                    WHERE id = :eid AND teacher_id = :tid";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':title' => trim($examData['title']),
                ':desc' => trim($examData['description'] ?? ''),
                ':sid' => (int)$examData['subject_id'],
                ':cid' => $classId,
                ':duration' => (int)$examData['duration_minutes'],
                ':start' => $examData['start_time'],
                ':end' => $examData['end_time'],
                ':rq' => !empty($examData['is_random_question']) ? 1 : 0,
                ':ro' => !empty($examData['is_random_option']) ? 1 : 0,
                ':ep' => !empty($examData['enable_proctoring']) ? 1 : 0,
                ':status' => $examData['status'] ?? 'draft',
                ':tscore' => !empty($examData['total_score']) ? (float)$examData['total_score'] : 100.00,
                ':pscore' => !empty($examData['passing_score']) ? (float)$examData['passing_score'] : 75.00,
                ':eid' => $examId,
                ':tid' => $teacherId
            ]);

            // 2. Refresh assigned questions
            $delEqStmt = $pdo->prepare("DELETE FROM exam_questions WHERE exam_id = :eid");
            $delEqStmt->execute([':eid' => $examId]);

            $eqStmt = $pdo->prepare("
                INSERT INTO exam_questions (exam_id, question_id, question_order, question_score) 
                VALUES (:eid, :qid, :order, :score)
            ");

            $totalQuestions = count($questionIds);
            $scorePerQuestion = $totalQuestions > 0 ? (100.00 / $totalQuestions) : 0;

            $order = 1;
            foreach ($questionIds as $qid) {
                $eqStmt->execute([
                    ':eid' => $examId,
                    ':qid' => (int)$qid,
                    ':order' => $order++,
                    ':score' => round($scorePerQuestion, 2)
                ]);
            }

            // 3. If class changed, sync participants
            if ((int)$existing['class_id'] !== $classId) {
                $delEpStmt = $pdo->prepare("DELETE FROM exam_participants WHERE exam_id = :eid AND status = 'not_started'");
                $delEpStmt->execute([':eid' => $examId]);

                $studentsStmt = $pdo->prepare("SELECT id FROM students WHERE class_id = :cid");
                $studentsStmt->execute([':cid' => $classId]);
                $students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

                $epStmt = $pdo->prepare("
                    INSERT IGNORE INTO exam_participants (exam_id, student_id, status) 
                    VALUES (:eid, :sid, 'not_started')
                ");

                foreach ($students as $s) {
                    $epStmt->execute([
                        ':eid' => $examId,
                        ':sid' => (int)$s['id']
                    ]);
                }
            }

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Delete exam owned by teacher
     */
    public function deleteExam(int $examId, int $teacherId): bool
    {
        $sql = "DELETE FROM exams WHERE id = :eid AND teacher_id = :tid";
        return $this->db->query($sql)
            ->bind(':eid', $examId)
            ->bind(':tid', $teacherId)
            ->execute();
    }

    /**
     * Update exam status (e.g. publish/unpublish)
     */
    public function updateStatus(int $examId, int $teacherId, string $newStatus): bool
    {
        $sql = "UPDATE exams SET status = :status WHERE id = :eid AND teacher_id = :tid";
        return $this->db->query($sql)
            ->bind(':status', $newStatus)
            ->bind(':eid', $examId)
            ->bind(':tid', $teacherId)
            ->execute();
    }

    /**
     * Count exams grouped by 4 statuses for teacher dashboard
     *
     * @param int $teacherId
     * @return array [ 'draft' => N, 'published' => N, 'running' => N, 'finished' => N, 'total' => N ]
     */
    public function countByTeacherGroupedStatus(int $teacherId): array
    {
        $sql = "SELECT status, COUNT(*) AS count 
                FROM exams 
                WHERE teacher_id = :tid 
                GROUP BY status";

        $rows = $this->db->query($sql)->bind(':tid', $teacherId)->resultSet();

        $stats = [
            'draft' => 0,
            'published' => 0,
            'running' => 0,
            'finished' => 0,
            'total' => 0
        ];

        foreach ($rows as $r) {
            $st = $r['status'];
            if (isset($stats[$st])) {
                $stats[$st] = (int)$r['count'];
            }
            $stats['total'] += (int)$r['count'];
        }

        return $stats;
    }
}
