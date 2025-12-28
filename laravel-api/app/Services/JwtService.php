<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * JWT Service - Token generation and validation
 */
class JwtService
{
    private string $secret;
    private int $ttl;
    private string $algo;

    public function __construct()
    {
        $this->secret = config('jwt.secret');
        $this->ttl = config('jwt.ttl', 10080); // 7 days in minutes
        $this->algo = config('jwt.algo', 'HS256');
    }

    /**
     * Generate JWT token for user
     */
    public function generateUserToken(array $user): string
    {
        $payload = [
            'id' => $user['id'],
            'email' => $user['email'],
            'type' => 'user',
            'iat' => time(),
            'exp' => time() + ($this->ttl * 60),
        ];

        return JWT::encode($payload, $this->secret, $this->algo);
    }

    /**
     * Generate JWT token for admin
     */
    public function generateAdminToken(array $admin): string
    {
        $payload = [
            'id' => $admin['id'],
            'username' => $admin['username'],
            'role' => 'admin',
            'type' => 'admin',
            'iat' => time(),
            'exp' => time() + ($this->ttl * 60),
        ];

        return JWT::encode($payload, $this->secret, $this->algo);
    }

    /**
     * Verify and decode JWT token
     * @return array|null Decoded payload or null if invalid
     */
    public function verifyToken(?string $token): ?array
    {
        if (!$token) {
            return null;
        }

        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algo));
            return (array) $decoded;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extract token from Authorization header
     */
    public function extractTokenFromHeader(?string $authHeader): ?string
    {
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        return substr($authHeader, 7);
    }

    /**
     * Verify user token (type = 'user')
     */
    public function verifyUserToken(?string $token): ?array
    {
        $payload = $this->verifyToken($token);
        
        if (!$payload || ($payload['type'] ?? null) !== 'user') {
            return null;
        }

        return $payload;
    }

    /**
     * Verify admin token (role = 'admin')
     */
    public function verifyAdminToken(?string $token): ?array
    {
        $payload = $this->verifyToken($token);
        
        if (!$payload || ($payload['role'] ?? null) !== 'admin') {
            return null;
        }

        return $payload;
    }
}