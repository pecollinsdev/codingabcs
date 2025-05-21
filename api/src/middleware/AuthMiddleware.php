<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\JwtService;

class AuthMiddleware
{
    // List of public routes that don't require authentication
    private static $publicRoutes = [
        '/register',
        '/login',
        '/auth/validate',
        '/config'
    ];

    public static function handle(Request $request): ?Response
    {
        // Check if the current route is public
        $currentPath = $request->getUri();
        if (in_array($currentPath, self::$publicRoutes)) {
            return null; // Allow access to public routes
        }

        // First check for token in cookies
        $token = $_COOKIE['jwt_token'] ?? null;
        if ($token) {
            $authHeader = 'Bearer ' . $token;
        } else {
            // If no token in cookies, check Authorization header
            $authHeader = $request->getHeader('Authorization');
        }

        if (!$authHeader) {
            return Response::unauthorized('Missing or invalid Authorization header');
        }

        // Normalize the header
        if (!str_starts_with($authHeader, 'Bearer ')) {
            $authHeader = 'Bearer ' . ltrim($authHeader);
        }

        $token = substr($authHeader, 7); // Strip "Bearer "

        try {
            $user = JwtService::verify($token);
            
            // Ensure we have the required user data
            if (!isset($user['id']) || !isset($user['email']) || !isset($user['role'])) {
                return Response::unauthorized('Invalid token data');
            }
            
            $request->setUser($user);
            return null; // Allow access
        } catch (\Throwable $e) {
            return Response::unauthorized('Invalid or expired token');
        }
    }
}
