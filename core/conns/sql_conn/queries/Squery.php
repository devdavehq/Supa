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
        // Convert string types to empty array to prevent issues
        if (is_string($params) {
            $params = [];
        }
    
        // Handle parameter merging
        if (empty($params) {
            if (in_array($this->queryType, ['INSERT', 'UPDATE'])) {
                // Ensure stored params is an array
                if (!is_array($this->params)) {
                    $this->params = [];
                }
                $params = $this->params;
            } elseif ($this->queryType === 'DELETE' && !str_contains(strtoupper($this->sql), 'WHERE')) {
                throw new \RuntimeException('DELETE requires WHERE clause');
            }
        }
    
        // Final type safety check
        if (!is_array($params)) {
            $params = [];
        }
    
        $this->params = $params;
        $this->types = $types;
        return $this->execute();
    }
    private function execute() {
        // Validate SQL query to prevent SQL injection
        if (!self::isValidSql($this->sql)) {
            error_log("Invalid SQL query: " . $this->sql);
            return false;
        }
    
        try {
            $this->stmt = $this->conn->prepare($this->sql);
    
            // Bind parameters if they exist
            if (!empty($this->params)) {
                // If types string wasn't provided, detect types automatically
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
    
                // Ensure types string matches the number of parameters
                if (strlen($this->types) !== count($this->params)) {
                    error_log("Number of types doesn't match number of parameters");
                    return false;
                }
    
                // Bind parameters with their types
                foreach ($this->params as $key => $param) {
                    $typeChar = $this->types[$key] ?? 's'; // Default to string if type not specified
                    $pdoType = self::getPdoType($typeChar);
                    
                    // Handle NULL values specially
                    if (is_null($param)) {
                        $this->stmt->bindValue($key + 1, null, \PDO::PARAM_NULL);
                    } else {
                        $this->stmt->bindValue($key + 1, $param, $pdoType);
                    }
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
                    return $this->stmt->fetchAll(\PDO::FETCH_ASSOC);
    
                case 'INSERT':
                    return $this->conn->lastInsertId();
    
                case 'UPDATE':
                case 'DELETE':
                    return $this->stmt->rowCount();
    
                default:
                    throw new \Exception("Unsupported query type: " . $this->queryType);
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
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