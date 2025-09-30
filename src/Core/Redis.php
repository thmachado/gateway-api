<?php

declare(strict_types=1);

namespace App\Core;

use Predis\{Client, ClientException};
use RuntimeException;

class Redis
{
    private static ?Client $instance = null;

    public static function getInstance(): ?Client
    {
        if (self::$instance === null) {
            try {
                self::$instance = new Client([
                    "host" => "redis",
                    "port" => 6379
                ]);
            } catch (ClientException $e) {
                self::$instance = null;
                throw new RuntimeException($e->getMessage());
            }
        }

        return self::$instance;
    }
}
