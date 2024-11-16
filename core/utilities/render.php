<?php

 function render($viewName, $data = []) {
    // Construct the path to the view file
    $viewPath = __DIR__ . "/views/" . $viewName . ".php";

    // Check if the view file exists
    if (file_exists($viewPath)) {
        // Extract data to variables
        if (!empty($data)) {
            extract($data);
        }

        // Include the view file
        include $viewPath;
    } else {
        // Handle the case where the view does not exist
        echo "View not found: " . htmlspecialchars($viewName);
    }
}