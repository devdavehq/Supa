<?php


namespace SUPA\conns\sql_conn;


class Conn {
    protected static $dbType = 'mysql'; // Set the database type to MySQL
    private static $host = 'localhost';
    private static $user = 'root';
    private static $password = 'your_password';
    private static $pdo = null; // Store the PDO instance
    private static $dbname = ''; // sqllite database only

    // Get the static connection
    public static function connectPDO() {
        if (self::$pdo === null) { // Check if the connection is already established
            try {
                switch (self::$dbType) {
                    case 'mysql':
                        $dsn = "mysql:host=" . self::$host;
                        break;
                    case 'pgsql':
                        $dsn = "pgsql:host=" . self::$host;
                        break;
                    case 'sqlsrv':
                        $dsn = "sqlsrv:Server=" . self::$host;
                        break;
                    default:
                        throw new \Exception("Unsupported database type: " . self::$dbType);
                }

                // sqllite suspended unil further notice
                // case 'sqlite':
                //     $dsn = "sqlite:" . self::$dbname; // For SQLite, $dbName should be the file path
                //     break;
                self::$pdo = new \PDO($dsn, self::$user, self::$password);
                self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION); // Set error mode
            } catch (\PDOException $e) {
                error_log("Connection failed: " . $e->getMessage());
                return null; // Handle connection failure
            }
        }
        return self::$pdo; // Return the PDO instance
    }

    

    // Close the static connection
    public static function closeConnection() {
        if (self::$pdo !== null) {
            self::$pdo = null; // Close the connection
        }
    }
}




