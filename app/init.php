<?php
/**
 * Application Initializer / Bootstrap
 * AI Online Exam & Proctoring System
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Csrf.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Model.php';
require_once __DIR__ . '/core/Controller.php';
require_once __DIR__ . '/core/App.php';
require_once __DIR__ . '/services/ProctoringRiskService.php';

// Class Autoloader for models, services, controllers
spl_autoload_register(function ($className) {
    $paths = [
        __DIR__ . '/models/' . $className . '.php',
        __DIR__ . '/services/' . $className . '.php',
        __DIR__ . '/core/' . $className . '.php',
        __DIR__ . '/controllers/' . $className . '.php',
    ];
    foreach ($paths as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Global HTML escaping helper for XSS defense
if (!function_exists('e')) {
    function e(?string $str): string
    {
        return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
    }
}

// HTTP Security Headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Initialize session & auth listener
Auth::init();

