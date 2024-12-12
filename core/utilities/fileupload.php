<?php

/**
 * FileUploader - A simple file upload handler
 * 
 * @param array $files The $_FILES array or specific file input
 * @param array $options Configuration options for the upload
 * @return array Result of the upload operation
 */
function fileUpload($files, array $options = []) {
    
    try{
    // Default options
    $defaultOptions = [
        'destination' => 'uploads/', // Default upload directory
        'allowedTypes' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'], // Allowed file types
        'maxSize' => 5 * 1024 * 1024, // 5MB default max file size
        'createDirectory' => true, // Create directory if it doesn't exist
        'randomizeFilename' => true, // Randomize filename to prevent overwrites
        // 'multiple' => false, // Allow multiple file uploads // DO NOT USE
        'maxFiles' => 5      // Add maximum number of files allowed

    ];

    if($files['tmp_name'] !== null){
    // Merge provided options with defaults
    $options = array_merge($defaultOptions, $options);

    // Ensure destination directory exists
    if (!is_dir($options['destination']) && $options['createDirectory']) {
        mkdir($options['destination'], 0755, true);
    }

    // Initialize result array
    $result = [
        'success' => false,
        'files' => [],
        'errors' => []
    ];

    // Handle single file upload if files is not an array of files
    if (isset($files['tmp_name']) && !is_array($files['tmp_name'])) {
        $files = [
            'name' => [$files['name']],
            'type' => [$files['type']],
            'tmp_name' => [$files['tmp_name']],
            'error' => [$files['error']],
            'size' => [$files['size']]
        ];
    }

       // Check number of files if multiple upload
    if (count($files['tmp_name']) > $options['maxFiles']) {
        $result['errors'][] = "Too many files. Maximum allowed: " . $options['maxFiles'];
        return $result;
    }
    // Process each file
    foreach ($files['tmp_name'] as $index => $tmpName) {
        $originalName = $files['name'][$index];
        $fileSize = $files['size'][$index];
        $fileError = $files['error'][$index];
        $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        // Skip if no file was uploaded
        if ($fileError === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        // Validate file
        if ($fileError !== UPLOAD_ERR_OK) {
            $result['errors'][] = "Error uploading file: " . $originalName;
            continue;
        }

        // Check file size
        if ($fileSize > $options['maxSize']) {
            $result['errors'][] = "File too large: " . $originalName;
            continue;
        }

        // Check file type
        if (!in_array($fileExtension, $options['allowedTypes'])) {
            $result['errors'][] = "Invalid file type: " . $originalName;
            continue;
        }

        // Generate new filename if randomization is enabled
        if ($options['randomizeFilename']) {
            $newFilename = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $fileExtension;
        } else {
            $newFilename = $originalName;
        }

        $destination = $options['destination'] . $newFilename;

        // Move uploaded file
        if (move_uploaded_file($tmpName, $destination)) {
            $result['files'][] = [
                'originalName' => $originalName,
                'savedAs' => $newFilename,
                'path' => $destination,
                'size' => $fileSize,
                'type' => $fileExtension
            ];
        } else {
            $result['errors'][] = "Failed to move uploaded file: " . $originalName;
        }
    }

    // Set success flag if any files were uploaded successfully
        $result['success'] = !empty($result['files']);

        return $result;
    }else {
        throw new Exception("Invalid data passed or file is empty", 1); 
    
    }
    }catch(Exception $e){
        return $e->getMessage();
    }
    
}


//  usage


// Handle form submission
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     // Single file upload
//     if (isset($_FILES['singleFile'])) {
//         $result = fileUpload($_FILES['singleFile'], [
//             'destination' => 'uploads/',
//             'maxFiles' => 1
//         ]);
//         echo "<h3>Single File Upload Result:</h3>";
//         echo "<pre>" . print_r($result, true) . "</pre>";
//     }

//     // Multiple file upload
//     if (isset($_FILES['multipleFiles'])) {
//         $result = fileUpload($_FILES['multipleFiles'], [
//             'maxFiles' => 5,
//             'maxSize' => 10 * 1024 * 1024, // 10MB per file
//             'allowedTypes' => ['jpg', 'png', 'pdf']
//         ]);
//         echo "<h3>Multiple File Upload Result:</h3>";
//         echo "<pre>" . print_r($result, true) . "</pre>";
//     }
// }

