<?php
/**
 * Email Service
 * Handles SMTP email delivery, logging, templates, and safe fallback handling.
 */

require_once __DIR__ . '/../vendor/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Check if SMTP credentials are fully configured
     *
     * @return bool
     */
    public static function isConfigured(): bool
    {
        return !empty(MAIL_USERNAME) && !empty(MAIL_PASSWORD) && !empty(MAIL_HOST);
    }

    /**
     * Get detailed diagnostic information of SMTP configuration
     *
     * @return array
     */
    public static function getConfigurationDetails(): array
    {
        $hasEnv = defined('ENV_FILE_EXISTS') ? ENV_FILE_EXISTS : file_exists(dirname(__DIR__, 2) . '/.env');
        $hasHost = !empty(MAIL_HOST);
        $hasPort = !empty(MAIL_PORT);
        $hasUsername = !empty(MAIL_USERNAME);
        $hasPassword = !empty(MAIL_PASSWORD);

        $missing = [];
        if (!$hasEnv) {
            $missing[] = "File '.env' belum dibuat di direktori utama project";
        }
        if (!$hasUsername) {
            $missing[] = "MAIL_USERNAME belum diisi";
        }
        if (!$hasPassword) {
            $missing[] = "MAIL_PASSWORD (App Password 16 karakter) belum diisi";
        }

        return [
            'has_env_file' => $hasEnv,
            'host' => MAIL_HOST,
            'port' => MAIL_PORT,
            'encryption' => MAIL_ENCRYPTION,
            'username_masked' => $hasUsername ? (substr(MAIL_USERNAME, 0, 3) . '***@' . (explode('@', MAIL_USERNAME)[1] ?? 'gmail.com')) : '(Belum diatur)',
            'from_address' => MAIL_FROM_ADDRESS,
            'from_name' => MAIL_FROM_NAME,
            'has_username' => $hasUsername,
            'has_password' => $hasPassword,
            'is_configured' => ($hasUsername && $hasPassword && $hasHost),
            'missing' => $missing,
            'missing_items' => $missing
        ];
    }

    /**
     * Send email with automated logging, validation, and deduplication
     *
     * @param string $toEmail Recipient email address
     * @param string $toName Recipient display name
     * @param string $subject Email subject line
     * @param string $htmlBody Rendered HTML email body
     * @param string $eventType Business event category (e.g. 'exam_published', 'exam_schedule')
     * @param string|null $referenceId Entity reference ID (e.g. exam ID)
     * @param int|null $userId User account ID in database
     * @param bool $forceSend Force sending even if already logged (for retries)
     * @return array Status result [success => bool, status => string, message => string]
     */
    public function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $eventType,
        ?string $referenceId = null,
        ?int $userId = null,
        bool $forceSend = false
    ): array {
        $toEmail = trim($toEmail);
        $toName = trim($toName);

        // 1. Validate email address format
        if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $this->logEmail([
                'user_id' => $userId,
                'recipient_email' => $toEmail ?: '(kosong)',
                'recipient_name' => $toName,
                'event_type' => $eventType,
                'reference_id' => $referenceId,
                'subject' => $subject,
                'status' => 'invalid_email',
                'error_message' => 'Alamat email tidak valid atau kosong pada akun pengguna.'
            ]);

            return [
                'success' => false,
                'status' => 'invalid_email',
                'message' => 'Alamat email penerima tidak valid atau kosong di database.'
            ];
        }

        // 2. Deduplication check: prevent multiple identical emails for the same event
        if (!$forceSend && $this->isAlreadySent($eventType, $referenceId, $toEmail)) {
            return [
                'success' => true,
                'status' => 'skipped',
                'message' => 'Email untuk event ini sudah pernah terkirim ke penerima terkait.'
            ];
        }

        // Avoid repeated smtp_disabled spam for identical event and reference when SMTP remains unconfigured
        if (!$forceSend && !self::isConfigured() && !empty($referenceId)) {
            $existing = $this->db->query("
                SELECT id FROM email_logs 
                WHERE event_type = :event 
                  AND reference_id = :ref 
                  AND recipient_email = :email 
                  AND status = 'smtp_disabled' 
                LIMIT 1
            ")->bind(':event', $eventType)
              ->bind(':ref', $referenceId)
              ->bind(':email', $toEmail)
              ->single();

            if ($existing) {
                return [
                    'success' => false,
                    'status' => 'smtp_disabled',
                    'log_id' => (int)$existing['id'],
                    'message' => 'SMTP belum dikonfigurasi (Pemberitahuan telah dicatat sebelumnya).'
                ];
            }
        }

        // 3. Check if SMTP configuration is present
        if (!self::isConfigured()) {
            $logId = $this->logEmail([
                'user_id' => $userId,
                'recipient_email' => $toEmail,
                'recipient_name' => $toName,
                'event_type' => $eventType,
                'reference_id' => $referenceId,
                'subject' => $subject,
                'status' => 'smtp_disabled',
                'error_message' => 'SMTP belum dikonfigurasi. Lengkapi MAIL_USERNAME dan MAIL_PASSWORD di file .env.'
            ]);

            return [
                'success' => false,
                'status' => 'smtp_disabled',
                'log_id' => $logId,
                'message' => 'SMTP belum dikonfigurasi di file .env (Pengiriman email dilewati, notifikasi website tetap dibuat).'
            ];
        }

        // 4. Attempt sending via PHPMailer
        $mail = new PHPMailer(true);

        try {
            // SMTP Settings
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USERNAME;
            $mail->Password   = MAIL_PASSWORD;
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 15; // 15 seconds timeout

            if (MAIL_ENCRYPTION === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif (MAIL_ENCRYPTION === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = false;
                $mail->SMTPAutoTLS = false;
            }

            $mail->Port = (int)MAIL_PORT;

            // Sender and Recipient
            $fromAddress = MAIL_FROM_ADDRESS ?: MAIL_USERNAME;
            $fromName = MAIL_FROM_NAME ?: APP_NAME;
            $mail->setFrom($fromAddress, $fromName);
            $mail->addAddress($toEmail, $toName);
            $mail->addReplyTo($fromAddress, $fromName);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<p>', '</p>'], ["\n", "\n", "\n", "\n\n"], $htmlBody));

            $mail->send();

            // 5. Log success
            $logId = $this->logEmail([
                'user_id' => $userId,
                'recipient_email' => $toEmail,
                'recipient_name' => $toName,
                'event_type' => $eventType,
                'reference_id' => $referenceId,
                'subject' => $subject,
                'status' => 'sent',
                'sent_at' => date('Y-m-d H:i:s'),
                'error_message' => null
            ]);

            return [
                'success' => true,
                'status' => 'sent',
                'log_id' => $logId,
                'message' => 'Email berhasil diserahkan ke server SMTP.'
            ];
        } catch (Exception $e) {
            // Sanitize error message to hide sensitive secrets
            $rawError = $mail->ErrorInfo ?: $e->getMessage();
            $safeError = $this->sanitizeError($rawError);

            $logId = $this->logEmail([
                'user_id' => $userId,
                'recipient_email' => $toEmail,
                'recipient_name' => $toName,
                'event_type' => $eventType,
                'reference_id' => $referenceId,
                'subject' => $subject,
                'status' => 'failed',
                'error_message' => $safeError
            ]);

            return [
                'success' => false,
                'status' => 'failed',
                'log_id' => $logId,
                'message' => 'Gagal mengirim email: ' . $safeError
            ];
        }
    }

    /**
     * Check if email has already been sent to avoid duplicates
     */
    protected function isAlreadySent(string $eventType, ?string $referenceId, string $toEmail): bool
    {
        if (empty($referenceId)) {
            return false;
        }

        $sql = "SELECT id FROM email_logs 
                WHERE event_type = :event 
                  AND reference_id = :ref 
                  AND recipient_email = :email 
                  AND status = 'sent' 
                LIMIT 1";

        $res = $this->db->query($sql)
            ->bind(':event', $eventType)
            ->bind(':ref', $referenceId)
            ->bind(':email', $toEmail)
            ->single();

        return !empty($res);
    }

    /**
     * Record email transaction log to database with smart deduplication
     */
    protected function logEmail(array $data): int
    {
        try {
            $eventType = $data['event_type'];
            $refId = $data['reference_id'] ?? null;
            $email = $data['recipient_email'];

            // If reference_id is provided, check if a log row already exists for this exact recipient & event
            if (!empty($refId)) {
                $existing = $this->db->query("
                    SELECT id, attempt_count FROM email_logs 
                    WHERE event_type = :event 
                      AND reference_id = :ref 
                      AND recipient_email = :email 
                    ORDER BY id DESC LIMIT 1
                ")->bind(':event', $eventType)
                  ->bind(':ref', $refId)
                  ->bind(':email', $email)
                  ->single();

                if ($existing) {
                    $newAttempt = ((int)($existing['attempt_count'] ?? 1)) + 1;
                    $sql = "UPDATE email_logs 
                            SET user_id = :uid,
                                recipient_name = :name,
                                subject = :subject,
                                status = :status,
                                attempt_count = :attempt,
                                error_message = :err,
                                sent_at = :sent 
                            WHERE id = :id";

                    $this->db->query($sql)
                        ->bind(':uid', $data['user_id'] ?? null)
                        ->bind(':name', $data['recipient_name'] ?? null)
                        ->bind(':subject', $data['subject'])
                        ->bind(':status', $data['status'])
                        ->bind(':attempt', $newAttempt)
                        ->bind(':err', $data['error_message'] ?? null)
                        ->bind(':sent', $data['sent_at'] ?? null)
                        ->bind(':id', (int)$existing['id'])
                        ->execute();

                    return (int)$existing['id'];
                }
            }

            $sql = "INSERT INTO email_logs (user_id, recipient_email, recipient_name, event_type, reference_id, subject, status, attempt_count, error_message, sent_at, created_at)
                    VALUES (:uid, :email, :name, :event, :ref, :subject, :status, 1, :err, :sent, NOW())";

            $this->db->query($sql)
                ->bind(':uid', $data['user_id'] ?? null)
                ->bind(':email', $data['recipient_email'])
                ->bind(':name', $data['recipient_name'] ?? null)
                ->bind(':event', $data['event_type'])
                ->bind(':ref', $data['reference_id'] ?? null)
                ->bind(':subject', $data['subject'])
                ->bind(':status', $data['status'])
                ->bind(':err', $data['error_message'] ?? null)
                ->bind(':sent', $data['sent_at'] ?? null)
                ->execute();

            return (int)$this->db->lastInsertId();
        } catch (Throwable $t) {
            // Failsafe: logging failure must never stop execution
            return 0;
        }
    }

    /**
     * Sanitize error message to prevent accidental leak of password or auth tokens
     */
    protected function sanitizeError(string $msg): string
    {
        $patterns = [
            '/' . preg_quote(MAIL_PASSWORD, '/') . '/' => '***HIDDEN***',
            '/' . preg_quote(MAIL_USERNAME, '/') . '/' => substr(MAIL_USERNAME, 0, 3) . '***@***',
        ];
        return (string)preg_replace(array_keys($patterns), array_values($patterns), $msg);
    }

    /**
     * Send test email to verify SMTP configuration from Admin Panel
     *
     * @param string $testEmail Target email address
     * @return array
     */
    public function testSmtpConnection(string $testEmail): array
    {
        $testEmail = trim($testEmail);
        if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'status' => 'invalid_email',
                'message' => 'Alamat email pengujian tidak valid: ' . htmlspecialchars($testEmail)
            ];
        }

        if (!self::isConfigured()) {
            $configStatus = self::getConfigurationDetails();
            $missingStr = implode(', ', $configStatus['missing_items']);

            $logId = $this->logEmail([
                'user_id' => null,
                'recipient_email' => $testEmail,
                'recipient_name' => 'Administrator Uji',
                'event_type' => 'system_test',
                'reference_id' => 'test_' . date('Ymd_His'),
                'subject' => 'Uji Konfigurasi SMTP - ' . APP_SHORT_NAME,
                'status' => 'smtp_disabled',
                'error_message' => 'Pengujian dibatalkan: ' . $missingStr
            ]);

            return [
                'success' => false,
                'status' => 'smtp_disabled',
                'log_id' => $logId,
                'message' => 'Pengujian belum dapat dilakukan karena kredensial SMTP belum lengkap: ' . $missingStr . '.'
            ];
        }

        $subject = 'Uji Konfigurasi SMTP - ' . APP_SHORT_NAME;
        $now = date('d F Y, H:i:s T');
        $html = $this->buildHtmlTemplate(
            'Uji Koneksi SMTP Berhasil',
            'Halo Administrator,',
            "<p>Email ini merupakan pesan uji coba untuk memverifikasi bahwa konfigurasi server SMTP (Gmail) pada sistem <strong>" . APP_NAME . "</strong> telah aktif dan berhasil terhubung.</p>
             <p>Detail Konfigurasi Saat Pengujian:</p>
             <ul>
                 <li><strong>Host:</strong> " . htmlspecialchars(MAIL_HOST) . "</li>
                 <li><strong>Port:</strong> " . (int)MAIL_PORT . "</li>
                 <li><strong>Enkripsi:</strong> " . htmlspecialchars(strtoupper(MAIL_ENCRYPTION)) . "</li>
                 <li><strong>Waktu Uji:</strong> " . $now . "</li>
             </ul>
             <p>Setiap akun peserta didik dan guru akan menerima pemberitahuan otomatis ke alamat email masing-masing sesuai event sistem yang relevan.</p>",
            BASE_URL . '/admin/dashboard',
            'Buka Dashboard Admin'
        );

        $refId = 'test_' . date('Ymd_His');

        return $this->send(
            $testEmail,
            'Administrator Uji',
            $subject,
            $html,
            'system_test',
            $refId,
            null,
            true // Force send
        );
    }

    /**
     * Retry sending a failed email from email_logs
     *
     * @param int $logId
     * @return array
     */
    public function retryFailedEmail(int $logId): array
    {
        $log = $this->db->query("SELECT * FROM email_logs WHERE id = :id LIMIT 1")
            ->bind(':id', $logId)
            ->single();

        if (!$log) {
            return ['success' => false, 'message' => 'Log email tidak ditemukan.'];
        }

        $subject = $log['subject'];
        $html = $this->buildHtmlTemplate(
            $log['subject'],
            'Halo ' . htmlspecialchars($log['recipient_name'] ?: 'Pengguna') . ',',
            "<p>Pemberitahuan ulang otomatis terkait: <strong>" . htmlspecialchars($log['event_type']) . "</strong>.</p>",
            BASE_URL,
            'Buka Sistem'
        );

        $res = $this->send(
            $log['recipient_email'],
            $log['recipient_name'] ?: 'Pengguna',
            $subject,
            $html,
            $log['event_type'],
            $log['reference_id'],
            $log['user_id'] ? (int)$log['user_id'] : null,
            true
        );

        if ($res['success']) {
            $this->db->query("UPDATE email_logs SET status = 'sent', sent_at = NOW(), attempt_count = attempt_count + 1 WHERE id = :id")
                ->bind(':id', $logId)
                ->execute();
        } else {
            $this->db->query("UPDATE email_logs SET attempt_count = attempt_count + 1, error_message = :err WHERE id = :id")
                ->bind(':err', $res['message'] ?? 'Retry failed')
                ->bind(':id', $logId)
                ->execute();
        }

        return $res;
    }

    /**
     * Get email logs with pagination, status filter, and scope filter for Admin
     */
    public function getLogs(int $limit = 50, int $offset = 0, string $statusFilter = '', string $scopeFilter = ''): array
    {
        $where = [];
        $params = [];

        if (!empty($statusFilter) && $statusFilter !== 'all') {
            $where[] = "el.status = :status";
            $params[':status'] = $statusFilter;
        }

        if ($scopeFilter === 'academic') {
            $where[] = "el.event_type != 'system_test'";
        } elseif ($scopeFilter === 'test') {
            $where[] = "el.event_type = 'system_test'";
        }

        $whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";

        $sql = "SELECT el.*, u.username, u.role 
                FROM email_logs el 
                LEFT JOIN users u ON el.user_id = u.id 
                {$whereClause} 
                ORDER BY el.id DESC 
                LIMIT {$limit} OFFSET {$offset}";

        $q = $this->db->query($sql);
        foreach ($params as $k => $v) {
            $q->bind($k, $v);
        }

        return $q->resultSet();
    }

    /**
     * Get summary statistics of email deliveries
     */
    public function getStats(): array
    {
        return [
            'total' => (int)$this->db->query("SELECT COUNT(*) FROM email_logs")->fetchColumn(),
            'sent' => (int)$this->db->query("SELECT COUNT(*) FROM email_logs WHERE status = 'sent'")->fetchColumn(),
            'failed' => (int)$this->db->query("SELECT COUNT(*) FROM email_logs WHERE status = 'failed'")->fetchColumn(),
            'smtp_disabled' => (int)$this->db->query("SELECT COUNT(*) FROM email_logs WHERE status = 'smtp_disabled'")->fetchColumn(),
            'invalid_email' => (int)$this->db->query("SELECT COUNT(*) FROM email_logs WHERE status = 'invalid_email'")->fetchColumn(),
            'academic_total' => (int)$this->db->query("SELECT COUNT(*) FROM email_logs WHERE event_type != 'system_test'")->fetchColumn(),
            'test_total' => (int)$this->db->query("SELECT COUNT(*) FROM email_logs WHERE event_type = 'system_test'")->fetchColumn(),
        ];
    }

    /**
     * Build modern, responsive Indonesian HTML email template
     *
     * @param string $heading
     * @param string $greeting
     * @param string $contentHtml
     * @param string|null $actionUrl
     * @param string|null $actionText
     * @param array $details Key-value pairs for information box
     * @return string
     */
    public function buildHtmlTemplate(
        string $heading,
        string $greeting,
        string $contentHtml,
        ?string $actionUrl = null,
        ?string $actionText = null,
        array $details = []
    ): string {
        $detailsHtml = '';
        if (!empty($details)) {
            $detailsHtml .= '<div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin: 20px 0;">';
            $detailsHtml .= '<table style="width: 100%; border-collapse: collapse; font-size: 14px;">';
            foreach ($details as $label => $val) {
                $detailsHtml .= '<tr>';
                $detailsHtml .= '<td style="padding: 6px 0; color: #64748b; width: 38%; vertical-align: top;">' . htmlspecialchars($label) . '</td>';
                $detailsHtml .= '<td style="padding: 6px 0; color: #1e293b; font-weight: 600; width: 62%;">' . htmlspecialchars((string)$val) . '</td>';
                $detailsHtml .= '</tr>';
            }
            $detailsHtml .= '</table></div>';
        }

        $buttonHtml = '';
        if (!empty($actionUrl) && !empty($actionText)) {
            $buttonHtml = '
            <div style="text-align: center; margin: 30px 0;">
                <a href="' . htmlspecialchars($actionUrl) . '" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-weight: 600; font-size: 14px; display: inline-block; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);">
                    ' . htmlspecialchars($actionText) . ' &rarr;
                </a>
            </div>';
        }

        $appName = APP_NAME;
        $appShort = APP_SHORT_NAME;
        $year = date('Y');

        return '
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($heading) . '</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; color: #334155; line-height: 1.6;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 30px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025); border: 1px solid #e2e8f0;">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); padding: 30px 30px 25px; text-align: left; border-bottom: 3px solid #2563eb;">
                            <div style="display: inline-block; background-color: rgba(37, 99, 235, 0.2); border: 1px solid rgba(59, 130, 246, 0.4); padding: 4px 10px; border-radius: 20px; font-size: 12px; color: #93c5fd; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 8px;">
                                ' . htmlspecialchars($appShort) . ' NOTIFIKASI RESMI
                            </div>
                            <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: #ffffff;">' . htmlspecialchars($heading) . '</h1>
                        </td>
                    </tr>
                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 30px 30px 20px;">
                            <p style="font-size: 15px; font-weight: 600; color: #0f172a; margin-top: 0; margin-bottom: 12px;">' . htmlspecialchars($greeting) . '</p>
                            <div style="font-size: 14px; color: #475569;">
                                ' . $contentHtml . '
                            </div>
                            ' . $detailsHtml . '
                            ' . $buttonHtml . '
                            <p style="font-size: 12px; color: #94a3b8; margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 15px;">
                                Pesan ini dikirim secara otomatis oleh sistem kepada akun Anda yang terdaftar pada database ' . htmlspecialchars($appName) . '. Jangan membalas email ini secara langsung.
                            </p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 20px 30px; text-align: center; border-top: 1px solid #e2e8f0;">
                            <p style="margin: 0; font-size: 12px; color: #64748b;">
                                &copy; ' . $year . ' ' . htmlspecialchars($appName) . '. Hak Cipta Dilindungi.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }
}
