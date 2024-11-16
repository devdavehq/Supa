<?php

require 'vendor/autoload.php';

class PdoQuery {

    // ... existing code ...

    public static function execute($sql, $params = [], $types = "") {
        // Get the database connection from Dbconn class
        $conn = Pdoconn::connectPDO(); // Change to PDO connection

        $stmt = null;

        // Validate SQL query to prevent SQL injection
        if (!self::isValidSql($sql)) {
            error_log("Invalid SQL query: " . $sql);
            return false; // Return false or handle as needed
        }

        try {
            // Prepare the SQL statement
            $stmt = $conn->prepare($sql);

            // ... existing code ...

            // Bind parameters if they exist
            if (!empty($params)) {
                // Validate and set types if none are provided
                if ($types === "") {
                    $types = str_repeat("s", count($params)); // Default to string
                }

                // Bind parameters using a loop
                foreach ($params as $key => $param) {
                    $stmt->bindValue($key + 1, $param, self::getPdoType($types[$key])); // Bind parameters
                }
            }

            // Execute the statement
            $success = $stmt->execute();

            if (!$success) {
                throw new Exception("Failed to execute statement: " . implode(", ", $stmt->errorInfo()));
            }

            // Determine the type of query
            $queryType = strtoupper(explode(' ', trim($sql))[0]);

            // Handle different types of queries
            switch ($queryType) {
                case 'SELECT':
                    return $stmt; // return stmt

                case 'INSERT':
                    return $conn->lastInsertId(); // Return the insert ID for INSERT queries

                case 'UPDATE':
                case 'DELETE':
                    return $stmt->rowCount(); // Return the number of affected rows for UPDATE/DELETE queries

                default:
                    throw new Exception("Unsupported query type: " . $queryType);
            }
        } catch (Exception $e) {
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
        $allowedCommands = ['SELECT', 'INSERT', 'UPDATE', 'DELETE'];
        $queryType = strtoupper(explode(' ', trim($sql))[0]);
        return in_array($queryType, $allowedCommands);
    }

    private static function getPdoType($type) {
        switch ($type) {
            case 'i':
                return PDO::PARAM_INT;
            case 'd':
                return PDO::PARAM_STR; // PDO does not have a specific float type
            case 's':
            default:
                return PDO::PARAM_STR;
        }
    }
} 