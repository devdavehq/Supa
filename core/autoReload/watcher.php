<?php
// watcher.php

$filesToWatch = ['index.php', 'reload.js']; // List of files to monitor for changes
$lastModifiedTimes = []; // Array to store the last modified times of the files

// Initialize last modified times
foreach ($filesToWatch as $file) {
    $lastModifiedTimes[$file] = filemtime($file); // Get the last modified time of each file
}

// Start WebSocket server
$server = new Ratchet\App('localhost', 2088); // Create a new WebSocket server on localhost:8080
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

