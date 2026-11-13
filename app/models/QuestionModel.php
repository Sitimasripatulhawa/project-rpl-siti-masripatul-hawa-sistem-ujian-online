<?php
/**
 * Question Model (Bank Soal)
 * AI Online Exam & Proctoring System
 */

class QuestionModel extends Model
{
    /**
     * Get questions owned by teacher with search and filters
     *
     * @param int $teacherId
     * @param string $search
     * @param int|null $subjectId
     * @param string $type
     * @param string $difficulty
     * @return array
     */
    public function getByTeacher(int $teacherId, string $search = '', ?int $subjectId = null, string $type = '', string $difficulty = ''): array
    {
        $params = [':tid' => $teacherId];
        $where = ["q.teacher_id = :tid"];

        if (!empty($search)) {
            $where[] = "(q.question_text LIKE :s1 OR q.explanation LIKE :s2 OR s.name LIKE :s3)";
            $params[':s1'] = '%' . $search . '%';
            $params[':s2'] = '%' . $search . '%';
            $params[':s3'] = '%' . $search . '%';
        }

        if (!empty($subjectId)) {
            $where[] = "q.subject_id = :sid";
            $params[':sid'] = $subjectId;
        }

        if (!empty($type)) {
            $where[] = "q.question_type = :qtype";
            $params[':qtype'] = $type;
        }

        if (!empty($difficulty)) {
            $where[] = "q.difficulty = :diff";
            $params[':diff'] = $difficulty;
        }

        $whereClause = "WHERE " . implode(' AND ', $where);

        $sql = "SELECT q.id, q.subject_id, q.teacher_id, q.question_text, q.question_type, 
                       q.difficulty, q.default_score, q.explanation, q.created_at, q.updated_at,
                       s.code AS subject_code, s.name AS subject_name,
                       COUNT(o.id) AS total_options 
                FROM questions q 
                JOIN subjects s ON q.subject_id = s.id 
                LEFT JOIN question_options o ON q.id = o.question_id 
                {$whereClause} 
                GROUP BY q.id 
                ORDER BY q.id DESC";

        $query = $this->db->query($sql);
        foreach ($params as $param => $val) {
            $query->bind($param, $val);
        }

        return $query->resultSet();
    }

    /**
     * Find question by ID and verify teacher ownership
     *
     * @param int $id
     * @param int $teacherId
     * @return array|null
     */
    public function findByIdAndTeacher(int $id, int $teacherId): ?array
    {
        $sql = "SELECT q.id, q.subject_id, q.teacher_id, q.question_text, q.question_type, 
                       q.difficulty, q.default_score, q.explanation, q.created_at, q.updated_at,
                       s.code AS subject_code, s.name AS subject_name 
                FROM questions q 
                JOIN subjects s ON q.subject_id = s.id 
                WHERE q.id = :qid AND q.teacher_id = :tid 
                LIMIT 1";

        $question = $this->db->query($sql)
            ->bind(':qid', $id)
            ->bind(':tid', $teacherId)
            ->single();

        if (!$question) {
            return null;
        }

        // Fetch options if multiple choice
        $options = [];
        if ($question['question_type'] === 'multiple_choice') {
            $optSql = "SELECT id, option_label, option_text, is_correct 
                       FROM question_options 
                       WHERE question_id = :qid 
                       ORDER BY option_label ASC";
            $options = $this->db->query($optSql)
                ->bind(':qid', $id)
                ->resultSet();
        }

        $question['options'] = $options;
        return $question;
    }

    /**
     * Count questions owned by teacher
     *
     * @param int $teacherId
     * @return int
     */
    public function countByTeacher(int $teacherId): int
    {
        $sql = "SELECT COUNT(*) AS total FROM questions WHERE teacher_id = :tid";
        $res = $this->db->query($sql)->bind(':tid', $teacherId)->single();
        return (int)($res['total'] ?? 0);
    }

    /**
     * Create a new question with options atomically
     *
     * @param int $teacherId
     * @param array $data
     * @return int Question ID
     * @throws Exception
     */
    public function createQuestion(int $teacherId, array $data): int
    {
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();

        try {
            // 1. Insert question record
            $sql = "INSERT INTO questions (subject_id, teacher_id, question_text, question_type, difficulty, default_score, explanation) 
                    VALUES (:sid, :tid, :qtext, :qtype, :diff, :score, :expl)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':sid' => (int)$data['subject_id'],
                ':tid' => $teacherId,
                ':qtext' => trim($data['question_text']),
                ':qtype' => $data['question_type'] ?? 'multiple_choice',
                ':diff' => $data['difficulty'] ?? 'medium',
                ':score' => !empty($data['default_score']) ? (float)$data['default_score'] : 1.00,
                ':expl' => trim($data['explanation'] ?? '')
            ]);

            $questionId = (int)$pdo->lastInsertId();

            // 2. If multiple choice, insert options
            if (($data['question_type'] ?? 'multiple_choice') === 'multiple_choice' && !empty($data['options'])) {
                $correctOption = $data['correct_option'] ?? 'A';
                $optStmt = $pdo->prepare("
                    INSERT INTO question_options (question_id, option_label, option_text, is_correct) 
                    VALUES (:qid, :label, :text, :is_correct)
                ");

                foreach ($data['options'] as $label => $text) {
                    if (trim($text) === '') continue; // Skip empty optional labels (like E)
                    $isCorrect = (strtoupper(trim($label)) === strtoupper(trim($correctOption))) ? 1 : 0;
                    $optStmt->execute([
                        ':qid' => $questionId,
                        ':label' => strtoupper(trim($label)),
                        ':text' => trim($text),
                        ':is_correct' => $isCorrect
                    ]);
                }
            }

            $pdo->commit();
            return $questionId;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Update existing question and its options atomically
     *
     * @param int $questionId
     * @param int $teacherId
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function updateQuestion(int $questionId, int $teacherId, array $data): bool
    {
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();

        try {
            // Verify ownership first
            $checkStmt = $pdo->prepare("SELECT id, question_type FROM questions WHERE id = :qid AND teacher_id = :tid LIMIT 1");
            $checkStmt->execute([':qid' => $questionId, ':tid' => $teacherId]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$existing) {
                throw new Exception("Soal tidak ditemukan atau Anda tidak memiliki akses kepemilikan.");
            }

            // 1. Update question record
            $sql = "UPDATE questions 
                    SET subject_id = :sid, question_text = :qtext, question_type = :qtype, 
                        difficulty = :diff, default_score = :score, explanation = :expl 
                    WHERE id = :qid AND teacher_id = :tid";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':sid' => (int)$data['subject_id'],
                ':qtext' => trim($data['question_text']),
                ':qtype' => $data['question_type'] ?? 'multiple_choice',
                ':diff' => $data['difficulty'] ?? 'medium',
                ':score' => !empty($data['default_score']) ? (float)$data['default_score'] : 1.00,
                ':expl' => trim($data['explanation'] ?? ''),
                ':qid' => $questionId,
                ':tid' => $teacherId
            ]);

            // 2. Refresh options for multiple choice
            if (($data['question_type'] ?? 'multiple_choice') === 'multiple_choice' && !empty($data['options'])) {
                // Remove previous options
                $delStmt = $pdo->prepare("DELETE FROM question_options WHERE question_id = :qid");
                $delStmt->execute([':qid' => $questionId]);

                $correctOption = $data['correct_option'] ?? 'A';
                $optStmt = $pdo->prepare("
                    INSERT INTO question_options (question_id, option_label, option_text, is_correct) 
                    VALUES (:qid, :label, :text, :is_correct)
                ");

                foreach ($data['options'] as $label => $text) {
                    if (trim($text) === '') continue;
                    $isCorrect = (strtoupper(trim($label)) === strtoupper(trim($correctOption))) ? 1 : 0;
                    $optStmt->execute([
                        ':qid' => $questionId,
                        ':label' => strtoupper(trim($label)),
                        ':text' => trim($text),
                        ':is_correct' => $isCorrect
                    ]);
                }
            } elseif (($data['question_type'] ?? '') === 'essay') {
                // If changed to essay, remove any remaining options
                $delStmt = $pdo->prepare("DELETE FROM question_options WHERE question_id = :qid");
                $delStmt->execute([':qid' => $questionId]);
            }

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Delete question owned by teacher
     *
     * @param int $questionId
     * @param int $teacherId
     * @return bool
     */
    public function deleteQuestion(int $questionId, int $teacherId): bool
    {
        $sql = "DELETE FROM questions WHERE id = :qid AND teacher_id = :tid";
        return $this->db->query($sql)
            ->bind(':qid', $questionId)
            ->bind(':tid', $teacherId)
            ->execute();
    }
}
