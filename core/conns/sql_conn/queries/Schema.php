<?php

require 'vendor/autoload.php';

class CreateSchema extends Pdoconn {
    private $pdo; // PDO connection
    private $mysqli; // MySQLi connection

    // Constructor to initialize connections
    public function __construct() {
        $this->pdo = Pdoconn::connectPDO(); // Assuming Pdoconn is the class for PDO connection
        $this->mysqli = Dbconnsql::connectMysqli(); // Assuming MysqliConn is the class for MySQLi connection
    }

    public function createDatabase($databaseName) {
        // Validate database name
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $databaseName)) {
            throw new InvalidArgumentException("Invalid database name.");
        }

        try {
            // Accessing dbType through a public method or changing its visibility
            switch (self::$dbType) { // Change to self:: if dbType is protected or public
                case 'mysql':
                    // Check if the database exists
                    $result = $this->mysqli->query("SHOW DATABASES LIKE '$databaseName'");
                    if ($result->num_rows > 0) {
                        echo "Database $databaseName already exists.\n";
                        return;
                    }
                    $sql = "CREATE DATABASE `$databaseName`";
                    $this->mysqli->query($sql);
                    break;
                case 'pgsql':
                    // Check if the database exists
                    $result = $this->pdo->query("SELECT 1 FROM pg_database WHERE datname = '$databaseName'");
                    if ($result->fetchColumn()) {
                        echo "Database $databaseName already exists.\n";
                        return;
                    }
                    $sql = "CREATE DATABASE \"$databaseName\"";
                    $this->pdo->exec($sql);
                    break;
                case 'sqlsrv':
                    // Check if the database exists
                    $result = $this->pdo->query("SELECT name FROM sys.databases WHERE name = '$databaseName'");
                    if ($result->fetchColumn()) {
                        echo "Database $databaseName already exists.\n";
                        return;
                    }
                    $sql = "CREATE DATABASE [$databaseName]";
                    $this->pdo->exec($sql);
                    break;
                default:
                    throw new Exception("Unsupported database type.");
            }
            echo "Database $databaseName created successfully.\n";
        } catch (Exception $e) {
            echo "Error creating database: " . $e->getMessage() . "\n";
        }
    }

    public function createTable($tableName, $columns) {
        // Validate table name
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
            throw new InvalidArgumentException("Invalid table name.");
        }

        // Check if the table exists
        try {
            switch (self::$dbType) { // Change to self:: if dbType is protected or public
                case 'mysql':
                    $result = $this->mysqli->query("SHOW TABLES LIKE '$tableName'");
                    if ($result->num_rows > 0) {
                        echo "Table $tableName already exists.\n";
                        return;
                    }
                    break;
                case 'pgsql':
                    $result = $this->pdo->query("SELECT to_regclass('$tableName')");
                    if ($result->fetchColumn()) {
                        echo "Table $tableName already exists.\n";
                        return;
                    }
                    break;
                case 'sqlsrv':
                    $result = $this->pdo->query("SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '$tableName'");
                    if ($result->fetchColumn()) {
                        echo "Table $tableName already exists.\n";
                        return;
                    }
                    break;
                // case 'sqlite':
                //     $result = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$tableName'");
                //     if ($result->fetchColumn()) {
                //         echo "Table $tableName already exists.\n";
                //         return;
                //     }
                //     break;
                default:
                    throw new Exception("Unsupported database type.");
            }

            $columnsSQL = [];
            foreach ($columns as $column => $definition) {
                $columnSQL = $definition['type'] . ' ' . implode(' ', $definition['attributes']);
                $columnsSQL[] = "$column $columnSQL";
            }

            $columnsSQL = implode(", ", $columnsSQL);
            $sql = "CREATE TABLE $tableName ($columnsSQL)";

            // Execute the create table command
            $this->pdo->exec($sql); // Use PDO connection for table creation
            echo "Table $tableName created successfully.\n";
        } catch (Exception $e) {
            echo "Error creating table: " . $e->getMessage() . "\n";
        }
    }

    public function updateTable($tableName, $columnName, $newDefinition) {
        // Validate table name and column name
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName) || !preg_match('/^[a-zA-Z0-9_]+$/', $columnName)) {
            throw new InvalidArgumentException("Invalid table or column name.");
        }

        try {
            // Generate the ALTER TABLE query for column modification
            $alterSQL = "ALTER TABLE $tableName CHANGE $columnName $columnName " . $newDefinition['type'] . ' ' . implode(' ', $newDefinition['attributes']);
            $this->pdo->exec($alterSQL); // Use PDO connection for altering the table
            echo "Column $columnName in table $tableName updated successfully.\n";
        } catch (Exception $e) {
            echo "Error updating column: " . $e->getMessage() . "\n";
        }
    }

    public function deleteColumn($tableName, $columnName) {
        // Validate table name and column name
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName) || !preg_match('/^[a-zA-Z0-9_]+$/', $columnName)) {
            throw new InvalidArgumentException("Invalid table or column name.");
        }

        try {
            // Generate the DROP COLUMN query
            $dropSQL = "ALTER TABLE $tableName DROP COLUMN $columnName";
            $this->pdo->exec($dropSQL); // Use PDO connection for dropping the column
            echo "Column $columnName in table $tableName deleted successfully.\n";
        } catch (Exception $e) {
            echo "Error deleting column: " . $e->getMessage() . "\n";
        }
    }
}


// HOW TO USE
// Include the necessary class files
// require 'vendor/autoload.php'; // Ensure Composer's autoload is included

// // Create an instance of the CreateSchema class
// $schemaCreator = new CreateSchema();

// // Create a new database
// $databaseName = 'test_database';
// $schemaCreator->createDatabase($databaseName);

// // Define the table name and columns
// $tableName = 'users';
// $columns = [
//     'id' => [
//         'type' => 'INT',
//         'attributes' => ['PRIMARY KEY', 'AUTO_INCREMENT']
//     ],
//     'name' => [
//         'type' => 'VARCHAR(100)',
//         'attributes' => ['NOT NULL']
//     ],
//     'email' => [
//         'type' => 'VARCHAR(100)',
//         'attributes' => ['NOT NULL', 'UNIQUE']
//     ],
//     'created_at' => [
//         'type' => 'TIMESTAMP',
//         'attributes' => ['DEFAULT CURRENT_TIMESTAMP']
//     ],
//     'updated_at' => [
//         'type' => 'TIMESTAMP',
//         'attributes' => ['DEFAULT CURRENT_TIMESTAMP', 'ON UPDATE CURRENT_TIMESTAMP']
//     ]
// ];

// // Create a new table in the newly created database
// $schemaCreator->createTable($tableName, $columns);

// Update a column's definition (e.g., change the column type)
// $newColumnDefinition = [
//     'type' => 'VARCHAR(200)',
//     'attributes' => ['NOT NULL', 'UNIQUE']
// ];
// $schemaCreator->updateTable('users', 'email', $newColumnDefinition);

// // Delete a column (e.g., remove the 'name' column)
// $schemaCreator->deleteColumn('users', 'name');