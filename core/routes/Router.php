<?php
// ob_start();

namespace SUPA\routes;

class Router
{
    protected static $routes = [];
    private static $routeCounter = 0;
    private static $groupStack = [];
    protected static $namedRoutes = [];
    private static $fallbackHandler;
    private static $middlewareGroups = [];
    private static $globalMiddleware = []; // Array to hold global middleware

    // Method to register global middleware
    public static function use($middleware)
    {
        self::$globalMiddleware[] = $middleware; // Add middleware to the global array
    }

    public static function group($prefix, $middleware = null)
    {
        // Push the current group attributes onto the stack
        self::$groupStack[] = [
            'prefix' => rtrim($prefix, '/'),
            'middleware' => self::resolveMiddleware($middleware)
        ];
        return new class {
            public function get($url, $handler, $middleware = null)
            {
                 Router::addRoute('GET', $url, $handler, $middleware);
                 return $this;
            }

            public function post($url, $handler, $middleware = null)
            {
                 Router::addRoute('POST', $url, $handler, $middleware);
                 return $this;
            }

            public function put($url, $handler, $middleware = null)
            {
                 Router::addRoute('PUT', $url, $handler, $middleware);
                 return $this;
            }

            public function delete($url, $handler, $middleware = null)
            {
                 Router::addRoute('DELETE', $url, $handler, $middleware);
                 return $this;
            }
        };
    }

    public static function addRoute($method, $url, $handler, $middlewareOrName = null)
    {
        $groupAttributes = self::getGroupAttributes();
        $url = $groupAttributes['prefix'] . $url;

        // Initialize variables
        $middleware = [];
        $routeName = null;

        // Determine if it's a middleware or route name
        if ($middlewareOrName !== null) {
            if (is_string($middlewareOrName)) {
                // Validate route name
                if (isset(self::$namedRoutes[$middlewareOrName])) {
                    throw new \Exception("Route name '{$middlewareOrName}' already exists");
                }
                $routeName = $middlewareOrName;
            } elseif (is_callable($middlewareOrName) || is_array($middlewareOrName)) {
                $middleware = (array) $middlewareOrName; // Ensure it's an array
            }
        }

        // Merge with group middleware
        $middleware = array_merge($groupAttributes['middleware'], $middleware);

        $routeId = 'route_' . self::$routeCounter++;
        self::$routes[$routeId] = [
            'method' => strtoupper($method),
            'url' => $url,
            'handler' => $handler,
            'middleware' => $middleware
        ];

        // Store route name if provided
        if ($routeName) {
            self::$namedRoutes[$routeName] = $routeId;
        }
        // include_once 'Namedroute.php';
        return new Mchain($routeId);
    }

    public static function handleRequest($response = '')
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = $_SERVER['REQUEST_URI'];

        // Initialize a flag to check if a route was matched
        $routeMatched = false;

        foreach (self::$routes as $route) {
            // Check if the method matches and if the path matches
            if ($route['method'] === $method) {
                $matches = self::match($path, $route['url']);
                if ($matches !== false) {
                    $routeMatched = true; // Set the flag to true if a route is matched
                    $queryParams = [];
                    $queryString = parse_url($path,  PHP_URL_QUERY);
                    if ($queryString !== null) {
                        parse_str($queryString, $queryParams);
                    }
                    $allParams = array_merge($matches, $queryParams);

                    // Execute route-specific middleware
                    foreach ($route['middleware'] as $middleware) {
                        if (is_callable($middleware)) {
                            $middlewareResult = $middleware($allParams, $matches);
                            if ($middlewareResult === false) {
                                return; // Stop execution if middleware fails
                            }
                        } else {
                            throw new \Exception("Middleware is not callable.");
                        }
                    }

                    // Execute the main handler
                    switch ($method) {
                        case 'POST':
                            $requestData = $_POST;
                            break;
                        case 'GET':
                            $requestData = $_GET;
                            break;
                        case 'PUT':
                        case 'DELETE':
                        case 'PATCH':  // Added PATCH method for completeness
                            $input = file_get_contents("php://input");
                            if (!empty($input)) {
                                // Check if content is JSON
                                if (isset($_SERVER['CONTENT_TYPE']) && 
                                    strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
                                    $requestData = json_decode($input, true);
                                    if (json_last_error() !== JSON_ERROR_NONE) {
                                        $requestData = [];
                                    }
                                } else {
                                    parse_str($input, $requestData);
                                }
                            } else {
                                $requestData = [];
                            }
                            break;
                        default:
                            $requestData = [];
                    }
                    
                    $requestData = array_merge($requestData, $_FILES);
                    $response = $route['handler']($allParams, $requestData, self::sendResponse(200, 'ok'));
                    return;
                }
            }
        }

        // If no route matched, handle fallback
        if (self::$fallbackHandler) {
            $response = call_user_func(self::$fallbackHandler);
            self::sendResponse(404, $response);
        } else {
            echo self::sendResponse(404, 'Not Found');
        }

        if ($response === '') {
            return $response;
        } else {
            echo $response;
        }
    }

    private static function match($path, $url)
    {
        $path = parse_url($path, PHP_URL_PATH);
        $urlParts = parse_url($url);
        $urlPath = $urlParts['path'];

        $urlPattern = preg_replace_callback('/\{(\w+)\}/', function ($matches) {
            return '(?P<' . $matches[1] . '>[^/]+)';
        }, $urlPath);

        $urlPattern = '/^' . str_replace('/', '\/', $urlPattern) . '$/';

        if (preg_match($urlPattern, $path, $matches)) {
            return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        }

        return false;
    }


    public static function route()
    {
        
        return new class {
            public function get($url, $handler, $middleware = null)
            {
                 Router::addRoute('GET',  $url, $handler, $middleware);
                 return $this;
            }

            public function post($url, $handler, $middleware = null)
            {
                 Router::addRoute('POST', $url, $handler, $middleware);
                 return $this;
            }

            public function put($url, $handler, $middleware = null)
            {
                 Router::addRoute('PUT', $url, $handler, $middleware);
                 return $this;
            }

            public function delete($url, $handler, $middleware = null)
            {
                 Router::addRoute('DELETE', $url, $handler, $middleware);
                 return $this;
            }
        };
    }

    private static function sendResponse($statusCode, $message)
    {
        http_response_code($statusCode);
        return json_encode(['status' => $statusCode, 'message' => $message]);
       
    }

    private static function getGroupAttributes()
    {
        $prefix = '';
        $middleware = [];
        foreach (self::$groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
            if (isset($group['middleware'])) {
                $middleware = array_merge($middleware, (array) $group['middleware']);
            }
        }
        return ['prefix' => $prefix, 'middleware' => $middleware];
    }

    public static function url($name, $parameters = [])
    {
        if (!isset(self::$namedRoutes[$name])) {
            throw new \Exception("Route not found: $name");
        }
        $url = self::$routes[self::$namedRoutes[$name]]['url'];
        foreach ($parameters as $key => $value) {
            $url = str_replace("{{$key}}", $value, $url);
        }
        return $url;
    }

    public static function fallback($handler)
    {
        self::$fallbackHandler = $handler;
    }

    public static function middlewareGroup($name, array $middleware)
    {
        self::$middlewareGroups[$name] = $middleware;
    }

    private static function resolveMiddleware($middleware)
    {
        if (is_string($middleware) && isset(self::$middlewareGroups[$middleware])) {
            return self::$middlewareGroups[$middleware];
        }
        return (array) $middleware;
    }

    public static function defineGroup($name, $attributes, $callback)
    {
        self::$groupStack[] = ['name' => $name, 'attributes' => $attributes];
        call_user_func($callback);
        array_pop(self::$groupStack);
    }
}



//  class for middlewarechaining routes
class Mchain extends Router {
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

   
}









// // Define middleware group
// Router::middlewareGroup('api', ['throttle', 'json']);


// // Define fallback route
// Router::fallback(function() {
//     return "API endpoint not found";
// });

// // Generate URL for a named route
// $userDetailUrl = Router::url('users.detail', ['id' => 5]);

// // Handle the request
// Router::handleRequest();

// Define middleware
// function authMiddleware($params) {
//     if (!isset($_SESSION['user'])) {
//         Router::sendResponse(401, 'Unauthorized');
//         return true; // Stop execution
//     }
// }

// function logMiddleware($params) {
//     error_log("Request to: " . $_SERVER['REQUEST_URI']);
// }

// // Group routing with middleware
// Router::group('api/', 'authMiddleware')
//     ->get('/user', function($params) {
//         // Handle GET request for user
//         echo "User details";
//     }, ['logMiddleware']) // You can also add additional middleware here
//     ->post('/user', function($params) {
//         // Handle POST request for user
//         echo "User created";
//     });
//    


// Route with middleware
// Router::get('/private', function($params, $matches) {
//     echo "This is a private route";
// }, 'authMiddleware');

// Router::get('/private', function($params, $matches) {
//     echo "This is a private route";
// }, function(params){});



// // Handle the request
// Router::handleRequest();
// extract($data);



// Router::defineGroup('users', ['middleware' => 'authMiddleware'], function() {
    
//      // Define a GET route for /user
    // Router::get('/user', function($params) {
    //     echo "User details";
    // })
    // ->middleware(['logMiddleware', 'anotherMiddleware']); // Adding multiple middleware

    // // Define a POST route for /user
    // Router::post('/user', function($params) {
    //     echo "User created";
    // })
    // ->middleware('logMiddleware'); // Adding a single middleware

    // // Define a public route
    // Router::get('/public', function($params) {
    //     echo "Public API";
    // })
    // ->middleware(['logMiddleware']); // Adding middleware for this route as well

// });