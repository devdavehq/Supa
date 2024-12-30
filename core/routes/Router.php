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
                return Router::addRoute('GET', $url, $handler, $middleware);
            }

            public function post($url, $handler, $middleware = null)
            {
                return Router::addRoute('POST', $url, $handler, $middleware);
            }

            public function put($url, $handler, $middleware = null)
            {
                return Router::addRoute('PUT', $url, $handler, $middleware);
            }

            public function delete($url, $handler, $middleware = null)
            {
                return Router::addRoute('DELETE', $url, $handler, $middleware);
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
                    throw new Exception("Route name '{$middlewareOrName}' already exists");
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

        return new Route($routeId);
    }

    public static function handleRequest($response = '')
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

        if($response === ''){
            return $response;
        }else {
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