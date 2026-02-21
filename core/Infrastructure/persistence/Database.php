<?php

namespace Core\Infrastructure\Persistence;
use PDO;
use PDOException;

class Database {
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $config = require __DIR__ . '/../../../config/database.php';

            $dsn = "{$config['driver']}:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";

            try {
                self::$connection = new PDO (
                    $dsn,
                    $config['username'],
                    $config['password'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
                // echo "Connected successfully!";
            } catch (PDOException $e) {
                die('Database connection failed: '.$e->getMessage());
            }
        }

        return self::$connection;
    }
}
