<?php

namespace App\Core;

class Session
{
    /**
     * Start the session securely with proper cookie settings.
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_strict_mode', '1');
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '', // Optional: set for your domain
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict',
            ]);

            session_start();
            self::bindToUserAgent();
            self::cleanupFlash();
        }
    }

    /**
     * Regenerate the session ID to prevent fixation.
     */
    public static function regenerate(bool $deleteOldSession = true): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id($deleteOldSession);
            $_SESSION['last_regeneration'] = time();
        }
    }

    /**
     * Auto-regenerate session ID every X seconds.
     */
    public static function autoRegenerate(int $interval = 300): void
    {
        if (!isset($_SESSION['last_regeneration']) || time() - $_SESSION['last_regeneration'] > $interval) {
            self::regenerate();
        }
    }

    /**
     * Bind session to user-agent for hijack protection.
     */
    public static function bindToUserAgent(): void
    {
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        if (!isset($_SESSION['user_agent'])) {
            $_SESSION['user_agent'] = $agent;
        } elseif ($_SESSION['user_agent'] !== $agent) {
            self::destroy();
            exit('Session hijack detected. Please log in again.');
        }
    }

    /**
     * Set a session key (supports namespace).
     */
    public static function set(string $key, $value, ?string $namespace = null): void
    {
        if ($namespace) {
            $_SESSION[$namespace][$key] = $value;
        } else {
            $_SESSION[$key] = $value;
        }
    }

    /**
     * Get a session value.
     */
    public static function get(string $key, ?string $namespace = null)
    {
        return $namespace
            ? ($_SESSION[$namespace][$key] ?? null)
            : ($_SESSION[$key] ?? null);
    }

    /**
     * Check if a session key exists.
     */
    public static function has(string $key, ?string $namespace = null): bool
    {
        return $namespace
            ? isset($_SESSION[$namespace][$key])
            : isset($_SESSION[$key]);
    }

    /**
     * Remove a session key.
     */
    public static function remove(string $key, ?string $namespace = null): void
    {
        if ($namespace) {
            unset($_SESSION[$namespace][$key]);
        } else {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Destroy the session completely.
     */
    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
        }
    }

    /**
     * Get all session values.
     */
    public static function getAll(): array
    {
        return $_SESSION;
    }

    /**
     * Set or get a flash message (available for one request).
     */
    public static function flash(string $key, $value = null)
    {
        if ($value !== null) {
            $_SESSION['_flash_next'][$key] = $value;
            return null;
        }

        $val = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $val;
    }

    /**
     * Move flash data forward one request, clearing expired.
     */
    private static function cleanupFlash(): void
    {
        $_SESSION['_flash'] = $_SESSION['_flash_next'] ?? [];
        unset($_SESSION['_flash_next']);
    }
}
