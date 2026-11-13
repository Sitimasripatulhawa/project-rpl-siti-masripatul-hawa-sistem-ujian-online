<?php
/**
 * User Model
 * AI Online Exam & Proctoring System
 */

class UserModel extends Model
{
    /**
     * Find user by username or email (for auth & verification)
     *
     * @param string $identifier Username or Email
     * @return array|null
     */
    public function findByUsernameOrEmail(string $identifier): ?array
    {
        $sql = "SELECT id, username, email, password, role, full_name, phone, avatar, is_active 
                FROM users 
                WHERE (username = :uname OR email = :uemail) 
                LIMIT 1";

        $user = $this->db->query($sql)
            ->bind(':uname', $identifier)
            ->bind(':uemail', $identifier)
            ->single();

        if (!$user) {
            return null;
        }

        // Fetch extra profile details based on role
        $extra = [];
        if ($user['role'] === 'teacher') {
            $t = $this->db->query("SELECT id AS teacher_id, nip, specialization FROM teachers WHERE user_id = :uid LIMIT 1")
                ->bind(':uid', $user['id'])
                ->single();
            if ($t) $extra = $t;
        } elseif ($user['role'] === 'student') {
            $s = $this->db->query("
                SELECT s.id AS student_id, s.nis, s.nisn, s.gender, s.class_id, c.name AS class_name 
                FROM students s 
                LEFT JOIN classes c ON s.class_id = c.id 
                WHERE s.user_id = :uid 
                LIMIT 1
            ")
            ->bind(':uid', $user['id'])
            ->single();
            if ($s) $extra = $s;
        }

        $user['extra'] = $extra;
        return $user;
    }

    /**
     * Find user by ID
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT id, username, email, role, full_name, phone, avatar, is_active, created_at, updated_at 
                FROM users 
                WHERE id = :id 
                LIMIT 1";

        return $this->db->query($sql)
            ->bind(':id', $id)
            ->single();
    }

    /**
     * Get all users with search, role filter, and pagination
     *
     * @param string $search
     * @param string $role
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAll(string $search = '', string $role = '', int $limit = 50, int $offset = 0): array
    {
        $params = [];
        $where = [];

        if (!empty($search)) {
            $where[] = "(username LIKE :s1 OR email LIKE :s2 OR full_name LIKE :s3 OR phone LIKE :s4)";
            $params[':s1'] = '%' . $search . '%';
            $params[':s2'] = '%' . $search . '%';
            $params[':s3'] = '%' . $search . '%';
            $params[':s4'] = '%' . $search . '%';
        }

        if (!empty($role)) {
            $where[] = "role = :role";
            $params[':role'] = $role;
        }

        $whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";
        $sql = "SELECT id, username, email, role, full_name, phone, is_active, created_at 
                FROM users 
                {$whereClause} 
                ORDER BY id DESC 
                LIMIT {$limit} OFFSET {$offset}";

        $query = $this->db->query($sql);
        foreach ($params as $param => $val) {
            $query->bind($param, $val);
        }

        return $query->resultSet();
    }

    /**
     * Count users matching filter
     *
     * @param string $search
     * @param string $role
     * @return int
     */
    public function countAll(string $search = '', string $role = ''): int
    {
        $params = [];
        $where = [];

        if (!empty($search)) {
            $where[] = "(username LIKE :s1 OR email LIKE :s2 OR full_name LIKE :s3 OR phone LIKE :s4)";
            $params[':s1'] = '%' . $search . '%';
            $params[':s2'] = '%' . $search . '%';
            $params[':s3'] = '%' . $search . '%';
            $params[':s4'] = '%' . $search . '%';
        }

        if (!empty($role)) {
            $where[] = "role = :role";
            $params[':role'] = $role;
        }

        $whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";
        $sql = "SELECT COUNT(*) AS total FROM users {$whereClause}";

        $query = $this->db->query($sql);
        foreach ($params as $param => $val) {
            $query->bind($param, $val);
        }

        $res = $query->single();
        return (int)($res['total'] ?? 0);
    }

    /**
     * Check if username is already in use
     */
    public function isUsernameTaken(string $username, ?int $excludeId = null): bool
    {
        $sql = "SELECT id FROM users WHERE username = :uname";
        if ($excludeId !== null) {
            $sql .= " AND id != :exId";
        }
        $sql .= " LIMIT 1";

        $q = $this->db->query($sql)->bind(':uname', $username);
        if ($excludeId !== null) {
            $q->bind(':exId', $excludeId);
        }
        return !empty($q->single());
    }

    /**
     * Check if email is already in use
     */
    public function isEmailTaken(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT id FROM users WHERE email = :email";
        if ($excludeId !== null) {
            $sql .= " AND id != :exId";
        }
        $sql .= " LIMIT 1";

        $q = $this->db->query($sql)->bind(':email', $email);
        if ($excludeId !== null) {
            $q->bind(':exId', $excludeId);
        }
        return !empty($q->single());
    }

    /**
     * Create new user record
     *
     * @param array $data
     * @return int Inserted ID
     */
    public function create(array $data): int
    {
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);

        $sql = "INSERT INTO users (username, email, password, role, full_name, phone, is_active) 
                VALUES (:uname, :email, :pass, :role, :fname, :phone, :active)";

        $this->db->query($sql)
            ->bind(':uname', $data['username'])
            ->bind(':email', $data['email'])
            ->bind(':pass', $passwordHash)
            ->bind(':role', $data['role'] ?? 'student')
            ->bind(':fname', $data['full_name'])
            ->bind(':phone', $data['phone'] ?? null)
            ->bind(':active', isset($data['is_active']) ? (int)$data['is_active'] : 1)
            ->execute();

        return (int)$this->db->lastInsertId();
    }

    /**
     * Update user record
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $fields = [
            'username = :uname',
            'email = :email',
            'role = :role',
            'full_name = :fname',
            'phone = :phone',
            'is_active = :active'
        ];

        if (!empty($data['password'])) {
            $fields[] = 'password = :pass';
        }

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $q = $this->db->query($sql)
            ->bind(':uname', $data['username'])
            ->bind(':email', $data['email'])
            ->bind(':role', $data['role'])
            ->bind(':fname', $data['full_name'])
            ->bind(':phone', $data['phone'] ?? null)
            ->bind(':active', isset($data['is_active']) ? (int)$data['is_active'] : 1)
            ->bind(':id', $id);

        if (!empty($data['password'])) {
            $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
            $q->bind(':pass', $passwordHash);
        }

        return $q->execute();
    }

    /**
     * Delete user record
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM users WHERE id = :id";
        return $this->db->query($sql)->bind(':id', $id)->execute();
    }
}
