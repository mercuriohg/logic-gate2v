<?php

class Database
{
    private static $connection = null;

    public static function getConnection()
    {
        if (self::$connection === null) {
            $host = getenv('DB_HOST') ?: 'mysql';
            $port = getenv('DB_PORT') ?: '3306';
            $database = getenv('DB_DATABASE') ?: 'logic_gate';
            $username = getenv('DB_USERNAME') ?: 'logic_gate_user';
            $password = getenv('DB_PASSWORD') ?: 'logic_gate_password';

            $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";

            self::$connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        }

        return self::$connection;
    }
}