<?php
/**
 * Setting Model
 * AI Online Exam & Proctoring System
 * 
 * Manages dynamic system configurations, database settings persistence,
 * environment synchronization (.env), and default parameters for exams & proctoring.
 */

class SettingModel extends Model
{
    /** @var array<string, mixed> Statically cached settings for current request lifecycle */
    private static array $cache = [];
    private static bool $isLoaded = false;

    public function __construct()
    {
        parent::__construct();
        $this->loadAll();
    }

    /**
     * Load all settings from database into memory cache
     */
    public function loadAll(bool $forceRefresh = false): void
    {
        if (self::$isLoaded && !$forceRefresh) {
            return;
        }

        try {
            $rows = $this->db->query("SELECT setting_key, setting_value FROM system_settings")->resultSet();
            foreach ($rows as $row) {
                self::$cache[$row['setting_key']] = $row['setting_value'];
            }
            self::$isLoaded = true;
        } catch (Throwable $t) {
            // Graceful fallback if table is not yet created or DB error
            self::$isLoaded = true;
        }
    }

    /**
     * Get a setting value with fallback
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        if (!self::$isLoaded) {
            $instance = new self();
        }

        if (array_key_exists($key, self::$cache)) {
            $val = self::$cache[$key];
            if ($val !== null && $val !== '') {
                return $val;
            }
        }

        // Secondary fallback to getenv or predefined constants
        $envVal = getenv(strtoupper($key));
        if ($envVal !== false && $envVal !== '') {
            return $envVal;
        }

        $constName = strtoupper($key);
        if (defined($constName)) {
            return constant($constName);
        }

        return $default;
    }

    /**
     * Update or insert a setting in the database
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $group
     * @param string|null $label
     * @param string|null $description
     * @param string|null $fieldType
     * @param int $isSecret
     * @return bool
     */
    public function set(
        string $key,
        $value,
        ?string $group = null,
        ?string $label = null,
        ?string $description = null,
        ?string $fieldType = null,
        int $isSecret = 0
    ): bool {
        try {
            $existing = $this->db->query("SELECT setting_key FROM system_settings WHERE setting_key = :k")
                ->bind(':k', $key)
                ->single();

            if ($existing) {
                $sql = "UPDATE system_settings SET setting_value = :v, updated_at = NOW() WHERE setting_key = :k";
                $this->db->query($sql)
                    ->bind(':k', $key)
                    ->bind(':v', (string)$value)
                    ->execute();
            } else {
                $sql = "INSERT INTO system_settings (setting_key, setting_value, setting_group, label, description, field_type, is_secret)
                        VALUES (:k, :v, :g, :l, :d, :t, :s)";
                $this->db->query($sql)
                    ->bind(':k', $key)
                    ->bind(':v', (string)$value)
                    ->bind(':g', $group ?? 'general')
                    ->bind(':l', $label ?? ucfirst(str_replace('_', ' ', $key)))
                    ->bind(':d', $description)
                    ->bind(':t', $fieldType ?? 'text')
                    ->bind(':s', $isSecret)
                    ->execute();
            }

            self::$cache[$key] = (string)$value;
            return true;
        } catch (Throwable $t) {
            error_log("Failed to save setting [{$key}]: " . $t->getMessage());
            return false;
        }
    }

    /**
     * Get all settings records grouped by setting_group
     *
     * @return array<string, array>
     */
    public function getAllGrouped(): array
    {
        $this->loadAll(true);
        $rows = $this->db->query("
            SELECT setting_key, setting_value, setting_group, label, description, field_type, is_secret, updated_at
            FROM system_settings
            ORDER BY setting_group ASC, setting_key ASC
        ")->resultSet();

        $grouped = [
            'general' => [],
            'exam' => [],
            'proctoring' => [],
            'ai' => []
        ];

        foreach ($rows as $row) {
            $g = $row['setting_group'];
            if (!isset($grouped[$g])) {
                $grouped[$g] = [];
            }
            $grouped[$g][$row['setting_key']] = $row;
        }

        return $grouped;
    }

    /**
     * Get Proctoring event weights for ProctoringRiskService
     *
     * @return array
     */
    public static function getProctoringWeights(): array
    {
        return [
            'FACE_NOT_DETECTED' => (int)self::get('proctoring_weight_face_not_detected', 10),
            'MULTIPLE_FACE' => (int)self::get('proctoring_weight_multiple_face', 40),
            'CAMERA_DISCONNECTED' => (int)self::get('proctoring_weight_camera_disconnected', 20),
            'CAMERA_BLOCKED' => (int)self::get('proctoring_weight_camera_blocked', 15),
            'SUSPICIOUS_GAZE' => (int)self::get('proctoring_weight_suspicious_gaze', 15),
            'TAB_SWITCH' => (int)self::get('proctoring_weight_tab_switch', 10),
            'FACE_CHANGED' => (int)self::get('proctoring_weight_face_changed', 50)
        ];
    }

    /**
     * Get Proctoring risk level thresholds
     *
     * @return array
     */
    public static function getProctoringThresholds(): array
    {
        return [
            'normal_max' => (int)self::get('proctoring_threshold_normal_max', 29),
            'warning_max' => (int)self::get('proctoring_threshold_warning_max', 59),
            'high_risk_min' => (int)self::get('proctoring_threshold_high_risk_min', 60)
        ];
    }

    /**
     * Get default settings for new exam sessions
     *
     * @return array
     */
    public static function getExamDefaults(): array
    {
        return [
            'duration_minutes' => (int)self::get('exam_default_duration', 90),
            'passing_score' => (float)self::get('exam_default_passing_score', 75.00),
            'is_random_question' => (int)self::get('exam_default_random_question', 1),
            'is_random_option' => (int)self::get('exam_default_random_option', 1),
            'enable_proctoring' => (int)self::get('exam_default_enable_proctoring', 1),
            'show_result' => (int)self::get('exam_default_show_result', 1)
        ];
    }

    /**
     * Get current SMTP configuration details with safety masking
     *
     * @return array
     */
    public static function getSmtpConfig(): array
    {
        $host = getenv('MAIL_HOST') ?: (defined('MAIL_HOST') ? MAIL_HOST : 'smtp.gmail.com');
        $port = (int)(getenv('MAIL_PORT') ?: (defined('MAIL_PORT') ? MAIL_PORT : 587));
        $encryption = getenv('MAIL_ENCRYPTION') ?: (defined('MAIL_ENCRYPTION') ? MAIL_ENCRYPTION : 'tls');
        $username = getenv('MAIL_USERNAME') ?: (defined('MAIL_USERNAME') ? MAIL_USERNAME : '');
        $password = getenv('MAIL_PASSWORD') ?: (defined('MAIL_PASSWORD') ? MAIL_PASSWORD : '');
        $fromAddress = getenv('MAIL_FROM_ADDRESS') ?: (defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : $username);
        $fromName = getenv('MAIL_FROM_NAME') ?: (defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : (defined('APP_NAME') ? APP_NAME : 'AI Online Exam'));

        $maskedUsername = '';
        if (!empty($username)) {
            $parts = explode('@', $username);
            if (count($parts) === 2) {
                $name = $parts[0];
                $domain = $parts[1];
                $visible = substr($name, 0, 3);
                $maskedUsername = $visible . '***@' . $domain;
            } else {
                $maskedUsername = substr($username, 0, 3) . '***';
            }
        }

        return [
            'host' => $host,
            'port' => $port,
            'encryption' => $encryption,
            'username' => $username,
            'username_masked' => $maskedUsername ?: '(Belum diatur)',
            'has_password' => !empty($password),
            'password_masked' => !empty($password) ? '●●●●●●●● (Tersimpan aman)' : '(Belum diatur)',
            'from_address' => $fromAddress,
            'from_name' => $fromName,
            'is_configured' => (!empty($username) && !empty($password))
        ];
    }

    /**
     * Safely updates .env file without destroying comments, structure, or blank passwords
     *
     * @param array<string, string|null> $keyValues
     * @return bool
     */
    public static function updateEnvFile(array $keyValues): bool
    {
        $envPath = dirname(__DIR__, 2) . '/.env';
        if (!file_exists($envPath)) {
            // Fallback: check if .env.example exists to clone it, or create new
            $examplePath = dirname(__DIR__, 2) . '/.env.example';
            if (file_exists($examplePath)) {
                copy($examplePath, $envPath);
            } else {
                file_put_contents($envPath, "# AI Online Exam & Proctoring System Environment\n\n");
            }
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return false;
        }

        $updatedKeys = [];
        $newLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
                $newLines[] = $line;
                continue;
            }

            [$key, $currVal] = explode('=', $trimmed, 2);
            $key = trim($key);

            if (array_key_exists($key, $keyValues)) {
                $newVal = $keyValues[$key];

                // CRITICAL SAFETY: If the new value is null or empty string for secret keys, PRESERVE old value!
                if (($key === 'MAIL_PASSWORD' || $key === 'GEMINI_API_KEY') && ($newVal === null || $newVal === '')) {
                    $newLines[] = $line; // Keep original password
                    $updatedKeys[$key] = true;
                    continue;
                }

                // Format value cleanly
                if (str_contains((string)$newVal, ' ') || str_contains((string)$newVal, '#')) {
                    $formattedVal = '"' . addcslashes((string)$newVal, '"') . '"';
                } else {
                    $formattedVal = (string)$newVal;
                }

                $newLines[] = "{$key}={$formattedVal}";
                $updatedKeys[$key] = true;

                // Sync to current PHP runtime
                putenv("{$key}={$newVal}");
                $_ENV[$key] = $newVal;
                $_SERVER[$key] = $newVal;
            } else {
                $newLines[] = $line;
            }
        }

        // Append any new keys that were not yet present in .env
        foreach ($keyValues as $k => $v) {
            if (!isset($updatedKeys[$k]) && $v !== null && $v !== '') {
                if (str_contains((string)$v, ' ') || str_contains((string)$v, '#')) {
                    $formattedVal = '"' . addcslashes((string)$v, '"') . '"';
                } else {
                    $formattedVal = (string)$v;
                }
                $newLines[] = "{$k}={$formattedVal}";
                putenv("{$k}={$v}");
                $_ENV[$k] = $v;
                $_SERVER[$k] = $v;
            }
        }

        $content = implode("\n", $newLines) . "\n";
        return file_put_contents($envPath, $content, LOCK_EX) !== false;
    }
}
