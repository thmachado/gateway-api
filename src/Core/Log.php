<?php

declare(strict_types=1);

namespace App\Core;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class Log
{
    private static ?Logger $instance = null;

    public static function getInstance(): Logger
    {
        if (self::$instance === null) {
            $logger = new Logger("app");
            $handler = new StreamHandler("php://stdout", Logger::DEBUG);
            $handler->setFormatter(new JsonFormatter());
            $logger->pushHandler($handler);
            self::$instance = $logger;
        }

        return self::$instance;
    }
}
