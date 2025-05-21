<?php

namespace App\Core;

/**
 * Class Request
 * 
 * Handles HTTP request data and headers for API interactions
 */
class Request
{
    private ?array $data = null;
    private array $headers = [];
    private string $method;
    private string $uri;
    private bool $debugMode;
    public ?array $user = null;
    private array $routeParams = [];

    /**
     * Constructor initializes the request context
     *
     * @param bool $debugMode Enable verbose logging (optional)
     */
    public function __construct(bool $debugMode = false)
    {
        $this->debugMode = $debugMode;
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Get the path from REQUEST_URI, removing query string
        $requestUri = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
        
        // Remove the base path if it exists
        $basePath = '/codingabcs/api/public';
        if (strpos($requestUri, $basePath) === 0) {
            $requestUri = substr($requestUri, strlen($basePath));
        }
        
        // Remove index.php if it exists
        if (strpos($requestUri, '/index.php') === 0) {
            $requestUri = substr($requestUri, strlen('/index.php'));
        }
        
        // Remove leading and trailing slashes
        $this->uri = trim($requestUri, '/');
        
        
        $this->headers = $this->getRequestHeaders();
        $this->parseRequestData();
    }

    /**
     * Collect and normalize request headers
     *
     * @return array
     */
    public function getRequestHeaders(): array
    {
        $headers = [];


        // First, get all HTTP_* headers
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $normalized = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$normalized] = $value;
            }
        }

        // Special handling for Authorization header
        $authHeader = null;
        
        // Check various possible locations for the Authorization header
        $possibleAuthKeys = [
            'HTTP_AUTHORIZATION',
            'REDIRECT_HTTP_AUTHORIZATION',
            'Authorization',
            'authorization',
            'HTTP_X_AUTHORIZATION',
            'X-Authorization'
        ];

        foreach ($possibleAuthKeys as $key) {
            if (isset($_SERVER[$key])) {
                $authHeader = $_SERVER[$key];
                break;
            }
        }

        // If not found in $_SERVER, check the headers array
        if (!$authHeader && isset($headers['authorization'])) {
            $authHeader = $headers['authorization'];
        }

        // If still no Authorization header, check for token in cookies
        if (!$authHeader && isset($_COOKIE['jwt_token'])) {
            $authHeader = 'Bearer ' . $_COOKIE['jwt_token'];
        }

        // If we found an Authorization header, ensure it's properly set
        if ($authHeader) {
            // Ensure the header starts with 'Bearer '
            if (!str_starts_with($authHeader, 'Bearer ')) {
                $authHeader = 'Bearer ' . ltrim($authHeader);
            }
            $headers['Authorization'] = $authHeader;
        }

        return $headers;
    }

    /**
     * Parses input depending on content type
     *
     * Supports:
     * - application/json
     * - application/x-www-form-urlencoded
     * - multipart/form-data
     */
    private function parseRequestData(): void
    {
        $contentType = $this->headers['Content-Type'] ?? '';

        if (str_starts_with($contentType, 'application/json')) {
            $input = file_get_contents('php://input');
            if (!empty($input)) {
                $this->data = json_decode($input, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \RuntimeException('Invalid JSON input: ' . json_last_error_msg());
                }
            }
        } elseif (str_starts_with($contentType, 'application/x-www-form-urlencoded')) {
            $this->data = $_POST;
        } elseif (str_starts_with($contentType, 'multipart/form-data')) {
            $this->data = array_merge($_POST, $_FILES);
        } else {
            // Fallback: try reading input as JSON
            $input = file_get_contents('php://input');
            $this->data = json_decode($input, true);
        }
    }

    /**
     * Get all parsed request data
     *
     * @return array|null
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * Get a specific field from parsed request data
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Get a specific request header
     *
     * @param string $name Header name
     * @return string|null
     */
    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * Get request method (GET, POST, etc.)
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get raw request URI (path only)
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get all query parameters ($_GET)
     *
     * @return array
     */
    public function getQueryParams(): array
    {
        return filter_input_array(INPUT_GET, FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? [];
    }

    /**
     * Get a single query parameter by name
     *
     * @param string $name
     * @return mixed
     */
    public function getQueryParam(string $name): mixed
    {
        return filter_input(INPUT_GET, $name, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    }

    /**
     * Get all request headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get uploaded files ($_FILES)
     *
     * @return array
     */
    public function getFiles(): array
    {
        return $_FILES;
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function getParam(string $key): mixed
    {
        return $this->routeParams[$key] ?? null;
    }

    /**
     * Get the authenticated user data
     *
     * @return array|null
     */
    public function getUser(): ?array
    {
        return $this->user;
    }

    /**
     * Set the authenticated user data
     *
     * @param array $user
     * @return void
     */
    public function setUser(array $user): void
    {
        $this->user = $user;
    }

    /**
     * Check if the request has an authenticated user
     *
     * @return bool
     */
    public function hasUser(): bool
    {
        return $this->user !== null;
    }
}
