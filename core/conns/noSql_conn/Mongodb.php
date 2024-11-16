<?php 

class MongoDBConnection {
    private $connection;
    public $db; // Changed visibility to public for direct access
    public function __construct($host, $dbname) {
        $this->connection = new MongoDB\Client("mongodb://$host");
        $this->db = $this->connection->$dbname;
    }

    public function getDb() {
        return $this->db;
    }
}

// Example usage:
// $mongo = new MongoDBConnection('localhost', 'my_database');
// $db = $mongo->getDb();
