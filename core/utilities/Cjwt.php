<?php

require_once 'vendor/autoload.php'; // Ensure Composer's autoload is included

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

class Cjwt {
    private $secret;

    public function __construct(string $secret) {
        $this->secret = $secret;
    }

    /**
     * Generate a JWT token.
     *
     * @param array $payload The data to include in the token.
     * @param int $expiry Expiry time in seconds (default: 3600).
     * @return string The generated token.
     */
    public function generateToken(array $payload, int $expiry = 3600): string {
        $payload['exp'] = time() + $expiry; // Set expiration time
        return JWT::encode($payload, $this->secret, 'HS256'); // Specify the algorithm
    }

    /**
     * Verify a JWT token.
     *
     * @param string $token The JWT token to verify.
     * @return array|null The decoded payload if valid, or null if invalid.
     */
    public function verifyToken(string $token): ?array {
        try {
            return (array) JWT::decode($token, new Key($this->secret, 'HS256'));
        } catch (ExpiredException $e) {
            // Token has expired
            return null;
        } catch (\Exception $e) {
            // Token is invalid
            return null;
        }
    }

    /**
     * Get the payload from the token.
     *
     * @param string $token The JWT token.
     * @return array|null The payload if valid, or null otherwise.
     */
    public function getPayloadFromToken(string $token): ?array {
        return $this->verifyToken($token);
    }

    /**
     * Check if the token is expired.
     *
     * @param string $token The JWT token.
     * @return bool True if expired, false otherwise.
     */
    public function isTokenExpired(string $token): bool {
        $payload = $this->verifyToken($token);
        return $payload === null || (isset($payload['exp']) && $payload['exp'] < time());
    }

    /**
     * Refresh a JWT token by generating a new token with updated expiration.
     *
     * @param string $token The old JWT token.
     * @param int $newExpiry New expiry time in seconds.
     * @return string|null The refreshed token, or null if the old token is invalid.
     */
    public function refreshToken(string $token, int $newExpiry = 3600): ?string {
        $payload = $this->verifyToken($token);
        if ($payload) {
            unset($payload['exp']); // Remove the old expiration
            return $this->generateToken($payload, $newExpiry);
        }
        return null; // Token is invalid or expired
    }
}

// // Initialize the JWT wrapper with a secret key
// $jwtWrapper = new JwtWrapper('your-secret-key-here');

// // Generate a token
// $token = $jwtWrapper->generateToken([
//     'user_id' => 123,
//     'email' => 'user@example.com',
//     'role' => 'admin'
// ], 3600); // Expires in 1 hour

// // Verify the token and get the payload
// $payload = $jwtWrapper->getPayloadFromToken($token);

// if ($payload) {
//     // Token is valid
//     echo "User ID: " . $payload['user_id'];
// } else {
//     // Token is invalid or expired
//     echo "Invalid or expired token.";
// }

// // Check if the token is expired
// if ($jwtWrapper->isTokenExpired($token)) {
//     echo "Token has expired.";
// }

// // Refresh the token
// $newToken = $jwtWrapper->refreshToken($token, 7200); // New token with 2 hours expiry
// if ($newToken) {
//     echo "New Token: " . $newToken;
// } else {
//     echo "Failed to refresh token.";
// }