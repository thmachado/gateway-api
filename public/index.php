<?php

declare(strict_types=1);

use App\Controllers\CustomerController;
use App\Core\{Database, Log, Router};
use App\Middleware\LoggerMiddleware;
use App\Repositories\CustomerRepository;
use App\Services\CustomerService;
use App\Validators\CustomerValidator;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Psr\Log\LoggerInterface;

require_once __DIR__ . '/../vendor/autoload.php';

$containerBuilder = new DI\ContainerBuilder();
$containerBuilder->addDefinitions([
    PDO::class => Database::getInstance(),
    LoggerInterface::class => Log::getInstance(),
    LoggerMiddleware::class => DI\autowire(),
    CustomerRepository::class => DI\autowire(),
    CustomerService::class => DI\autowire(),
    CustomerValidator::class => DI\autowire()
]);

$container = $containerBuilder->build();
$router = new Router($container);

/** @var App\Middleware\MiddlewareInterface $loggerMiddleware; */
$loggerMiddleware = $container->get(LoggerMiddleware::class);
$router->addMiddleware($loggerMiddleware);

$router->get("/health", function (): JsonResponse {
    return new JsonResponse(["status" => "Health check is ok!"], 200);
});

$router->get("/api/v1/customers", [CustomerController::class, "index"]);
$router->post("/api/v1/customers", [CustomerController::class, "store"]);
$router->get("/api/v1/customers/{code}", [CustomerController::class, "show"]);
$router->put("/api/v1/customers/{code}", [CustomerController::class, "update"]);
$router->delete("/api/v1/customers/{code}", [CustomerController::class, "delete"]);

$response = $router->dispatch(ServerRequestFactory::fromGlobals());
$emitter = new SapiEmitter();
$emitter->emit($response);