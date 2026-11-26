<?php

namespace App;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $config = require __DIR__ . '/config.php';
            $database = $config['database'];

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $database['host'],
                $database['port'],
                $database['database']
            );

            try {
                self::$connection = new PDO(
                    $dsn,
                    $database['username'],
                    $database['password'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
            } catch (PDOException $exception) {
                throw new PDOException(
                    'Не удалось подключиться к базе данных: ' . $exception->getMessage()
                );
            }
        }

        return self::$connection;
    }
}