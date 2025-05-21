<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class RoleMiddleware
{
    /**
     * Enforces role-based access.
     *
     * @param Request $request
     * @param string $requiredRole "admin" or "user"
     */
    public static function handle(Request $request, string $requiredRole): ?Response
    {
        if (!isset($request->user)) {
            return Response::unauthorized('User not authenticated');
        }

        $userRole = $request->user['role'] ?? null;

        if ($userRole !== $requiredRole) {
            return Response::forbidden("You must be a(n) $requiredRole to access this resource");
        }

        return null; // Allow access
    }
}
