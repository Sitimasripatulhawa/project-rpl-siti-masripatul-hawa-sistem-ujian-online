<?php
/**
 * Teacher Model (Data Guru)
 * AI Online Exam & Proctoring System
 */

class TeacherModel extends Model
{
    /**
     * Get all teachers with user details and question counts
     *
     * @param string $search
     * @return array
     */
    public function getAll(string $search = ''): array
    {
        $params = [];
        $where = "";

        if (!empty($search)) {
            $where = "WHERE (u.full_name LIKE :s1 OR u.email LIKE :s2 OR u.username LIKE :s3 OR t.nip LIKE :s4 OR t.specialization LIKE :s5)";
            $params[':s1'] = '%' . $search . '%';
            $params[':s2'] = '%' . $search . '%';
            $params[':s3'] = '%' . $search . '%';
            $params[':s4'] = '%' . $search . '%';
            $params[':s5'] = '%' . $search . '%';
        }

        $sql = "SELECT t.id, t.user_id, t.nip, t.specialization, t.created_at,
                       u.username, u.email, u.full_name, u.phone, u.is_active,
                       COUNT(q.id) AS total_questions,
                       COUNT(DISTINCT e.id) AS total_exams 
                FROM teachers t 
                JOIN users u ON t.user_id = u.id 
                LEFT JOIN questions q ON t.id = q.teacher_id 
                LEFT JOIN exams e ON t.id = e.teacher_id 
                {$where} 
                GROUP BY t.id 
                ORDER BY u.full_name ASC";

        $query = $this->db->query($sql);
        foreach ($params as $param => $val) {
            $query->bind($param, $val);
        }

        return $query->resultSet();
    }

    /**
     * Find teacher by ID with user details
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT t.id, t.user_id, t.nip, t.specialization, t.created_at, t.updated_at,
                       u.username, u.email, u.full_name, u.phone, u.is_active,
                       COUNT(q.id) AS total_questions 
                FROM teachers t 
                JOIN users u ON t.user_id = u.id 
                LEFT JOIN questions q ON t.id = q.teacher_id 
                WHERE t.id = :id 
                GROUP BY t.id 
                LIMIT 1";

        return $this->db->query($sql)
            ->bind(':id', $id)
            ->single();
    }

    /**
     * Check if NIP is already taken
     */
    public function isNipTaken(string $nip, ?int $excludeId = null): bool
    {
        $sql = "SELECT id FROM teachers WHERE nip = :nip";
        if ($excludeId !== null) {
            $sql .= " AND id != :exId";
        }
        $sql .= " LIMIT 1";

        $q = $this->db->query($sql)->bind(':nip', $nip);
        if ($excludeId !== null) {
            $q->bind(':exId', $excludeId);
        }
        return !empty($q->single());
    }

    /**
     * Create teacher record atomically with user account
     *
     * @param array $userData
     * @param array $teacherData
     * @return int Teacher ID
     * @throws Exception
     */
    public function createTeacherWithUser(array $userData, array $teacherData): int
    {
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();

        try {
            // 1. Create User
            $passwordHash = password_hash($userData['password'], PASSWORD_BCRYPT);
            $userStmt = $pdo->prepare("
                INSERT INTO users (username, email, password, role, full_name, phone, is_active) 
                VALUES (:uname, :email, :pass, 'teacher', :fname, :phone, :active)
            ");
            $userStmt->execute([
                ':uname' => $userData['username'],
                ':email' => $userData['email'],
                ':pass' => $passwordHash,
                ':fname' => $userData['full_name'],
                ':phone' => $userData['phone'] ?? null,
                ':active' => isset($userData['is_active']) ? (int)$userData['is_active'] : 1
            ]);
            $userId = (int)$pdo->lastInsertId();

            // 2. Create Teacher Profile
            $teacherStmt = $pdo->prepare("
                INSERT INTO teachers (user_id, nip, specialization) 
                VALUES (:uid, :nip, :spec)
            ");
            $teacherStmt->execute([
                ':uid' => $userId,
                ':nip' => $teacherData['nip'],
                ':spec' => $teacherData['specialization'] ?? null
            ]);
            $teacherId = (int)$pdo->lastInsertId();

            $pdo->commit();
            return $teacherId;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Update teacher and user record atomically
     *
     * @param int $teacherId
     * @param array $userData
     * @param array $teacherData
     * @return bool
     * @throws Exception
     */
    public function updateTeacherWithUser(int $teacherId, array $userData, array $teacherData): bool
    {
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();

        try {
            $teacher = $this->findById($teacherId);
            if (!$teacher) {
                throw new Exception("Data guru tidak ditemukan.");
            }
            $userId = $teacher['user_id'];

            // 1. Update User
            $userFields = "username = :uname, email = :email, full_name = :fname, phone = :phone, is_active = :active";
            $userParams = [
                ':uname' => $userData['username'],
                ':email' => $userData['email'],
                ':fname' => $userData['full_name'],
                ':phone' => $userData['phone'] ?? null,
                ':active' => isset($userData['is_active']) ? (int)$userData['is_active'] : 1,
                ':uid' => $userId
            ];

            if (!empty($userData['password'])) {
                $userFields .= ", password = :pass";
                $userParams[':pass'] = password_hash($userData['password'], PASSWORD_BCRYPT);
            }

            $userStmt = $pdo->prepare("UPDATE users SET {$userFields} WHERE id = :uid");
            $userStmt->execute($userParams);

            // 2. Update Teacher Profile
            $teacherStmt = $pdo->prepare("UPDATE teachers SET nip = :nip, specialization = :spec WHERE id = :tid");
            $teacherStmt->execute([
                ':nip' => $teacherData['nip'],
                ':spec' => $teacherData['specialization'] ?? null,
                ':tid' => $teacherId
            ]);

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Delete teacher and cascading user
     *
     * @param int $id
     * @return bool
     */
    public function deleteTeacher(int $id): bool
    {
        $teacher = $this->findById($id);
        if (!$teacher) return false;

        // Deleting user will cascade delete teacher due to FK constraint
        $sql = "DELETE FROM users WHERE id = :uid";
        return $this->db->query($sql)->bind(':uid', $teacher['user_id'])->execute();
    }
}
