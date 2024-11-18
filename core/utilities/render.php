<?php

 function render($viewName, $data = []) {
    // Construct the path to the view file
    $baseDir = dirname(__DIR__, 2); // Parent directory
    $viewDir = $baseDir . "/views"; // Views folder
    $viewPath = $viewDir . "/" . $viewName . ".php"; // Full path to the view file    // Check if the view file exists
    if (file_exists($viewPath)) {
        // Extract data to variables
        if (!empty($data)) {
            extract($data);
        }

        // Include the view file
        include $viewPath;
    } else {
        // Ensure the directory exists
        if (!is_dir($viewDir)) {
            mkdir($viewDir, 0777, true); // Create directory recursively
        }

        // Create the file
        $defaultContent = "<?php\n// New view file: " . htmlspecialchars($viewName) . "\n";
        if (file_put_contents($viewPath, $defaultContent)) {
            echo "View file created successfully: " . htmlspecialchars($viewPath);
        } else {
            echo "Failed to create view file.";
        }
    }
}