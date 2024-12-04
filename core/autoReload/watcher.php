<?php
// watcher.php

// $directoriesToWatch = [
//     'core/includes/', // First directory to watch
//     'core/anotherDirectory/', // Second directory to watch
//     // Add more directories as needed
// ];

$filesToWatch = []; // Initialize the array to store files to watch

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
$filesToWatch[] = 'index.php';
$filesToWatch[] = 'reload.js';

$lastModifiedTimes = []; // Array to store the last modified times of the files

// Initialize last modified times
foreach ($filesToWatch as $file) {
    $lastModifiedTimes[$file] = filemtime($file); // Get the last modified time of each file
}

// Start WebSocket server
$server = new Ratchet\App('localhost', 2088); // Create a new WebSocket server on localhost:2088
$server->route('/reload', new class implements \Ratchet\MessageComponentInterface {
    protected $clients; // Store connected clients

    public function __construct() {
        $this->clients = new \SplObjectStorage; // Initialize the clients storage
    }

    public function onOpen($conn) {
        $this->clients->attach($conn); // Attach a new client connection
    }

    public function onMessage($from, $msg) {
        // Handle incoming messages if needed (currently not used)
    }

    public function onClose($conn) {
        $this->clients->detach($conn); // Detach a client when it disconnects
    }

    public function onError($conn, $e) {
        $conn->close(); // Close the connection on error
    }

    public function reloadClients() {
        foreach ($this->clients as $client) {
            $client->send('reload'); // Send the 'reload' message to all connected clients
        }
    }
});

// Watch for file changes
while (true) {
    foreach ($filesToWatch as $file) {
        clearstatcache(); // Clear the file status cache
        $currentModifiedTime = filemtime($file); // Get the current modified time of the file
        if ($currentModifiedTime !== $lastModifiedTimes[$file]) { // Check if the file has been modified
            $lastModifiedTimes[$file] = $currentModifiedTime; // Update the last modified time
            $server->reloadClients(); // Notify all clients to reload
        }
    }
    sleep(1); // Check for changes every second
}

// add to html head
// <script src="autoReload/reload.js"></script>;

