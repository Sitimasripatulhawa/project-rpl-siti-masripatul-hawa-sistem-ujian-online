<?php
/**
 * Base Controller
 * AI Online Exam & Proctoring System
 */

abstract class Controller
{
    /**
     * Require authentication and optionally specific roles
     *
     * @param array|string $roles
     * @return void
     */
    protected function requireAuth($roles = []): void
    {
        Auth::requireRole($roles);
    }

    /**
     * Get currently authenticated user
     *
     * @return array|null
     */
    protected function currentUser(): ?array
    {
        return Auth::user();
    }

    /**
     * Check if user is logged in
     *
     * @return bool
     */
    protected function isLoggedIn(): bool
    {
        return Auth::check();
    }

    /**
     * Render a view file with optional data
     *
     * @param string $view View path relative to app/views (e.g. 'home/index' or 'auth/login')
     * @param array $data Associative array of data to extract into view scope
     * @return void
     */
    protected function view(string $view, array $data = []): void
    {
        // Add auth helper data to all views automatically
        $data['auth_user'] = Auth::user();
        $data['auth_check'] = Auth::check();
        $data['flash_message'] = Auth::getFlash();

        // Extract data variables to be directly accessible in view files
        extract($data);

        $viewPath = '../app/views/' . $view . '.php';
        if (!file_exists($viewPath)) {
            $viewPath = __DIR__ . '/../views/' . $view . '.php';
        }

        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            die("View not found: " . htmlspecialchars($view));
        }
    }

    /**
     * Load and instantiate a model class
     *
     * @param string $model Model class name
     * @return object Model instance
     */
    protected function model(string $model): object
    {
        $modelPath = '../app/models/' . $model . '.php';
        if (!file_exists($modelPath)) {
            $modelPath = __DIR__ . '/../models/' . $model . '.php';
        }

        if (file_exists($modelPath)) {
            require_once $modelPath;
            return new $model();
        } else {
            die("Model not found: " . htmlspecialchars($model));
        }
    }

    /**
     * Send a JSON response
     *
     * @param mixed $data
     * @param int $statusCode
     * @return void
     */
    protected function json($data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    /**
     * Redirect to another URL or route
     *
     * @param string $path Relative or absolute path
     * @return void
     */
    protected function redirect(string $path): void
    {
        if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
            header("Location: " . $path);
        } else {
            $path = ltrim($path, '/');
            header("Location: " . BASE_URL . '/' . $path);
        }
        exit;
    }

    /**
     * Verify CSRF Token from POST parameter, JSON body, or X-CSRF-Token header
     *
     * @param bool $terminateOnFail Whether to terminate and return 403 response if invalid
     * @return bool
     */
    protected function verifyCsrfToken(bool $terminateOnFail = true): bool
    {
        // 1. Cross-Site Origin & Fetch-Site Verification (OWASP CSRF Defense)
        $isCrossSite = false;

        if (!empty($_SERVER['HTTP_SEC_FETCH_SITE']) && $_SERVER['HTTP_SEC_FETCH_SITE'] === 'cross-site') {
            $isCrossSite = true;
        }

        $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
        if (!empty($origin)) {
            $originHost = parse_url($origin, PHP_URL_HOST);
            $serverHost = $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
            if (strpos($serverHost, ':') !== false) {
                $serverHost = explode(':', $serverHost)[0];
            }
            if (!empty($originHost) && strtolower($originHost) !== strtolower($serverHost)) {
                $isCrossSite = true;
            }
        }

        if ($isCrossSite) {
            if ($terminateOnFail) {
                $this->json(['success' => false, 'message' => 'Akses ditolak: Permintaan lintas situs (CSRF) terdeteksi.', 'csrf_error' => true], 403);
            }
            return false;
        }

        // 2. Synchronizer Token Verification
        $submittedToken = $_POST['csrf_token'] ?? null;

        if (!$submittedToken && !empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $submittedToken = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        if (!$submittedToken) {
            $rawJson = @file_get_contents('php://input');
            if ($rawJson) {
                $decoded = json_decode($rawJson, true);
                if (is_array($decoded) && isset($decoded['csrf_token'])) {
                    $submittedToken = $decoded['csrf_token'];
                }
            }
        }

        $isValid = true;
        if ($submittedToken !== null) {
            // When token is supplied, it MUST match valid session token
            $isValid = Csrf::verify($submittedToken);
        }

        if (!$isValid && $terminateOnFail) {
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                      || (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
                      || !empty($_SERVER['HTTP_X_CSRF_TOKEN']);

            if ($isAjax) {
                $this->json([
                    'success' => false,
                    'message' => 'Token keamanan (CSRF) tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.',
                    'csrf_error' => true
                ], 403);
            }

            Auth::setFlash('danger', 'Token keamanan (CSRF) tidak valid atau telah kedaluwarsa. Silakan coba lagi.');
            if (!empty($_SERVER['HTTP_REFERER'])) {
                $this->redirect($_SERVER['HTTP_REFERER']);
            } else {
                Auth::redirectToDashboard();
            }
        }

        return $isValid;
    }
}

