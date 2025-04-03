<?php

namespace SUPA\conns\sql_conn\queries;

require 'vendor/autoload.php';

use SUPA\conns\sql_conn\Conn;

class Squery {
    private $conn;
    private $table;
    private $params = [];
    private $types = '';
    private $queryType;
    private $sql;
    private $stmt; // Store the prepared statement
    private $dbname;
    private $dbpass;

    // Constructor to accept dbname and dbpass
    public function __construct($dbname = null, $dbpass = null) {
        $this->dbname = $dbname;
        $this->dbpass = $dbpass;
        $this->conn = Conn::connectPDO($this->dbname, $this->dbpass);
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
        $this->params = array_values($data);
        return $this;
    }

    public function update($data) {
        $this->queryType = 'UPDATE';
        $this->sql = "UPDATE " . $this->table . " SET " . implode(" = ?, ", array_keys($data)) . " = ?";
        $this->params = array_values($data); // Store the parameters to be bound
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
        // Fix parameter type checking
        if (!is_array($params)) {
            if ($params === null) {
                $params = [];
            } else {
                throw new \InvalidArgumentException('Params must be an array');
            }
        }

        // Handle parameter merging safely
        if (empty($params) && in_array($this->queryType, ['INSERT', 'UPDATE'])) {
            $params = is_array($this->params) ? $this->params : [];
        }

        // Validate DELETE operations
        if ($this->queryType === 'DELETE' && !str_contains(strtoupper($this->sql), 'WHERE')) {
            throw new \RuntimeException('DELETE requires WHERE clause for safety');
        }

        $this->params = $params;
        $this->types = $types;
        return $this->execute();
    }

    private function execute() {
        try {

            if (!self::isValidSql($this->sql)) {
                error_log("Invalid SQL query: " . $this->sql);
                return false;
            }
            $this->stmt = $this->conn->prepare($this->sql);

            // Improved parameter binding
            if (!empty($this->params)) {
                // Auto-detect types if not provided
                if (empty($this->types)) {
                    $this->types = '';
                    foreach ($this->params as $param) {
                        if (is_int($param)) {
                            $this->types .= 'i';
                        } elseif (is_float($param)) {
                            $this->types .= 'd';
                        } elseif (is_bool($param)) {
                            $this->types .= 'i'; // MySQL treats booleans as integers
                        } elseif (is_null($param)) {
                            $this->types .= 's'; // NULL is typically bound as string
                        } else {
                            $this->types .= 's';
                        }
                    }
                }
                foreach ($this->params as $key => $param) {
                    $typeChar = $this->types[$key] ?? 's';
                    $pdoType = $this->getPdoType($typeChar);
                    
                    // Special handling for NULL values
                    if ($param === null) {
                        $this->stmt->bindValue($key + 1, null, \PDO::PARAM_NULL);
                    } else {
                        $this->stmt->bindValue($key + 1, $param, $pdoType);
                    }
                }
            }

            if (!$this->stmt->execute()) {
                throw new \RuntimeException(
                    "Query failed: " . implode(" - ", $this->stmt->errorInfo())
                );
            }

            return $this->fetchResults();
        } catch (\Exception $e) {
            error_log("SQL Error: " . $e->getMessage());
            throw $e; // Re-throw for proper error handling
        }
    }

    private function fetchResults() {
        switch ($this->queryType) {
            case 'SELECT':
                return $this->stmt->fetchAll(\PDO::FETCH_ASSOC);
            case 'COUNT':
                return $this->stmt->fetchColumn();
            case 'INSERT':
                return $this->conn->lastInsertId();
            case 'UPDATE':
            case 'DELETE':
                return $this->stmt->rowCount();
            default:
                return true;
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

    
}

//  usage

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

// Include the necessary class files
// require 'vendor/autoload.php';

// use SUPA\conns\sql_conn\queries\Squery;

// // Create an instance of the Squery class with dbname and dbpass
// $query = new Squery('test_database', 'password');

// // Example SELECT query
// $results = $query->from('users')
//     ->select('*')
//     ->where('email = ?')
//     ->exec(['user@example.com'], 's');

// if ($results) {
//     print_r($results);
// } else {
//     echo "Error fetching users.";
// }

// // Example INSERT query
// $insertId = $query->from('users')
//     ->insert([
//         'name' => 'John Doe',
//         'email' => 'john@example.com',
//         'created_at' => date('Y-m-d H:i:s')
//     ])
//     ->exec();

// if ($insertId) {
//     echo "Inserted record with ID: $insertId";
// } else {
//     echo "Error inserting record.";
// }

// // Example UPDATE query
// $affectedRows = $query->from('users')
//     ->update([
//         'name' => 'Jane Doe'
//     ])
//     ->where('id = ?')
//     ->exec([1], 'i');

// if ($affectedRows) {
//     echo "Updated $affectedRows rows.";
// } else {
//     echo "Error updating record.";
// }

// // Example DELETE query
// $affectedRows = $query->from('users')
//     ->delete()
//     ->where('id = ?')
//     ->exec([1], 'i');

// if ($affectedRows) {
//     echo "Deleted $affectedRows rows.";
// } else {
//     echo "Error deleting record.";
// }