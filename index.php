<?php
   
 //Include the Router logic
 require 'vendor/autoload.php';
 require 'core/_index.php';

        

    Router::get("/", function ($allParams, $requestData, $status) {
        echo "All params (including query): ";
        print_r($allParams);
        
        echo "Request data (GET): ";
        print_r($requestData);

        
        
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

    
Router::handleRequest();
