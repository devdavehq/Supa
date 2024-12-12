<?php

require dirname(__DIR__, 2) . '/vendor/autoload.php'; // Include Composer's autoload file

use Ratchet\App;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

// Directories to watch
$directoriesToWatch = [
    'core/includes/', // First directory to watch
    'core/anotherDirectory/', // Second directory to watch
    // Add more directories as needed
];

$filesToWatch = []; // Initialize the array to store files to watch

// Function to recursively get all PHP files in a directory and its subdirectories
function getPhpFiles($directory) {
    $files = glob($directory . '*.php'); // Get all PHP files in the current directory
    $subdirectories = glob($directory . '*', GLOB_ONLYDIR); // Get all subdirectories

    foreach ($subdirectories as $subdirectory) {
        $files = array_merge($files, getPhpFiles($subdirectory . '/')); // Recursively get PHP files from subdirectories
    }

    return $files; // Return the array of PHP files
}

// Gather all PHP files from the specified directories
foreach ($directoriesToWatch as $directory) {
    $filesToWatch = array_merge($filesToWatch, getPhpFiles($directory)); // Merge files from each directory
}

// Add other specific files if needed
$filesToWatch[] = dirname(__DIR__, 2) . '/index.php';
$filesToWatch[] = __DIR__ . '/reload.js';

$lastModifiedTimes = []; // Array to store the last modified times of the files

// Initialize last modified times
foreach ($filesToWatch as $file) {
    $lastModifiedTimes[$file] = file_exists($file) ? filemtime($file) : 0; // Get the last modified time of each file
}

class ReloadServer implements MessageComponentInterface {
    protected $clients; // Store connected clients

    public function __construct() {
        $this->clients = new \SplObjectStorage; // Initialize the clients storage
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn); // Attach a new client connection
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        // Handle incoming messages if needed (currently not used)
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn); // Detach a client when it disconnects
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->close(); // Close the connection on error
    }

    public function reloadClients() {
        foreach ($this->clients as $client) {
            $client->send('reload'); // Send the 'reload' message to all connected clients
        }
    }
}

// Start WebSocket server
$server = new App('localhost', 2088); // Create a new WebSocket server on localhost:2088
$reloadServer = new ReloadServer();
$server->route('/reload', $reloadServer);

// Watch for file changes in a non-blocking way
$loop = React\EventLoop\Factory::create(); // Create a ReactPHP event loop

$loop->addPeriodicTimer(1, function() use ($filesToWatch, $lastModifiedTimes, $reloadServer) {
    foreach ($filesToWatch as $file) {
        if (!file_exists($file)) {
            echo "File does not exist: $file\n"; // Debugging output
            continue; // Skip to the next file
        }
        clearstatcache(); // Clear the file status cache
        $currentModifiedTime = filemtime($file); // Get the current modified time of the file
        if ($currentModifiedTime !== $lastModifiedTimes[$file]) { // Check if the file has been modified
            $lastModifiedTimes[$file] = $currentModifiedTime; // Update the last modified time
            $reloadServer->reloadClients(); // Notify all clients to reload
        }
    }
});

// Run the server and the loop
$server->run();

