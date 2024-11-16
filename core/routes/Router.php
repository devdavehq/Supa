<?php
// ob_start();
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
        $method = $_SERVER['REQUEST_METHOD'];
        self::$globalMiddleware[] = $middleware; // Add middleware to the global array
        // Prepare parameters to pass to the middleware
        $rawInput = file_get_contents("php://input"); // Read raw input for PUT and DELETE
        $parsedInput = [];

        // Parse the input if it's not empty
        if (!empty($rawInput)) {
            parse_str($rawInput, $parsedInput); // Parse the raw input into an associative array
        }

        // Prepare parameters for middleware
        $params = [
            'method' => $method, // Pass the request method
            'GET' => $_GET ?? [], // Use null coalescing to provide an empty array if not set
            'POST' => $_POST ?? [], // Same for POST
            'PUT' => $parsedInput, // Use parsed input for PUT
            'DELETE' => $parsedInput // Use parsed input for DELETE
        ];

        // Execute global middleware before handling routes
        foreach (self::$globalMiddleware as $middleware) {
            try {
                $middlewareResult = $middleware($params); // Call global middleware with superglobal params
                if ($middlewareResult === false) {
                    // Handle the failure case immediately
                    echo Router::sendResponse(401, 'Unauthorized'); // Example response
                    return; // Stop further execution
                }
            } catch (Exception $e) {
                // Handle the exception
                echo Router::sendResponse(500, $e->getMessage()); // Send error response
                return; // Stop further execution
            }
        }
    }

    public static function addRoute($method, $url, $handler, $middlewareOrName = null)
    {
        $groupAttributes = self::getGroupAttributes();
        $url = $groupAttributes['prefix'] . $url;
        
        // Initialize variables
        $middleware = [];
        $routeName = null;

        // determine if its a middleware or route name
        if ($middlewareOrName !== null) {
            if (is_string($middlewareOrName)) {
                // Check if it's a middleware class or string
                if (class_exists($middlewareOrName, false)) { // Don't autoload
                    // Optionally validate the class has required method
                    if (!method_exists($middlewareOrName, '__invoke') && 
                        !method_exists($middlewareOrName, 'handle')) {
                        throw new Exception("Invalid middleware class: missing required method");
                    }
                    $middleware = [$middlewareOrName];
                } elseif (is_callable($middlewareOrName)) {
                    $middleware = [$middlewareOrName];
                } else {
                    // Validate route name
                    if (isset(self::$namedRoutes[$middlewareOrName])) {
                        throw new Exception("Route name '{$middlewareOrName}' already exists");
                    }
                    $routeName = $middlewareOrName;
                }
            } elseif (is_callable($middlewareOrName)) {
                // Handle direct callable (closure/function)
                $middleware = [$middlewareOrName];
            } elseif (is_array($middlewareOrName)) {
                // Validate each middleware in array
                foreach ($middlewareOrName as $m) {
                    if (!is_callable($m) && 
                        !(is_string($m) && class_exists($m, false))) {
                        throw new Exception("Invalid middleware in array");
                    }
                }
                $middleware = $middlewareOrName;
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

        return new Route($routeId);
    }

    public static function handleRequest()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = $_SERVER['REQUEST_URI'];



        foreach (self::$routes as $route) {
            $matches = self::match($path, $route['url']);
            if ($matches !== false && $route['method'] === $method) {
                $queryParams = [];
                $queryString = parse_url($path, PHP_URL_QUERY);
                if ($queryString !== null) {
                    parse_str($queryString, $queryParams);
                }
                $allParams = array_merge($matches, $queryParams);

                // Execute route-specific middleware
                foreach ($route['middleware'] as $middleware) {
                    $middlewareResult = $middleware($allParams, $matches);
                    if ($middlewareResult === false) {
                        return; // Stop execution if middleware fails
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
                        $input = file_get_contents("php://input");
                        if (!empty($input)) {
                            parse_str($input, $requestData);
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

        if (self::$fallbackHandler) {
            $response = call_user_func(self::$fallbackHandler);
            self::sendResponse(404, $response);
        } else {
            echo self::sendResponse(404, 'Not Found');
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


    public static function get($url, $handler, $middleware = null)
    {
        return self::addRoute('GET', $url, $handler, $middleware);
    }

    public static function post($url, $handler, $middleware = null)
    {
        return self::addRoute('POST', $url, $handler, $middleware);
    }

    public static function put($url, $handler, $middleware = null)
    {
        return self::addRoute('PUT', $url, $handler, $middleware);
    }

    public static function delete($url, $handler, $middleware = null)
    {
        return self::addRoute('DELETE', $url, $handler, $middleware);
    }

    private static function sendResponse($statusCode, $message)
    {
        http_response_code($statusCode);
        return json_encode(['status' => $statusCode, 'message' => $message]);
       
    }

    public static function group($attributes, $callback)
    {
        self::$groupStack[] = $attributes;
        call_user_func($callback);
        array_pop(self::$groupStack);
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
            throw new Exception("Route not found: $name");
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

include_once 'Namedroute.php';


// // Define middleware group
// Router::middlewareGroup('api', ['throttle', 'json']);

// // Use route group with middleware group
// Router::group(['prefix' => '/api', 'middleware' => 'api'], function() {
//     // Define named routes within the group
//     Router::get('/users', $userListHandler)->name('users.list');
//     Router::get('/users/{id}', $userDetailHandler)->name('users.detail');
// });

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
// Router::group(['prefix' => 'api', 'middleware' => logMiddleware], function() {
    
//     Router::group(['prefix' => 'users', 'middleware' => authMiddleware], function() {
        
//         // This route will have URL /api/users/{id} and both logMiddleware and authMiddleware
//         Router::get('/{id}', function($params, $matches) {
//             echo "User details for ID: " . $matches['id'];
//         }, 'user.details');

//         // This route will have URL /api/users/{id?} with an optional ID
//         Router::get('/{id?}', function($params, $matches) {
//             if (isset($matches['id'])) {
//                 echo "User details for ID: " . $matches['id'];
//             } else {
//                 echo "List of all users";
//             }
//         }, 'user.list');

//     });

//     // This route will have URL /api/public and only logMiddleware
//     Router::get('/public', function($params, $matches) {
//         echo "Public API";
//     }, 'public.api');

// });
// Route with middleware
// Router::get('/private', function($params, $matches) {
//     echo "This is a private route";
// }, 'authMiddleware');

// Router::get('/private', function($params, $matches) {
//     echo "This is a private route";
// }, function(params){});

// // Using named routes
// echo Router::url('user.details', ['id' => 5]); // Outputs: /api/users/5
// echo Router::url('user.list'); // Outputs: /api/users
// echo Router::url('public.api'); // Outputs: /api/public

// // Handle the request
// Router::handleRequest();
// extract($data);

// Router::defineGroup('users', ['middleware' => 'authMiddleware'], function() {
    
//     // This route will have URL /api/users/{id} and both logMiddleware and authMiddleware
//     Router::addRoute('GET', '/{id}', function($params, $matches) {
//         echo "User details for ID: " . $matches['id'];
//     }, 'user.details');

//     // This route will have URL /api/users/{id?} with an optional ID
//     Router::addRoute('GET', '/{id?}', function($params, $matches) {
//         if (isset($matches['id'])) {
//             echo "User details for ID: " . $matches['id'];
//         } else {
//             echo "List of all users";
//         }
//     }, 'user.list');

// });