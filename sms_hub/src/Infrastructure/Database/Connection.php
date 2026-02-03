<?php
namespace Infrastructure\Database;

use PDO;
use PDOException;

final class Connection
{
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    public function __wakeup()
    {
        throw new \Exception('Cannot unserialize singleton');
    }

    public static function get(array $config): PDO
    {
        if (self::$instance === null) {
            try {
                self::$instance = new PDO(
                    $config['dsn'],
                    $config['user'],
                    $config['pass'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (PDOException $e) {
                throw new \RuntimeException(
                    'DB connection failed: ' . $e->getMessage()
                );
            }
        }

        return self::$instance;
    }
}
