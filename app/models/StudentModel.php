<?php
/**
 * Student Model (Data Peserta Didik)
 * AI Online Exam & Proctoring System
 */

class StudentModel extends Model
{
    /**
     * Get all students with class and user details
     *
     * @param string $search
     * @param int|null $classId
     * @return array
     */
    public function getAll(string $search = '', ?int $classId = null): array
    {
        $params = [];
        $where = [];

        if (!empty($search)) {
            $where[] = "(u.full_name LIKE :s1 OR u.email LIKE :s2 OR u.username LIKE :s3 OR s.nis LIKE :s4 OR s.nisn LIKE :s5)";
            $params[':s1'] = '%' . $search . '%';
            $params[':s2'] = '%' . $search . '%';
            $params[':s3'] = '%' . $search . '%';
            $params[':s4'] = '%' . $search . '%';
            $params[':s5'] = '%' . $search . '%';
        }

        if (!empty($classId)) {
            $where[] = "s.class_id = :cid";
            $params[':cid'] = $classId;
        }

        $whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";

        $sql = "SELECT s.id, s.user_id, s.class_id, s.nis, s.nisn, s.gender, s.created_at,
                       u.username, u.email, u.full_name, u.phone, u.is_active,
                       c.name AS class_name, c.level AS class_level, c.academic_year 
                FROM students s 
                JOIN users u ON s.user_id = u.id 
                LEFT JOIN classes c ON s.class_id = c.id 
                {$whereClause} 
                ORDER BY c.name ASC, u.full_name ASC";

        $query = $this->db->query($sql);
        foreach ($params as $param => $val) {
            $query->bind($param, $val);
        }

        return $query->resultSet();
    }

    /**
     * Find student by ID with class & user details
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT s.id, s.user_id, s.class_id, s.nis, s.nisn, s.gender, s.created_at, s.updated_at,
                       u.username, u.email, u.full_name, u.phone, u.is_active,
                       c.name AS class_name, c.level AS class_level, c.academic_year 
                FROM students s 
                JOIN users u ON s.user_id = u.id 
                LEFT JOIN classes c ON s.class_id = c.id 
                WHERE s.id = :id 
                LIMIT 1";

        return $this->db->query($sql)
            ->bind(':id', $id)
            ->single();
    }

    /**
     * Check if NIS is already taken
     */
    public function isNisTaken(string $nis, ?int $excludeId = null): bool
    {
        $sql = "SELECT id FROM students WHERE nis = :nis";
        if ($excludeId !== null) {
            $sql .= " AND id != :exId";
        }
        $sql .= " LIMIT 1";

        $q = $this->db->query($sql)->bind(':nis', $nis);
        if ($excludeId !== null) {
            $q->bind(':exId', $excludeId);
        }
        return !empty($q->single());
    }

    /**
     * Check if NISN is already taken
     */
    public function isNisnTaken(?string $nisn, ?int $excludeId = null): bool
    {
        if (empty($nisn)) return false;

        $sql = "SELECT id FROM students WHERE nisn = :nisn";
        if ($excludeId !== null) {
            $sql .= " AND id != :exId";
        }
        $sql .= " LIMIT 1";

        $q = $this->db->query($sql)->bind(':nisn', $nisn);
        if ($excludeId !== null) {
            $q->bind(':exId', $excludeId);
        }
        return !empty($q->single());
    }

    /**
     * Create student with user account atomically
     *
     * @param array $userData
     * @param array $studentData
     * @return int Student ID
     * @throws Exception
     */
    public function createStudentWithUser(array $userData, array $studentData): int
    {
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();

        try {
            // 1. Create User
            $passwordHash = password_hash($userData['password'], PASSWORD_BCRYPT);
            $userStmt = $pdo->prepare("
                INSERT INTO users (username, email, password, role, full_name, phone, is_active) 
                VALUES (:uname, :email, :pass, 'student', :fname, :phone, :active)
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

            // 2. Create Student Profile
            $studentStmt = $pdo->prepare("
                INSERT INTO students (user_id, class_id, nis, nisn, gender) 
                VALUES (:uid, :cid, :nis, :nisn, :gender)
            ");
            $studentStmt->execute([
                ':uid' => $userId,
                ':cid' => !empty($studentData['class_id']) ? (int)$studentData['class_id'] : null,
                ':nis' => $studentData['nis'],
                ':nisn' => !empty($studentData['nisn']) ? $studentData['nisn'] : null,
                ':gender' => $studentData['gender'] ?? 'L'
            ]);
            $studentId = (int)$pdo->lastInsertId();

            $pdo->commit();
            return $studentId;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Update student with user account atomically
     *
     * @param int $studentId
     * @param array $userData
     * @param array $studentData
     * @return bool
     * @throws Exception
     */
    public function updateStudentWithUser(int $studentId, array $userData, array $studentData): bool
    {
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();

        try {
            $student = $this->findById($studentId);
            if (!$student) {
                throw new Exception("Data peserta didik tidak ditemukan.");
            }
            $userId = $student['user_id'];

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

            // 2. Update Student Profile
            $studentStmt = $pdo->prepare("
                UPDATE students 
                SET class_id = :cid, nis = :nis, nisn = :nisn, gender = :gender 
                WHERE id = :sid
            ");
            $studentStmt->execute([
                ':cid' => !empty($studentData['class_id']) ? (int)$studentData['class_id'] : null,
                ':nis' => $studentData['nis'],
                ':nisn' => !empty($studentData['nisn']) ? $studentData['nisn'] : null,
                ':gender' => $studentData['gender'] ?? 'L',
                ':sid' => $studentId
            ]);

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Delete student and cascading user
     *
     * @param int $id
     * @return bool
     */
    public function deleteStudent(int $id): bool
    {
        $student = $this->findById($id);
        if (!$student) return false;

        $sql = "DELETE FROM users WHERE id = :uid";
        return $this->db->query($sql)->bind(':uid', $student['user_id'])->execute();
    }
}
