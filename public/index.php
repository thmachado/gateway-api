<?php

declare(strict_types=1);

use App\Core\{Database, Log, Router};
use App\Middleware\LoggerMiddleware;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Psr\Log\LoggerInterface;

require_once __DIR__ . '/../vendor/autoload.php';

$containerBuilder = new DI\ContainerBuilder();
$containerBuilder->addDefinitions([
    PDO::class => Database::getInstance(),
    LoggerInterface::class => Log::getInstance(),
    LoggerMiddleware::class => DI\autowire()
]);

$container = $containerBuilder->build();
$router = new Router($container);

/** @var App\Middleware\MiddlewareInterface $loggerMiddleware; */
$loggerMiddleware = $container->get(LoggerMiddleware::class);
$router->addMiddleware($loggerMiddleware);

$router->get("/health", function (): JsonResponse {
    return new JsonResponse(["status" => "Health check is ok!"], 200);
});

$response = $router->dispatch(ServerRequestFactory::fromGlobals());
$emitter = new SapiEmitter();
$emitter->emit($response);