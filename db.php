<?php
class DB {
    private static $host = 'localhost';
    private static $db   = 'ussd';
    private static $user = 'root';
    private static $pass = '';
    private static $charset = 'utf8mb4';

    private static $pdo = null;

    public static function connect() {
        if (self::$pdo === null) {
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db . ";charset=" . self::$charset;
            try {
                self::$pdo = new PDO($dsn, self::$user, self::$pass);
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die("END Database Connection Failed: " . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}
?>
