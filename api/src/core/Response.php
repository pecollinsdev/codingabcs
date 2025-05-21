<?php

namespace App\Core;

/**
 * Response class to handle HTTP responses
 */
class Response
{
    private mixed $data;
    private int $statusCode;
    private array $headers = [];
    private string $contentType = 'application/json; charset=utf-8';

    public function __construct(mixed $data = null, int $statusCode = 200)
    {
        $this->data = $data;
        $this->statusCode = $statusCode;
        $this->setHeader('Content-Type', $this->contentType);
    }

    /**
     * Set a response header
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Set response status code
     */
    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Set response content type (JSON, HTML, plain text)
     */
    public function setContentType(string $type): self
    {
        $this->contentType = $type;
        return $this->setHeader('Content-Type', $type);
    }

    /**
     * Set response data
     */
    public function setData(mixed $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Send the response
     */
    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        // Handle empty responses like 204 No Content
        if ($this->statusCode === 204 || $this->data === null) {
            return;
        }

        // Choose format based on content type
        if (str_contains($this->contentType, 'application/json')) {
            $json = json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'JSON encoding failed']);
            } else {
                echo $json;
            }
        } elseif (str_contains($this->contentType, 'text/plain')) {
            echo is_string($this->data) ? $this->data : print_r($this->data, true);
        } elseif (str_contains($this->contentType, 'text/html')) {
            echo $this->data;
        }
    }

    /**
     * Send response and terminate script
     */
    public function sendAndExit(): void
    {
        $this->send();
        exit;
    }

    /**
     * Create a structured success response
     *
     * @param array|object $data     The main payload data
     * @param array        $meta     Optional metadata (pagination, etc.)
     * @param int          $statusCode HTTP status code (default 200)
     * @return self
     */
    public static function success(array|object $data, array $meta = [], int $statusCode = 200): self
    {
        $response = [
            'status' => 'success',
            'data'   => $data,
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return new self($response, $statusCode);
    }

    /**
     * Create an error response
     */
    public static function error(string $message, int $statusCode = 400, array $errors = [], array $data = []): self
    {
        $response = [
            'status' => 'error',
            'message' => $message
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        if (!empty($data)) {
            $response = array_merge($response, $data);
        }

        return new self($response, $statusCode);
    }

    /**
     * Create a 404 not found response
     */
    public static function notFound(string $message = 'Resource not found'): self
    {
        return self::error($message, 404);
    }

    /**
     * Create a 401 unauthorized response
     */
    public static function unauthorized(string $message = 'Unauthorized access'): self
    {
        return self::error($message, 401);
    }

    /**
     * Create a 403 forbidden response
     */
    public static function forbidden(string $message = 'Access forbidden'): self
    {
        return self::error($message, 403);
    }

    /**
     * Create a 422 validation error response
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): self
    {
        return self::error($message, 422, $errors);
    }

    /**
     * Create a 204 No Content response
     */
    public static function noContent(): self
    {
        return new self(null, 204);
    }
}
