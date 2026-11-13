<?php
/**
 * Class Model (Manajemen Kelas)
 * AI Online Exam & Proctoring System
 */

class ClassModel extends Model
{
    /**
     * Get all classes with search and student count
     *
     * @param string $search
     * @return array
     */
    public function getAll(string $search = ''): array
    {
        $params = [];
        $where = "";

        if (!empty($search)) {
            $where = "WHERE (c.name LIKE :s1 OR c.level LIKE :s2 OR c.academic_year LIKE :s3)";
            $params[':s1'] = '%' . $search . '%';
            $params[':s2'] = '%' . $search . '%';
            $params[':s3'] = '%' . $search . '%';
        }

        $sql = "SELECT c.id, c.name, c.level, c.academic_year, c.created_at, 
                       COUNT(s.id) AS total_students 
                FROM classes c 
                LEFT JOIN students s ON c.id = s.class_id 
                {$where} 
                GROUP BY c.id 
                ORDER BY c.level ASC, c.name ASC";

        $query = $this->db->query($sql);
        foreach ($params as $param => $val) {
            $query->bind($param, $val);
        }

        return $query->resultSet();
    }

    /**
     * Find class by ID with student count
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT c.id, c.name, c.level, c.academic_year, c.created_at, c.updated_at,
                       COUNT(s.id) AS total_students 
                FROM classes c 
                LEFT JOIN students s ON c.id = s.class_id 
                WHERE c.id = :id 
                GROUP BY c.id 
                LIMIT 1";

        return $this->db->query($sql)
            ->bind(':id', $id)
            ->single();
    }

    /**
     * Create class
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO classes (name, level, academic_year) VALUES (:name, :level, :ay)";
        $this->db->query($sql)
            ->bind(':name', $data['name'])
            ->bind(':level', $data['level'])
            ->bind(':ay', $data['academic_year'])
            ->execute();

        return (int)$this->db->lastInsertId();
    }

    /**
     * Update class
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE classes SET name = :name, level = :level, academic_year = :ay WHERE id = :id";
        return $this->db->query($sql)
            ->bind(':name', $data['name'])
            ->bind(':level', $data['level'])
            ->bind(':ay', $data['academic_year'])
            ->bind(':id', $id)
            ->execute();
    }

    /**
     * Delete class
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM classes WHERE id = :id";
        return $this->db->query($sql)->bind(':id', $id)->execute();
    }

    /**
     * Count students in class
     */
    public function countStudents(int $id): int
    {
        $sql = "SELECT COUNT(*) AS total FROM students WHERE class_id = :id";
        $res = $this->db->query($sql)->bind(':id', $id)->single();
        return (int)($res['total'] ?? 0);
    }
}
