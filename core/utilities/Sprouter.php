<?php

class Sprouter {
    private $routes = []; // Store all routes

    /**
     * Add a route to the router
     * 
     */
    public function __construct(){
        return;
    }
    public function route($uri, $viewFile) {
        $this->routes[$uri] = $viewFile;
    }

    /**
     * Match the current route and return the corresponding view
     */
    public function render() {
        // Get the current request URI
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Check if the route exists in the saved routes
        if (isset($this->routes[$requestUri])) {
            // Include and render the matching view
            include $this->routes[$requestUri];
        } else {
            // Render the fallback 404 page
            throw new Exception("404 Not Found: The requested route '$requestUri' does not exist.");
        }
    }
}



// // Include the header
// include 'partials/header.php';

// // Initialize the router
// $router = new Router();

// // Register your routes (URI => View file)
// $router->addRoute('/', 'views/home.php');
// $router->addRoute('/about', 'views/about.php');
// $router->addRoute('/contact', 'views/contact.php');

// // Render the appropriate view
// $router->render();

// // Include the footer
// include 'partials/footer.php';