<?php
namespace App\Core;

/*
 * Session class for secure session handling.
 *
 * This class provides methods to start, regenerate, set, get, check, and destroy sessions securely.
 */
class Session {
    /**
     * Start the session securely
     */
    public static function start() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Regenerate the session ID securely
     * @param bool $deleteOldSession - Whether to delete the old session ID
     */
    public static function regenerate($deleteOldSession = true) {
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_regenerate_id($deleteOldSession);
            $_SESSION['last_regeneration'] = time(); // Store timestamp
        }
    }

    /**
     * Automatically regenerate session ID every X seconds (default: 5 minutes)
     * @param int $interval - Interval in seconds
     */
    public static function autoRegenerate($interval = 300) { // 300 sec = 5 min
        if (!isset($_SESSION['last_regeneration'])) {
            self::regenerate();
        } elseif (time() - $_SESSION['last_regeneration'] > $interval) {
            self::regenerate();
        }
    }

    /**
     * Set a session variable
     * @param string $key
     * @param mixed $value
     */
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    /**
     * Get a session variable
     * @param string $key
     * @return mixed|null
     */
    public static function get($key) {
        return $_SESSION[$key] ?? null;
    }

    /**
     * Check if a session variable exists
     * @param string $key
     * @return bool
     */
    public static function has($key) {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a session variable
     * @param string $key
     */
    public static function remove($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Destroy the entire session
     */
    public static function destroy() {
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
    }
}
