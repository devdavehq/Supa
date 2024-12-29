<?php

require 'vendor/autoload.php';
class Organizedmysqliquery {
    private $conn;
    private $table;
    private $params = [];
    private $queryType;
    private $sql;
    private $stmt; // Store the prepared statement

    public function __construct($host, $user, $password, $database) {
        $this->conn = Dbconnsql::connectMySQLi();
    }
    
    public function from($table) {
        $this->table = $table; // Set the table for the query
        return $this;
    }

    public function select($columns) {
        $this->queryType = 'SELECT';
        $this->sql = "SELECT " . $columns . " FROM " . $this->table;
        return $this;
    }

    public function insert($data) {
        $this->queryType = 'INSERT';
        $this->sql = "INSERT INTO " . $this->table . " (" . implode(", ", array_keys($data)) . ") VALUES (" . rtrim(str_repeat("?, ", count($data)), ', ') . ")";
        $this->params = array_values($data); // Store the values to bind
        return $this;
    }

    public function update($data) {
        $this->queryType = 'UPDATE';
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "$key = ?";
            $this->params[] = $value; // Store the values to bind
        }
        $this->sql = "UPDATE " . $this->table . " SET " . implode(", ", $set);
        return $this;
    }

    public function delete() {
        $this->queryType = 'DELETE';
        $this->sql = "DELETE FROM " . $this->table;
        return $this;
    }

    public function count($column = '*') {
        $this->queryType = 'COUNT';
        $this->sql = "SELECT COUNT($column) AS count FROM " . $this->table;
        return $this;
    }

    public function where($condition) {
        $this->sql .= " WHERE " . $condition;
        return $this;
    }

    public function limit($limit) {
        $this->sql .= " LIMIT " . (int)$limit; // Ensure limit is an integer
        return $this;
    }

    public function orderBy($column, $direction = 'ASC') {
        $this->sql .= " ORDER BY " . $column . " " . $direction;
        return $this;
    }

    public function groupBy($column) {
        $this->sql .= " GROUP BY " . $column;
        return $this;
    }

    public function having($condition) {
        $this->sql .= " HAVING " . $condition;
        return $this;
    }

    public function distinct() {
        $this->sql = str_replace("SELECT", "SELECT DISTINCT", $this->sql);
        return $this;
    }

    public function join($type = 'INNER', $table, $on) {
        $this->sql .= " " . strtoupper($type) . " JOIN " . $table . " ON " . $on;
        return $this;
    }

    public function truncate() {
        $this->sql = "TRUNCATE TABLE " . $this->table;
        return $this;
    }

    public function exec() {
        return $this->execute(); // Call the execute method to run the query
    }

    private function execute() {
         // Validate SQL query to prevent SQL injection
         if (!self::isValidSql($this->sql)) {
            error_log("Invalid SQL query: " . $this->sql);
            return false; // Return false or handle as needed
        }
        // Prepare the statement
        $this->stmt = $this->conn->prepare($this->sql);

        if ($this->stmt === false) {
            die("MySQLi prepare error: " . $this->conn->error);
        }

        // Bind parameters if they exist
        if (!empty($this->params)) {
            // Create a string for the types of the parameters
            $types = str_repeat("s", count($this->params)); // Default to string
            $this->stmt->bind_param($types, ...$this->params); // Bind parameters
        }

        // Execute the statement
        $success = $this->stmt->execute();

        if (!$success) {
            die("MySQLi execute error: " . $this->stmt->error);
        }

        // Handle different types of queries
        switch ($this->queryType) {
            case 'SELECT':
            case 'COUNT':
                $result = $this->stmt->get_result(); // Get the result set
                return $result; // Return all results as an associative array

            case 'INSERT':
                return $this->conn; // Return the insert ID for INSERT queries

            case 'UPDATE':
            case 'DELETE':
                return $this->stmt; // Return the number of affected rows for UPDATE/DELETE queries

            default:
                throw new Exception("Unsupported query type: " . $this->queryType);
        }
    }

    private static function isValidSql($sql) {
        // Implement a basic validation logic (e.g., whitelisting allowed commands)
        $allowedCommands = ['SELECT', 'INSERT', 'UPDATE', 'DELETE'];
        $queryType = strtoupper(explode(' ', trim($sql))[0]);
        return in_array($queryType, $allowedCommands);
    }

    public function __destruct() {
        $this->conn->close(); // Close the connection when the object is destroyed
    }
}