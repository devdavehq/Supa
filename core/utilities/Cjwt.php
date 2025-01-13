<?php

require_once 'vendor/autoload.php'; // Ensure Composer's autoload is included

use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;

class Cjwt {
    private $secret;

    public function __construct($secret) {
        $this->secret = $secret;
    }

    // Generate a JWT token
    public function generateToken(array $payload, int $expiry = 3600): string {
        // Set expiration time
        $payload['exp'] = time() + $expiry;
        return JWT::encode($payload, $this->secret);
    }

    // Verify a JWT token
    public function verifyToken(string $token): ?array {
        try {
            return (array) JWT::decode($token, $this->secret, ['HS256']);
        } catch (ExpiredException $e) {
            // Token has expired
            return null;
        } catch (\Exception $e) {
            // Token is invalid
            return null;
        }
    }

    // Get the entire payload from the token
    public function getPayloadFromToken(string $token): ?array {
        return $this->verifyToken($token);
    }

    // Check if the token is expired
    public function isTokenExpired(string $token): bool {
        $payload = $this->verifyToken($token);
        return $payload === null || (isset($payload['exp']) && $payload['exp'] < time());
    }

    // Optionally, implement refresh token functionality
    public function refreshToken(string $token, int $newExpiry = 3600): ?string {
        $payload = $this->verifyToken($token);
        if ($payload) {
            // Remove the exp claim and set a new expiration time
            unset($payload['exp']);
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