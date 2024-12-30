<?php

// MongoDB connection class
class MongoDBConnection {
    private $client;
    private $db;

    public function __construct($host, $database) {
        $this->client = new MongoDB\Client("mongodb://$host");
        $this->db = $this->client->$database;
    }

    public function getDb() {
        return $this->db;
    }
}

// // Initialize MongoDB connection
// $mongo = new MongoDBConnection('localhost', 'my_database');
// $db = $mongo->getDb();

// // CRUD Operations

// // Create a new user
// function createUser($db, $username, $email) {
//     $result = $db->users->insertOne([
//         'username' => $username,
//         'email' => $email,
//         'created_at' => new MongoDB\BSON\UTCDateTime()
//     ]);
//     return $result->getInsertedId();
// }

// // Read a user by email
// function readUser($db, $email) {
//     return $db->users->findOne(['email' => $email]);
// }

// // Update a user's email
// function updateUserEmail($db, $username, $newEmail) {
//     $result = $db->users->updateOne(
//         ['username' => $username],
//         ['$set' => ['email' => $newEmail]]
//     );
//     return $result->getModifiedCount();
// }

// // Delete a user by username
// function deleteUser($db, $username) {
//     $result = $db->users->deleteOne(['username' => $username]);
//     return $result->getDeletedCount();
// }

// // Example Usage
// // Create a user
// $userId = createUser($db, 'john_doe', 'john@example.com');
// echo "User created with ID: $userId\n";

// // Read the user
// $user = readUser($db, 'john@example.com');
// echo "User found: " . json_encode($user) . "\n";

// // Update the user's email
// $updatedCount = updateUserEmail($db, 'john_doe', 'john.doe@example.com');
// echo "Number of users updated: $updatedCount\n";

// // Delete the user
// $deletedCount = deleteUser($db, 'john_doe');
// echo "Number of users deleted: $deletedCount\n";
