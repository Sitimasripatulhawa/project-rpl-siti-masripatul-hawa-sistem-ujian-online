<?php
/**
 * Subject Model (Manajemen Mata Pelajaran)
 * AI Online Exam & Proctoring System
 */

class SubjectModel extends Model
{
    /**
     * Get all subjects with search and question count
     *
     * @param string $search
     * @return array
     */
    public function getAll(string $search = ''): array
    {
        $params = [];
        $where = "";

        if (!empty($search)) {
            $where = "WHERE (s.code LIKE :s1 OR s.name LIKE :s2 OR s.description LIKE :s3)";
            $params[':s1'] = '%' . $search . '%';
            $params[':s2'] = '%' . $search . '%';
            $params[':s3'] = '%' . $search . '%';
        }

        $sql = "SELECT s.id, s.code, s.name, s.description, s.created_at,
                       COUNT(q.id) AS total_questions 
                FROM subjects s 
                LEFT JOIN questions q ON s.id = q.subject_id 
                {$where} 
                GROUP BY s.id 
                ORDER BY s.name ASC";

        $query = $this->db->query($sql);
        foreach ($params as $param => $val) {
            $query->bind($param, $val);
        }

        return $query->resultSet();
    }

    /**
     * Find subject by ID
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT s.id, s.code, s.name, s.description, s.created_at, s.updated_at,
                       COUNT(q.id) AS total_questions 
                FROM subjects s 
                LEFT JOIN questions q ON s.id = q.subject_id 
                WHERE s.id = :id 
                GROUP BY s.id 
                LIMIT 1";

        return $this->db->query($sql)
            ->bind(':id', $id)
            ->single();
    }

    /**
     * Check if code is already in use
     */
    public function isCodeTaken(string $code, ?int $excludeId = null): bool
    {
        $sql = "SELECT id FROM subjects WHERE code = :code";
        if ($excludeId !== null) {
            $sql .= " AND id != :exId";
        }
        $sql .= " LIMIT 1";

        $q = $this->db->query($sql)->bind(':code', $code);
        if ($excludeId !== null) {
            $q->bind(':exId', $excludeId);
        }
        return !empty($q->single());
    }

    /**
     * Create subject
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO subjects (code, name, description) VALUES (:code, :name, :desc)";
        $this->db->query($sql)
            ->bind(':code', strtoupper(trim($data['code'])))
            ->bind(':name', trim($data['name']))
            ->bind(':desc', trim($data['description'] ?? ''))
            ->execute();

        return (int)$this->db->lastInsertId();
    }

    /**
     * Update subject
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE subjects SET code = :code, name = :name, description = :desc WHERE id = :id";
        return $this->db->query($sql)
            ->bind(':code', strtoupper(trim($data['code'])))
            ->bind(':name', trim($data['name']))
            ->bind(':desc', trim($data['description'] ?? ''))
            ->bind(':id', $id)
            ->execute();
    }

    /**
     * Delete subject
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM subjects WHERE id = :id";
        return $this->db->query($sql)->bind(':id', $id)->execute();
    }
}
