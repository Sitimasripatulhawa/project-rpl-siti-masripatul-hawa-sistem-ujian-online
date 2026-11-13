<?php
/**
 * Application Configuration
 * AI Online Exam & Proctoring System
 */

// Lightweight .env loader if .env file exists in project root
$envFile = dirname(__DIR__, 2) . '/.env';
$hasEnvFile = file_exists($envFile);
if (!defined('ENV_FILE_EXISTS')) {
    define('ENV_FILE_EXISTS', $hasEnvFile);
}

if ($hasEnvFile) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v, " \t\n\r\0\x0B\"'");
        
        $currentVal = getenv($k);
        if ($currentVal === false || $currentVal === '') {
            putenv("{$k}={$v}");
            $_ENV[$k] = $v;
            $_SERVER[$k] = $v;
        }
    }
}

// Application Info
define('APP_NAME', getenv('APP_NAME') ?: 'AI Online Exam & Proctoring System');
define('APP_SHORT_NAME', getenv('APP_SHORT_NAME') ?: 'AI-Exam');
define('APP_VERSION', '1.0.0');

// Base URL Configuration
// Auto-detect base URL for local development
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$baseUrl = rtrim($protocol . $host . $scriptDir, '/');

// If accessed directly from root or public folder, normalize to project root URL
if (substr($baseUrl, -7) === '/public') {
    $baseUrl = substr($baseUrl, 0, -7);
}

define('BASE_URL', $baseUrl);

// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') ?: 'db_ai_exam');

// Google Gemini AI Configuration
// Dapatkan API Key gratis di: https://aistudio.google.com/
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');
define('GEMINI_MODEL', getenv('GEMINI_MODEL') ?: 'gemini-3.8-flash');
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/' . GEMINI_MODEL . ':generateContent');

// Default Timezone
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Jakarta');

// Email & SMTP Configuration (Gmail SMTP or Custom SMTP)
define('MAIL_HOST', getenv('MAIL_HOST') ?: 'smtp.gmail.com');
define('MAIL_PORT', (int)(getenv('MAIL_PORT') ?: 587));
define('MAIL_USERNAME', getenv('MAIL_USERNAME') ?: '');
define('MAIL_PASSWORD', getenv('MAIL_PASSWORD') ?: '');
define('MAIL_ENCRYPTION', getenv('MAIL_ENCRYPTION') ?: 'tls');
define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS') ?: (getenv('MAIL_USERNAME') ?: 'noreply@sekolah.sch.id'));
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: APP_NAME);

// WhatsApp Gateway Configuration (Fonnte API - https://fonnte.com)
define('FONNTE_TOKEN', getenv('FONNTE_TOKEN') ?: '');
define('FONNTE_API_URL', getenv('FONNTE_API_URL') ?: 'https://api.fonnte.com/send');

// Session config
if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
    session_start();
}
