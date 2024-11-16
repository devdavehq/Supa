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
