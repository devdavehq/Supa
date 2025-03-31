<?php

class Redirect {
    /**
     * Base URL for all redirects
     */
    private static $baseUrl = '';
    
    /**
     * Set the base URL for all redirects
     */
    public static function setBaseUrl($url) {
        self::$baseUrl = rtrim($url, '/');
    }
    
    /**
     * Redirect to a URL
     */
    public static function to($path, $statusCode = 302, $with = []) {
        $url = self::buildUrl($path);
        
        // Store flash data if any
        if (!empty($with)) {
            self::with($with);
        }
        
        header("Location: $url", true, $statusCode);
        exit();
    }
    
    /**
     * Redirect back to previous page
     */
    public static function back($with = []) {
        $referer = $_SERVER['HTTP_REFERER'] ?? self::$baseUrl ?: '/';
        
        if (!empty($with)) {
            self::with($with);
        }
        
        header("Location: $referer", true, 302);
        exit();
    }
    
    /**
     * Redirect with flash data
     */
    public static function with($data) {
        foreach ($data as $key => $value) {
            $_SESSION['flash'][$key] = $value;
        }
    }
    
    /**
     * Redirect with errors
     */
    public static function withErrors($errors) {
        return self::with(['errors' => $errors]);
    }
    
    /**
     * Redirect with success message
     */
    public static function withSuccess($message) {
        return self::with(['success' => $message]);
    }
    
    /**
     * Build complete URL from path
     */
    private static function buildUrl($path) {
        // If it's already a full URL, return as-is
        if (parse_url($path, PHP_URL_SCHEME)) {
            return $path;
        }
        
        // Ensure path starts with slash
        $path = '/' . ltrim($path, '/');
        
        return self::$baseUrl . $path;
    }
    
    /**
     * Get flashed data and clear it
     */
    public static function getFlashed($key = null) {
        $data = $_SESSION['flash'] ?? [];
        
        if ($key !== null) {
            $value = $data[$key] ?? null;
            unset($_SESSION['flash'][$key]);
            return $value;
        }
        
        unset($_SESSION['flash']);
        return $data;
    }

}



// In your bootstrap file (e.g., config.php)
// Redirect::setBaseUrl('https://yourdomain.com');

// // In your controller/script:

// // Simple redirect
// Redirect::to('/dashboard');

// // Redirect with data
// Redirect::to('/profile', 302, [
//     'success' => 'Profile updated!',
//     'user_id' => 123
// ]);

// // Redirect back with errors
// if ($validationFailed) {
//     Redirect::back(['errors' => $validator->getErrors()]);
// }

// // Redirect with named parameters
// Redirect::to('/products/' . $productId . '/edit');

// Set data separately
// Redirect::with(['success' => 'Profile updated']);
// Redirect::to('/profile'); // Data carries over

// // In your view to retrieve flashed data:
// $success = Redirect::getFlashed('success');
// $errors = Redirect::getFlashed('errors');