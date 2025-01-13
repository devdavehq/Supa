<?php


namespace SUPA\utilities;
class FileUploader
{
    private $options;

    /**
     * Constructor to initialize default options.
     *
     * @param array $options Custom configuration options for file upload.
     */
    public function __construct(array $options = [])
    {
        $defaultOptions = [
            'destination' => 'uploads/',
            'allowedTypes' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
            'maxSize' => 5 * 1024 * 1024, // 5MB
            'createDirectory' => true,
            'randomizeFilename' => true,
            'maxFiles' => 5
        ];

        $this->options = array_merge($defaultOptions, $options);

        // Ensure destination directory exists
        if (!is_dir($this->options['destination']) && $this->options['createDirectory']) {
            mkdir($this->options['destination'], 0755, true);
        }
    }

    /**
     * Handle file upload.
     *
     * @param array $files The $_FILES array or specific file input.
     * @return array Result of the upload operation.
     */
    public function upload(array $files)
    {
        try {
            if (!isset($files['tmp_name']) || empty($files['tmp_name'])) {
                throw new Exception("Invalid data passed or file is empty");
            }

            $result = [
                'success' => false,
                'files' => [],
                'errors' => []
            ];

            // Normalize single file to multiple file structure
            if (!is_array($files['tmp_name'])) {
                $files = [
                    'name' => [$files['name']],
                    'type' => [$files['type']],
                    'tmp_name' => [$files['tmp_name']],
                    'error' => [$files['error']],
                    'size' => [$files['size']]
                ];
            }

            // Check number of files
            if (count($files['tmp_name']) > $this->options['maxFiles']) {
                $result['errors'][] = "Too many files. Maximum allowed: " . $this->options['maxFiles'];
                return $result;
            }

            // Process each file
            foreach ($files['tmp_name'] as $index => $tmpName) {
                $originalName = $files['name'][$index];
                $fileSize = $files['size'][$index];
                $fileError = $files['error'][$index];
                $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                if ($fileError === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                if ($fileError !== UPLOAD_ERR_OK) {
                    $result['errors'][] = "Error uploading file: " . $originalName;
                    continue;
                }

                if ($fileSize > $this->options['maxSize']) {
                    $result['errors'][] = "File too large: " . $originalName;
                    continue;
                }

                if (!in_array($fileExtension, $this->options['allowedTypes'])) {
                    $result['errors'][] = "Invalid file type: " . $originalName;
                    continue;
                }

                $newFilename = $this->options['randomizeFilename']
                    ? uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $fileExtension
                    : $originalName;

                $destination = $this->options['destination'] . $newFilename;

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

            $result['success'] = !empty($result['files']);
            return $result;
        } catch (Exception $e) {
            return [
                'success' => false,
                'errors' => [$e->getMessage()]
            ];
        }
    }
}

// // Example Usage
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $uploader = new FileUploader([
//         'destination' => 'uploads/',
//         'maxFiles' => 5,
//         'maxSize' => 10 * 1024 * 1024, // 10MB per file
//         'allowedTypes' => ['jpg', 'png', 'pdf']
//     ]);

//     // Single file upload
//     if (isset($_FILES['singleFile'])) {
//         $result = $uploader->upload($_FILES['singleFile']);
//         echo "<h3>Single File Upload Result:</h3>";
//         echo "<pre>" . print_r($result, true) . "</pre>";
//     }

//     // Multiple file upload
//     if (isset($_FILES['multipleFiles'])) {
//         $result = $uploader->upload($_FILES['multipleFiles']);
//         echo "<h3>Multiple File Upload Result:</h3>";
//         echo "<pre>" . print_r($result, true) . "</pre>";
//     }
// }
