<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

class JwtService
{
    /**
     * Secret key for signing tokens
     */
    private static string $secret = '';

    /**
     * Algorithm used to sign the token
     */
    private static string $algo = 'HS256';

    /**
     * Token expiration time (in seconds)
     */
    private static int $expiry = 86400; // Default: 1 day

    /**
     * Prevent direct instantiation
     */
    private function __construct() {}

    /**
     * Configure static settings (can be used in index.php)
     *
     * @param string $secret
     * @param string $algo
     * @param int $expiry
     */
    public static function configure(string $secret, string $algo = 'HS256', int $expiry = 86400): void
    {
        self::$secret = $secret;
        self::$algo = $algo;
        self::$expiry = $expiry;
    }

    /**
     * Load from environment variables (fallback if needed)
     */
    public static function configureFromEnv(): void
    {
        self::$secret = getenv('JWT_SECRET') ?: 'your-default-secret';
        self::$algo   = getenv('JWT_ALGORITHM') ?: 'HS256';
        self::$expiry = (int) (getenv('JWT_EXPIRATION_TIME') ?: 86400);
        
    }

    /**
     * Get the JWT secret key
     */
    public static function getSecret(): string
    {
        return self::$secret;
    }

    /**
     * Get the JWT algorithm
     */
    public static function getAlgorithm(): string
    {
        return self::$algo;
    }

    /**
     * Get the JWT expiration time in seconds
     */
    public static function getExpirationTime(): int
    {
        return self::$expiry;
    }

    /**
     * Generate a JWT token for the given user
     *
     * @param array $payload (e.g., ['id' => 1, 'role' => 'admin'])
     * @return string
     */
    public static function generate(array $payload): string
    {
        $issuedAt = time();
        $expireAt = $issuedAt + self::$expiry;

        $payload = array_merge($payload, [
            'iat' => $issuedAt,
            'exp' => $expireAt
        ]);

        return JWT::encode($payload, self::$secret, self::$algo);
    }

    /**
     * Verify and decode a JWT token
     *
     * @param string $token
     * @return array
     * @throws \Exception
     */
    public static function verify(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key(self::$secret, self::$algo));
            return (array) $decoded;
        } catch (ExpiredException $e) {
            throw new \Exception('Token has expired');
        } catch (\Throwable $e) {
            throw new \Exception('Invalid token');
        }
    }
}
