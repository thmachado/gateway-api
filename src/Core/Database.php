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

                self::$instance->exec("CREATE TABLE IF NOT EXISTS customers(
                    id SERIAL PRIMARY KEY,
                    code UUID DEFAULT gen_random_uuid(),
                    external VARCHAR(255) NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    document VARCHAR(255) NOT NULL,
                    emails JSONB DEFAULT '[]'::jsonb,
                    phones JSONB DEFAULT '[]'::jsonb,
                    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
                )");
            } catch (\PDOException $e) {
                throw new \RuntimeException($e->getMessage());
            }
        }

        return self::$instance;
    }
}
