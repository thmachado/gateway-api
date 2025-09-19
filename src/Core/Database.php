<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): ?PDO
    {   
        $host = (string) getenv("DB_HOST");
        $database = (string) getenv("DB_NAME");
        $username = (string) getenv("DB_USER");
        $password = (string) getenv("DB_PASSWORD");

        if (self::$instance === null) {
            try {
                self::$instance = new PDO("pgsql:host={$host};dbname={$database}", $username, $password);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (\PDOException $e) {
                throw new \RuntimeException($e->getMessage());
            }
        }

        return self::$instance;
    }
}