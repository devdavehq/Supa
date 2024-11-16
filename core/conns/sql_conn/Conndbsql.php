<?php
   
class Dbconnsql {

    private static $conn = null;
    private static $host = 'localhost';
    private static $user = 'root';
    private static $password = '';

    // Get the static connection
    public static function connectMySQLi() {
        if (self::$conn === null) {
            try {
                self::$conn = new mysqli(self::$host, self::$user, self::$password);

                if (self::$conn->connect_error) {
                    throw new Exception('Connection Failed: ' . self::$conn->connect_error);
                }
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }
        }

        return self::$conn;
    }

    // Close the static connection
    public static function closeConnection() {
        if (self::$conn !== null) {
            self::$conn->close();
            self::$conn = null;
        }
    }
}



