<?php

class CORSHandler
{
    private $allowedOrigins = [];
    private $allowedMethods = [];
    private $allowedHeaders = [];
    private $allowCredentials = false;
    private $maxAge = 0;

    /**
     * Set the allowed origins.
     *
     * @param array $origins
     * @return $this
     */
    public function setAllowedOrigins(array $origins)
    {
        $this->allowedOrigins = $origins;
        return $this;
    }

    /**
     * Set the allowed HTTP methods.
     *
     * @param array $methods
     * @return $this
     */
    public function setAllowedMethods(array $methods)
    {
        $this->allowedMethods = $methods;
        return $this;
    }

    /**
     * Set the allowed headers.
     *
     * @param array $headers
     * @return $this
     */
    public function setAllowedHeaders(array $headers)
    {
        $this->allowedHeaders = $headers;
        return $this;
    }

    /**
     * Set whether credentials are allowed.
     *
     * @param bool $allow
     * @return $this
     */
    public function setAllowCredentials(bool $allow)
    {
        $this->allowCredentials = $allow;
        return $this;
    }

    /**
     * Set the max age for the preflight request.
     *
     * @param int $seconds
     * @return $this
     */
    public function setMaxAge(int $seconds)
    {
        $this->maxAge = $seconds;
        return $this;
    }

    /**
     * Handle the CORS request.
     *
     * @param string $requestMethod
     * @param string $requestOrigin
     * @param string $requestHeaders
     */
    public function handle(string $requestMethod, string $requestOrigin, string $requestHeaders = '')
    {
        // Check if the origin is allowed
        if (!empty($this->allowedOrigins)) {
            if (in_array($requestOrigin, $this->allowedOrigins)) {
                header("Access-Control-Allow-Origin: $requestOrigin");
            }
        } else {
            header("Access-Control-Allow-Origin: *");
        }

        // Handle preflight requests
        if ($requestMethod === 'OPTIONS') {
            if (!empty($this->allowedMethods)) {
                header("Access-Control-Allow-Methods: " . implode(', ', $this->allowedMethods));
            }
            if (!empty($this->allowedHeaders)) {
                header("Access-Control-Allow-Headers: " . implode(', ', $this->allowedHeaders));
            }
            if ($this->allowCredentials) {
                header("Access-Control-Allow-Credentials: true");
            }
            if ($this->maxAge > 0) {
                header("Access-Control-Max-Age: $this->maxAge");
            }
            exit(0); // No further action needed for preflight requests
        }

        // Allow credentials if set
        if ($this->allowCredentials) {
            header("Access-Control-Allow-Credentials: true");
        }
    }
}



// usage
// <?php

// require 'CORSHandler.php';

// // Create a new instance of CORSHandler
// $cors = new CORSHandler();

// // Configure CORS settings
// $cors->setAllowedOrigins(['https://frontend.com', 'https://anotherdomain.com'])
//      ->setAllowedMethods(['GET', 'POST', 'PUT', 'DELETE'])
//      ->setAllowedHeaders(['Content-Type', 'Authorization'])
//      ->setAllowCredentials(true)
//      ->setMaxAge(3600);

// // Get request details
// $requestMethod = $_SERVER['REQUEST_METHOD'];
// $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
// $requestHeaders = $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] ?? '';

// // Handle CORS
// $cors->handle($requestMethod, $requestOrigin, $requestHeaders);

// // Your application logic
// if ($requestMethod === 'GET') {
//     echo json_encode(['message' => 'This is a GET request']);
// } elseif ($requestMethod === 'POST') {
//     echo json_encode(['message' => 'This is a POST request']);
// }