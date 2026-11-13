<?php
/**
 * Notification Model
 * Handles database operations for in-website notifications.
 */

class NotificationModel extends Model
{
    /**
     * Get notifications for a specific user with pagination and optional unread filter
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @param bool $unreadOnly
     * @return array
     */
    public function getByUser(int $userId, int $limit = 30, int $offset = 0, bool $unreadOnly = false): array
    {
        $where = "WHERE user_id = :uid";
        if ($unreadOnly) {
            $where .= " AND is_read = 0";
        }

        $sql = "SELECT id, user_id, title, message, type, category, link_url, is_read, read_at, created_at 
                FROM notifications 
                {$where} 
                ORDER BY created_at DESC, id DESC 
                LIMIT {$limit} OFFSET {$offset}";

        return $this->db->query($sql)
            ->bind(':uid', $userId)
            ->resultSet();
    }

    /**
     * Count unread notifications for a specific user
     *
     * @param int $userId
     * @return int
     */
    public function countUnread(int $userId): int
    {
        $sql = "SELECT COUNT(*) AS total FROM notifications WHERE user_id = :uid AND is_read = 0";
        $res = $this->db->query($sql)
            ->bind(':uid', $userId)
            ->single();

        return (int)($res['total'] ?? 0);
    }

    /**
     * Get recent unread notifications for navbar dropdown
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getRecent(int $userId, int $limit = 5): array
    {
        $sql = "SELECT id, user_id, title, message, type, category, link_url, is_read, created_at 
                FROM notifications 
                WHERE user_id = :uid 
                ORDER BY is_read ASC, created_at DESC 
                LIMIT {$limit}";

        return $this->db->query($sql)
            ->bind(':uid', $userId)
            ->resultSet();
    }

    /**
     * Create a new notification
     *
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO notifications (user_id, title, message, type, category, link_url, is_read, created_at) 
                VALUES (:uid, :title, :msg, :type, :cat, :url, 0, NOW())";

        $this->db->query($sql)
            ->bind(':uid', (int)$data['user_id'])
            ->bind(':title', trim($data['title']))
            ->bind(':msg', trim($data['message']))
            ->bind(':type', in_array($data['type'] ?? 'info', ['info', 'success', 'warning', 'danger']) ? $data['type'] : 'info')
            ->bind(':cat', trim($data['category'] ?? 'general'))
            ->bind(':url', !empty($data['link_url']) ? trim($data['link_url']) : null)
            ->execute();

        return (int)$this->db->lastInsertId();
    }

    /**
     * Mark a single notification as read, scoped strictly to the authenticated user
     *
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function markAsRead(int $id, int $userId): bool
    {
        $sql = "UPDATE notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE id = :id AND user_id = :uid";

        return $this->db->query($sql)
            ->bind(':id', $id)
            ->bind(':uid', $userId)
            ->execute();
    }

    /**
     * Mark all notifications as read for a specific user
     *
     * @param int $userId
     * @return bool
     */
    public function markAllAsRead(int $userId): bool
    {
        $sql = "UPDATE notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE user_id = :uid AND is_read = 0";

        return $this->db->query($sql)
            ->bind(':uid', $userId)
            ->execute();
    }

    /**
     * Delete a notification scoped strictly to the authenticated user
     *
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function delete(int $id, int $userId): bool
    {
        $sql = "DELETE FROM notifications WHERE id = :id AND user_id = :uid";
        return $this->db->query($sql)
            ->bind(':id', $id)
            ->bind(':uid', $userId)
            ->execute();
    }

    /**
     * Find notification by ID and User ID
     *
     * @param int $id
     * @param int $userId
     * @return array|null
     */
    public function findByIdAndUser(int $id, int $userId): ?array
    {
        $sql = "SELECT * FROM notifications WHERE id = :id AND user_id = :uid LIMIT 1";
        return $this->db->query($sql)
            ->bind(':id', $id)
            ->bind(':uid', $userId)
            ->single();
    }
}
