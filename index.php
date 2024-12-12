<?php
   
 //Include the Router logic
 require 'vendor/autoload.php';
 require 'core/_index.php';
//  use Core\Router;
// echo "Starting request handling...\n";

// use React\Http\HttpServer;
// use React\Http\Message\Response;
// use React\EventLoop\Factory;
 

        

    Router::get("/", function ($allParams, $requestData, $status) {
        echo "All params (including query): ";
        print_r($allParams);
        
        echo "Request data (GET): ";
        print_r($requestData);

        // print_r(fileUpload($_FILES));
        // print_r($_FILES);
        // $ins = new Sprouter();
        // $ins->route('/', 'pg.php');
        // $ins->render();
        // // render('name', ['name'=> 'David']);
        // $ins = new CSRFProtection();
        // echo $ins->getToken();
       
     
        // $loop = Factory::create();
     
        // $server = new HttpServer(function ($request) {
        //     return new Response(
        //         200,
        //         ['Content-Type' => 'text/plain'],
        //         "Hello, World!"
        //     );
        // });
     
        // $socket = new React\Socket\Server('127.0.0.1:2088', $loop);
        // $server->listen($socket);
     
        // echo "Server running at http://127.0.0.1:2088\n";
     
        // $loop->run();
        
        
    });

    Router::post("/users", function ($allParams, $requestData, $status) {
        echo "All params (including query): ";
        print_r($allParams);
   
        echo "Request data (POST): ";
        print_r($requestData);
    });

    Router::get("/users/{id}", function ($fullParams, $queryData, $status) {
        echo "All params (including query): ";
        print_r($fullParams);
     
        echo "Request data (GET): ";
        print_r($queryData);
    });

    Router::get("/user/{id?}", function ($allParams, $requestData, $status) {
        echo "All params (including query): ";
        print_r($allParams);
       
        echo "Request data (GET): ";
        print_r($requestData);
    });

Router::handleRequest();
// $directoriesToWatch = [
//     'core/includes/', // First directory to watch
//     'core/anotherDirectory/', // Second directory to watch
//     // Add more directories as needed
// ];

// $filesToWatch = []; // Initialize the array to store files to watch

// Function to recursively get all PHP files in a directory and its subdirectories
// function getPhpFiles($directory) {
//     $files = glob($directory . '*.php'); // Get all PHP files in the current directory
//     $subdirectories = glob($directory . '*', GLOB_ONLYDIR); // Get all subdirectories

//     foreach ($subdirectories as $subdirectory) {
//         $files = array_merge($files, getPhpFiles($subdirectory . '/')); // Recursively get PHP files from subdirectories
//     }

//     return $files; // Return the array of PHP files
// }

// Gather all PHP files from the specified directories
// foreach ($directoriesToWatch as $directory) {
//     $filesToWatch = array_merge($filesToWatch, getPhpFiles($directory)); // Merge files from each directory
// }

// Add other specific files if needed
// $filesToWatch[] = dirname(__DIR__, 2).'/index.php';
// $filesToWatch[] = __DIR__ . '/reload.js';

// $lastModifiedTimes = []; // Array to store the last modified times of the files

// // Initialize last modified times
// foreach ($filesToWatch as $file) {
//     $lastModifiedTimes[$file] = filemtime($file); // Get the last modified time of each file
// }


 ?>
 
 <!DOCTYPE html>
   <html lang="en">
   <head>
       <meta charset="UTF-8">
       <meta name="viewport" content="width=device-width, initial-scale=1.0">
       <title>Auto Reload Example</title>
       <script src="core/autoReload/reload.js"></script> <!-- Include your reload.js script -->
   </head>
   <body>
       <h1>Auto Reload Example</h1>
       <p>Make changes to your PHP files and see the magic!</p>
   </body>
   </html>


   jfjgjdfbkjb jdgeffwdfwyf
    <!-- <script>


            function Index(){
                return (

                    `  
                        <h1>Index</h1>
                        ${Buttons()}
                    `
                )
            }

            function Dashboard(){
                return (

                    `  
                        <h1>Dashboard</h1>
                        ${Buttons()}
                    `
                )
            }
            function About(){
                return (

                    `  
                        <h1>About</h1>
                        ${Buttons()}
                    `
                )
            }


            function PageNotFound(){
                return (

                    `  
                        <h1>PageNotFound</h1>
                        ${Buttons()}
                    `
                )
            }
            function Buttons(){
                return (

                    `  
                        <button>index</button>
                        <button>dashboard</button>
                        <button>about</button>
                    `
                )
            }




            function Router(routes, invalidRoute){
                let path = window.location.pathname;
                let query = window.location.search;

                let payload;
                let url;
                let route = Object.keys(routes)

                

                if (!query){
                       url = path.split('/')
                        payload = url[url.length - 1].toLowerCase() 
                        displaydata(payload)

                        // console.log(payload);
                        
                 
                }else if (query){
                    url = path.split('=')[1].toLowerCase().trim()
                    payload = url
                    // displaydata(payload)
                }else {
                    displaydata()
                }



                function displaydata(data){
                    if(!route.includes(data)){
                        document.body.innerHTML = invalidRoute()
                    }else{
                        const page = route[data]
                        document.body.innerHTML = page()
                    }

                    addbtnlisteners()
                }
            }



            function addbtnlisteners() {
                let buttons = document.querySelectorAll("button")

                buttons.forEach((btn) => {
                    let button = btn.innerText.toLowerCase()


                    window.history.pushState(null, button, `${button}`)

                    Router({'/': Index, Dashboard, About}, PageNotFound)
                })
            }



            Router({'/': Index, Dashboard, About}, PageNotFound)
    </script> -->
</body>
</html>
