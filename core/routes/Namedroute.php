<?php 

class Route extends Router {
    private $routeId;

    public function __construct($routeId) {
        $this->routeId = $routeId;
    }

    public function middleware($middleware) {
        // Add middleware to the route
        if (is_string($middleware)) {
            $middleware = [$middleware];
        }
        self::$routes[$this->routeId]['middleware'] = array_merge(
            self::$routes[$this->routeId]['middleware'] ?? [],
            $middleware
        );
        return $this; // Return $this for chaining
    }

    // public function name($name) {
    //     // Set the route name
    //     self::$namedRoutes[$name] = $this->routeId;
    //     return $this; // Return $this for chaining
    // }
}