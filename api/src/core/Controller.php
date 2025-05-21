<?php

namespace App\Core;

abstract class Controller
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    protected function user(): ?array
    {
        return $this->request->user;
    }

    protected function respond(mixed $data, int $statusCode = 200): void
    {
        Response::success($data, [], $statusCode)->send();
    }

    protected function respondCreated(mixed $data): void
    {
        Response::success($data, [], 201)->send();
    }

    protected function respondNoContent(): void
    {
        Response::noContent()->send();
    }

    protected function respondError(string|array $message, int $statusCode = 400, array $errors = []): void
    {
        if (is_array($message)) {
            Response::error($message['message'], $statusCode, $errors, $message)->send();
        } else {
            Response::error($message, $statusCode, $errors)->send();
        }
    }

    protected function respondNotFound(string $message = 'Resource not found'): void
    {
        Response::notFound($message)->send();
    }

    protected function respondUnauthorized(string $message = 'Unauthorized access'): void
    {
        Response::unauthorized($message)->send();
    }

    protected function respondForbidden(string $message = 'Access forbidden'): void
    {
        Response::forbidden($message)->send();
    }

    protected function respondValidationError(array $errors, string $message = 'Validation failed'): void
    {
        Response::validationError($errors, $message)->send();
    }
}
