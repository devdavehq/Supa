<?php
   
 //Include the Router logic
 require 'vendor/autoload.php';

        
    Router::route()
    ->get("/", function ($allParams, $requestData, $status) {
        echo "All params (including query): ";
        print_r($allParams);
        
        echo "Request data (GET): ";
        print_r($requestData);

        
        
    })
    ->get("/users/{id}", function ($fullParams, $queryData, $status) {
        echo "All params (including query): ";
        print_r($fullParams);
     
        echo "Request data (GET): ";
        print_r($queryData);
    })
    ->post("/users", function ($allParams, $requestData, $status) {
        echo "All params (including query): ";
        print_r($allParams);
   
        echo "Request data (POST): ";
        print_r($requestData);
    }) 
    ->get("/users/", function ($fullParams, $queryData, $status) {
        echo "All params (including query): ";
        print_r($fullParams);
     
        echo "Request data (GET): ";
        print_r($queryData);
    });


    // Router::group('api/', 'authMiddleware')
    Router::group('api/')
    ->post('/user', function($params) {
        // Handle GET request for user
        echo "User created";
    } ) // You can also add additional middleware here
    ->get('/user', function($params) {
        // Handle POST request for user
        echo "User details";
    });
    




    //  sample login/signup

    Router::route()
    ->post("/signup", function ($allParams, $requestData, $status) {
        // Validate the form data
        $validator = Validator::check(Sanitize($requestData))
            ->validate('email', ['required' => 'Email is required', 'email' => 'Invalid email format'])
            ->validate('password', ['required' => 'Password is required', 'min:8' => 'Password must be at least 8 characters'])
            ->validate('username', ['required' => 'Username is required', 'min:3' => 'Username must be at least 3 characters'])
            ->validate('file', ['required' => 'File is required']);

        // Check for validation errors
        if ($validator->failed()) {
            return jsonResponse(['status' => 'error', 'errors' => $validator->getErrors()]);
        }

        // Check if user already exists
        $squery = new Squery();
        $existingUser = $squery->from('users')->where("email = '{$requestData['email']}'")->exec();
        if (!empty($existingUser)) {
            return jsonResponse(['status' => 'error', 'message' => 'User already exists']);
        }

        // Handle file upload
        $uploadResult = fileUpload($_FILES['file'], [
            'destination' => 'uploads/',
            'maxFiles' => 1,
            'allowedTypes' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']
        ]);

        if (!$uploadResult['success']) {
            return jsonResponse(['status' => 'error', 'errors' => $uploadResult['errors']]);
        }

        // Insert user data into the database
        $squery->from('users')->insert([
            'username' => $requestData['username'],
            'email' => $requestData['email'],
            'password' => password_hash($requestData['password'], PASSWORD_BCRYPT),
            'file_path' => $uploadResult['files'][0]['path']
        ])->exec();

        $_SESSION['email'] = $requestData['email']; // Store email in session
        return jsonResponse(['status' => 'success', 'message' => 'User registered successfully']);
    })
    ->post("/login", function ($allParams, $requestData, $status) {
        // Validate the login form data
        $validator = Validator::check(Sanitize($requestData))
            ->validate('email', ['required' => 'Email is required', 'email' => 'Invalid email format'])
            ->validate('password', ['required' => 'Password is required']);

        // Check for validation errors
        if ($validator->failed()) {
            return jsonResponse(['status' => 'error', 'errors' => $validator->getErrors()]);
        }

        // Fetch user from the database
        $squery = new Squery();
        $user = $squery->from('users')->where("email = '{$requestData['email']}'")->exec();

        if (empty($user) || !password_verify($requestData['password'], $user[0]['password'])) {
            return jsonResponse(['status' => 'error', 'message' => 'Invalid email or password']);
        }

        // Successful login
        $_SESSION['email'] = $requestData['email']; // Store email in session
        return jsonResponse(['status' => 'success', 'message' => 'Login successful']);
    });


    
Router::handleRequest('server started');

