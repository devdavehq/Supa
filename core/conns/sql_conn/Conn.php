namespace SUPA\conns\sql_conn;

class Conn {
    protected static $dbType = 'mysql'; // Set the database type to MySQL
    private static $host = 'localhost';
    private static $user = 'root';
    private static $password = '';
    private static $pdo = null; // Store the PDO instance
    private static $dbname = ''; // sqlite database only
    private static $dbpass = ''; // Store database password dynamically

    // Get the static connection
    public static function connectPDO($dbname = null, $dbpass = null) {
        // If dbname is NOT null and NOT an empty string, update it
        if ($dbname !== null && $dbname !== '') {
            self::$dbname = $dbname;
        }

        // If dbpass is NOT null and NOT an empty string, update it
        if ($dbpass !== null && $dbpass !== '') {
            self::$dbpass = $dbpass;
        }

        if (self::$pdo === null) { // Check if the connection is already established
            try {
                switch (self::$dbType) {
                    case 'mysql':
                        $dsn = "mysql:host=" . self::$host;
                        if (self::$dbname !== null && self::$dbname !== '') {
                            $dsn .= ";dbname=" . self::$dbname;
                        }
                        break;
                    case 'pgsql':
                        $dsn = "pgsql:host=" . self::$host;
                        if (self::$dbname !== null && self::$dbname !== '') {
                            $dsn .= ";dbname=" . self::$dbname;
                        }
                        break;
                    case 'sqlsrv':
                        $dsn = "sqlsrv:Server=" . self::$host;
                        if (self::$dbname !== null && self::$dbname !== '') {
                            $dsn .= ";Database=" . self::$dbname;
                        }
                        break;
                    default:
                        throw new \Exception("Unsupported database type: " . self::$dbType);
                }

                // Use dbpass if it's NOT null and NOT empty, otherwise fallback to default password
                $passwordToUse = (self::$dbpass !== null && self::$dbpass !== '') ? self::$dbpass : self::$password;

                self::$pdo = new \PDO($dsn, self::$user, $passwordToUse);
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
        self::$pdo = null; // Close the connection
    }
}