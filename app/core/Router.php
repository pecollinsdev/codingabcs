<?php
namespace App\Core;

/**
 * Router class handles routing and dispatching requests.
 *
 * This class is responsible for registering routes and dispatching requests
 * to the appropriate controller and action based on the URL and HTTP method.
 */
class Router {
    // Registered routes
    private $routes = [];

    /**
     * Registers a route.
     *
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $route Route pattern (e.g., 'quiz/confirm/{quizId}')
     * @param string $controllerAction Controller and action (e.g., 'QuizController@confirm')
     */
    public function add($method, $route, $controllerAction) {
        $this->routes[strtoupper($method)][$route] = $controllerAction;
    }

    /**
     * Dispatch the request to the appropriate controller/action.
     *
     * @param string $url The requested URL (without the base path)
     * @param string $method HTTP method (GET, POST, etc.)
     */
    public function dispatch($url, $method) {
        $method = strtoupper($method);
        $url = trim($url, '/');

        // Attempt to match dynamic routes first
        if (isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $route => $controllerAction) {
                if (strpos($route, '{') !== false) {
                    // Convert placeholders to regex (e.g., {quizId} → ([^/]+))
                    $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $route);
                    $pattern = "#^" . $pattern . "$#";

                    if (preg_match($pattern, $url, $matches)) {
                        array_shift($matches); // Remove full match from the matches array

                        list($controllerName, $action) = explode('@', $controllerAction);
                        $controllerClass = "App\\Controllers\\$controllerName";

                        if (class_exists($controllerClass)) {
                            $controller = new $controllerClass();
                            if (method_exists($controller, $action)) {
                                // Ensure numeric IDs for security
                                $matches = array_map('htmlspecialchars', $matches);
                                return call_user_func_array([$controller, $action], $matches);
                            }
                        }
                    }
                }
            }
        }

        // Check for an exact route match
        if (isset($this->routes[$method][$url])) {
            list($controllerName, $action) = explode('@', $this->routes[$method][$url]);
            $controllerClass = "App\\Controllers\\$controllerName";

            if (class_exists($controllerClass)) {
                $controller = new $controllerClass();
                if (method_exists($controller, $action)) {
                    return $controller->$action();
                }
            }
        }

        // No route found – Display a custom 404 error page
        http_response_code(404);
        require_once '../app/views/errors/404.php';
        exit;
    }
}
