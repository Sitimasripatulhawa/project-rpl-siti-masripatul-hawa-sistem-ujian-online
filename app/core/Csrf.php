<?php
/**
 * CSRF Protection Helper
 * AI Online Exam & Proctoring System (Tahap 13)
 */

class Csrf
{
    const SESSION_TOKEN_KEY = 'csrf_token';

    /**
     * Generate or return existing CSRF token for the session
     *
     * @return string
     */
    public static function getToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION[self::SESSION_TOKEN_KEY])) {
            $_SESSION[self::SESSION_TOKEN_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_TOKEN_KEY];
    }

    /**
     * Regenerate CSRF token (e.g. after login or logout)
     *
     * @return string
     */
    public static function regenerateToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION[self::SESSION_TOKEN_KEY] = bin2hex(random_bytes(32));
        return $_SESSION[self::SESSION_TOKEN_KEY];
    }

    /**
     * Render hidden input field for HTML forms
     *
     * @return string
     */
    public static function tokenField(): string
    {
        $token = htmlspecialchars(self::getToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    /**
     * Alias for tokenField() for convenience and compatibility
     *
     * @return string
     */
    public static function field(): string
    {
        return self::tokenField();
    }

    /**
     * Render meta tag for JavaScript AJAX requests
     *
     * @return string
     */
    public static function tokenMeta(): string
    {
        $token = htmlspecialchars(self::getToken(), ENT_QUOTES, 'UTF-8');
        return '<meta name="csrf-token" content="' . $token . '">';
    }

    /**
     * Verify submitted token against session token
     *
     * @param string|null $submittedToken
     * @return bool
     */
    public static function verify(?string $submittedToken): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $sessionToken = $_SESSION[self::SESSION_TOKEN_KEY] ?? null;

        if (empty($sessionToken) || empty($submittedToken)) {
            return false;
        }

        return hash_equals($sessionToken, $submittedToken);
    }
}
