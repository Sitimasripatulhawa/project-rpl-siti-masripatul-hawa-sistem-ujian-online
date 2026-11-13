<?php
/**
 * Application Core Router / Dispatcher
 * AI Online Exam & Proctoring System
 */

class App
{
    /** @var object|string */
    protected $controller = 'HomeController';
    protected string $method = 'index';
    protected array $params = [];

    public function __construct()
    {
        $url = $this->parseUrl();

        // 1. Determine Controller
        if (!empty($url[0])) {
            $controllerName = ucfirst($url[0]) . 'Controller';
            $controllerFile = '../app/controllers/' . $controllerName . '.php';
            if (!file_exists($controllerFile)) {
                $controllerFile = __DIR__ . '/../controllers/' . $controllerName . '.php';
            }

            if (file_exists($controllerFile)) {
                $this->controller = $controllerName;
                unset($url[0]);
            }
        }

        // Require the Controller file
        $controllerFile = '../app/controllers/' . $this->controller . '.php';
        if (!file_exists($controllerFile)) {
            $controllerFile = __DIR__ . '/../controllers/' . $this->controller . '.php';
        }
        require_once $controllerFile;

        // Instantiate Controller
        $controllerInstance = new $this->controller();

        // 2. Determine Method
        if (isset($url[1])) {
            if (method_exists($controllerInstance, $url[1])) {
                $this->method = $url[1];
                unset($url[1]);
            }
        }

        // 3. Collect Parameters
        $this->params = $url ? array_values($url) : [];

        // 4. Dispatch to Controller Method
        call_user_func_array([$controllerInstance, $this->method], $this->params);
    }

    /**
     * Parse and sanitize request URL into array
     *
     * @return array
     */
    protected function parseUrl(): array
    {
        if (isset($_GET['url'])) {
            $url = rtrim($_GET['url'], '/');
            $url = filter_var($url, FILTER_SANITIZE_URL);
            return explode('/', $url);
        }

        // Fallback: Parse from REQUEST_URI if .htaccess rewrite isn't passing $_GET['url']
        $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseDir = dirname($scriptName);

        // Remove base directory or /public from URI
        if ($baseDir !== '/' && strpos($requestUri, $baseDir) === 0) {
            $path = substr($requestUri, strlen($baseDir));
        } else {
            $path = $requestUri;
        }

        $path = trim(str_replace('index.php', '', $path), '/');
        if (!empty($path)) {
            $path = filter_var($path, FILTER_SANITIZE_URL);
            return explode('/', $path);
        }

        return [];
    }
}
