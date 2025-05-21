<?php

namespace App\Core;

/**
 * Router class handles HTTP routing for the API.
 */
class Router
{
    private array $routes = [];
    private string $basePath;

    public function __construct(string $basePath = '')
    {
        $this->basePath = trim($basePath, '/');
    }

    public function get(string $route, string $controllerAction, array $middleware = []): void
    {
        $this->add('GET', $route, $controllerAction, $middleware);
    }

    public function post(string $route, string $controllerAction, array $middleware = []): void
    {
        $this->add('POST', $route, $controllerAction, $middleware);
    }

    public function patch(string $route, string $controllerAction, array $middleware = []): void
    {
        $this->add('PATCH', $route, $controllerAction, $middleware);
    }

    public function delete(string $route, string $controllerAction, array $middleware = []): void
    {
        $this->add('DELETE', $route, $controllerAction, $middleware);
    }

    public function resource(string $base, string $controller): void
    {
        $this->get("/$base", "$controller@index");
        $this->get("/$base/{id}", "$controller@show");
        $this->post("/$base", "$controller@store");
        $this->patch("/$base/{id}", "$controller@update");
        $this->delete("/$base/{id}", "$controller@destroy");
    }

    private function add(string $method, string $route, string $controllerAction, array $middleware = []): void
    {
        $this->routes[$method][trim($route, '/')] = [
            'action' => $controllerAction,
            'middleware' => array_map(function($middlewareClass) {
                return is_string($middlewareClass) ? $middlewareClass : get_class($middlewareClass);
            }, $middleware)
        ];
    }

    public function dispatch(string $uri, string $method): void
    {
        $method = strtoupper($method);

        // Normalize URI
        $parsedUri = parse_url($uri, PHP_URL_PATH);
        $cleanedUri = str_replace([$this->basePath, 'index.php'], '', $parsedUri);
        $uri = trim($cleanedUri, '/');


        if (!isset($this->routes[$method])) {
            $this->jsonError('Method not allowed', 405);
            return;
        }

        // Create a single Request object for the entire dispatch process
        $request = new Request();

        // Direct match
        if (isset($this->routes[$method][$uri])) {
            $routeData = $this->routes[$method][$uri];

            // Handle middleware
            foreach ($routeData['middleware'] as $middlewareClass) {
                if (class_exists($middlewareClass)) {
                    $response = $middlewareClass::handle($request);
                    if ($response instanceof Response) {
                        $response->send();
                        return;
                    }
                }
            }

            $this->callController($routeData['action'], $request);
            return;
        }

        // Pattern match
        foreach ($this->routes[$method] as $route => $data) {
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route);
            $pattern = "#^$pattern$#";

            if (preg_match($pattern, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $request->setRouteParams($params);

                // Handle middleware
                foreach ($data['middleware'] as $middlewareClass) {
                    if (class_exists($middlewareClass)) {
                        $response = $middlewareClass::handle($request);
                        if ($response instanceof Response) {
                            $response->send();
                            return;
                        }
                    }
                }

                $this->callController($data['action'], $request);
                return;
            }
        }

        $this->jsonError('Not found', 404);
    }

    private function callController(string $controllerAction, Request $request, array $params = []): void
    {
        try {
            [$controllerName, $method] = explode('@', $controllerAction);
            $controllerClass = "App\\Controllers\\$controllerName";

            if (class_exists($controllerClass)) {
                $controller = new $controllerClass($request); // Pass the same Request object

                if (method_exists($controller, $method)) {
                    call_user_func_array([$controller, $method], array_merge([$request], $params));
                    return;
                }
            }
            
            $this->jsonError('Controller or method not found', 404);
        } catch (\RuntimeException $e) {
            $this->jsonError($e->getMessage(), 400);
        } catch (\Throwable $e) {
            $this->jsonError('Internal server error', 500);
        }
    }

    private function jsonError(string $message, int $code): void
    {
        http_response_code($code);
        echo json_encode(['error' => $message]);
    }
}