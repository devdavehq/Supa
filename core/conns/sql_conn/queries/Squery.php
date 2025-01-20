<?php

namespace SUPA\conns\sql_conn\queries;


require 'vendor/autoload.php';

use SUPA\conns\sql_conn\Conn;
class Squery {
    private $conn;
    private $table;
    private $params = [];
    private $types = [];
    private $queryType;
    private $sql;
    private $stmt; // Store the prepared statement

    public function __construct() {
        $this->conn = Conn::connectPDO(); // Get the PDO connection
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
        $this->sql = "INSERT INTO " . $this->table . " (" . implode(", ", array_values($data)) . ") VALUES (" . rtrim(str_repeat("?, ", count($data)), ', ') . ")";
        return $this;
    }

    public function update($data) {
        $this->queryType = 'UPDATE';
        $this->sql = "UPDATE " . $this->table . " SET " . implode(" = ?, ", array_keys($data)) . " = ?";
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
    public function exec($params = [], $types = "") {
        $this->params = $params; // Store the parameters to be bound
        $this->types = $types; // Store the types of the parameters
        return $this->execute(); // Call the execute method to run the query
    }

    private function execute() {
        // Validate SQL query to prevent SQL injection
        if (!self::isValidSql($this->sql)) {
            error_log("Invalid SQL query: " . $this->sql);
            return false; // Return false or handle as needed
        }

        try {
            $this->stmt = $this->conn->prepare($this->sql); // Prepare the SQL statement

            // Bind parameters if they exist
            if (!empty($this->params)) {
                // Validate and set types if none are provided
                if ($this->types === "") {
                    $this->types = str_repeat("s", count($this->params)); // Default to string
                }

                // Bind parameters using a loop
                foreach ($this->params as $key => $param) {
                    $this->stmt->bindValue($key + 1, $param, self::getPdoType($this->types[$key])); // Bind parameters
                }
            }

            // Execute the statement
            $success = $this->stmt->execute();

            if (!$success) {
                throw new \Exception("Failed to execute statement: " . implode(", ", $this->stmt->errorInfo()));
            }

            // Handle different types of queries
            switch ($this->queryType) {
                case 'SELECT':
                case 'COUNT':
                    return $this->stmt->fetchAll(\PDO::FETCH_ASSOC); // Return all results for SELECT and COUNT queries

                case 'INSERT':
                    return $this->conn->lastInsertId(); // Return the insert ID for INSERT queries

                case 'UPDATE':
                case 'DELETE':
                    return $this->stmt->rowCount(); // Return the number of affected rows for UPDATE/DELETE queries

                default:
                    throw new \Exception("Unsupported query type: " . $this->queryType);
            }
        } catch (\Exception $e) {
            // Log the error instead of throwing it directly
            error_log($e->getMessage());
            return false; // Return false or handle as needed
        }
    }

    /**
     * Validates the SQL query to prevent SQL injection.
     *
     * @param string $sql The SQL query to validate.
     * @return bool True if the SQL query is valid, false otherwise.
     */
    private static function isValidSql($sql) {
        // Implement a basic validation logic (e.g., whitelisting allowed commands)
        $allowedCommands = ['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'COUNT'];
        $queryType = strtoupper(explode(' ', trim($sql))[0]);
        return in_array($queryType, $allowedCommands);
    }

    private static function getPdoType($type) {
        switch ($type) {
            case 'i':
                return \PDO::PARAM_INT;
            case 'd':
                return \PDO::PARAM_STR; // PDO does not have a specific float type
            case 's':
            default:
                return \PDO::PARAM_STR;
        }
    }
}



// Assuming you have already instantiated the Squery class and set the table
// $squery = new Squery();

// // Construct the query
// $result = $squery->from('users') // Specify the table
//     ->select('age, COUNT(*) AS count') // Select age and count
//     ->groupBy('age') // Group by age
//     ->having('COUNT(*) > 1') // Filter groups with more than 1 user
//     ->exec(); // Execute the query

// // Output the result
// print_r($result);