<?php

// Define the path where `index.php` should be placed
$targetPath = __DIR__ . '/../index.php';
$sourcePath = __DIR__ . '/../src/index.php';

if (!file_exists($targetPath)) {
    if (!copy($sourcePath, $targetPath)) {
        echo "Failed to copy index.php.\n";
    } else {
        echo "index.php has been successfully installed.\n";
    }
} else {
    echo "index.php already exists.\n";
}
