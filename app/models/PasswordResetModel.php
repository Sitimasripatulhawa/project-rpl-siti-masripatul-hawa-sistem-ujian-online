<?php
/**
 * Password Reset Model (OTP Storage & Verification)
 * AI Online Exam & Proctoring System
 */

class PasswordResetModel extends Model
{
    /**
     * Create a new OTP record for a user
     *
     * @param int $userId
     * @param string $phone
     * @param string $otpCode
     * @param string $token
     * @param int $validMinutes
     * @return int
     */
    public function createOtp(int $userId, string $phone, string $otpCode, string $token, int $validMinutes = 10): int
    {
        // Invalidate older unused OTPs for this user first
        $this->invalidateUserOtps($userId);

        $expiresAt = date('Y-m-d H:i:s', time() + ($validMinutes * 60));

        $sql = "INSERT INTO password_resets (user_id, phone, otp_code, token, expires_at, is_used) 
                VALUES (:uid, :phone, :otp, :tok, :exp, 0)";

        $this->db->query($sql)
            ->bind(':uid', $userId)
            ->bind(':phone', $phone)
            ->bind(':otp', $otpCode)
            ->bind(':tok', $token)
            ->bind(':exp', $expiresAt)
            ->execute();

        return (int)$this->db->lastInsertId();
    }

    /**
     * Invalidate all previous unused OTPs for a user
     *
     * @param int $userId
     */
    public function invalidateUserOtps(int $userId): void
    {
        $sql = "UPDATE password_resets SET is_used = 1 WHERE user_id = :uid AND is_used = 0";
        $this->db->query($sql)->bind(':uid', $userId)->execute();
    }

    /**
     * Find a valid, unexpired, and unused OTP record
     *
     * @param string $phone
     * @param string $otpCode
     * @return array|null
     */
    public function findValidOtp(string $phone, string $otpCode): ?array
    {
        $now = date('Y-m-d H:i:s');
        $phoneDigits = preg_replace('/[^0-9]/', '', $phone);
        $phoneAlt = str_starts_with($phoneDigits, '0') ? ('62' . substr($phoneDigits, 1)) : ('0' . substr($phoneDigits, 2));

        $sql = "SELECT id, user_id, phone, otp_code, token, expires_at, is_used 
                FROM password_resets 
                WHERE (phone = :p1 OR phone = :p2 OR phone = :p3)
                  AND otp_code = :otp 
                  AND is_used = 0 
                  AND expires_at >= :now 
                ORDER BY id DESC 
                LIMIT 1";

        return $this->db->query($sql)
            ->bind(':p1', $phone)
            ->bind(':p2', $phoneDigits)
            ->bind(':p3', $phoneAlt)
            ->bind(':otp', trim($otpCode))
            ->bind(':now', $now)
            ->single();
    }

    /**
     * Find record by verification token
     *
     * @param string $token
     * @return array|null
     */
    public function findByToken(string $token): ?array
    {
        $now = date('Y-m-d H:i:s');
        $sql = "SELECT pr.*, u.username, u.full_name, u.email 
                FROM password_resets pr 
                JOIN users u ON pr.user_id = u.id 
                WHERE pr.token = :tok 
                  AND pr.is_used = 0 
                  AND pr.expires_at >= :now 
                LIMIT 1";

        return $this->db->query($sql)
            ->bind(':tok', $token)
            ->bind(':now', $now)
            ->single();
    }

    /**
     * Mark an OTP record as used
     *
     * @param int $id
     * @return bool
     */
    public function markAsUsed(int $id): bool
    {
        $sql = "UPDATE password_resets SET is_used = 1 WHERE id = :id";
        return $this->db->query($sql)->bind(':id', $id)->execute();
    }
}
