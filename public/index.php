<?php

declare(strict_types=1);

use App\Controllers\{CustomerController, TokenController};
use App\Core\{Database, Log, Router, Token};
use App\Middleware\{LoggerMiddleware, JwtMiddleware};
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
    JwtMiddleware::class => DI\autowire(),
    CustomerRepository::class => DI\autowire(),
    CustomerService::class => DI\autowire(),
    CustomerValidator::class => DI\autowire(),
    Token::class => DI\create()->constructor(getenv("PEPPER"))
]);

$container = $containerBuilder->build();
$router = new Router($container);

/** @var App\Middleware\MiddlewareInterface $loggerMiddleware; */
$loggerMiddleware = $container->get(LoggerMiddleware::class);
/** @var \App\Middleware\MiddlewareInterface $jwtMiddleware; */
$jwtMiddleware = $container->get(JwtMiddleware::class);
$router->addMiddleware($loggerMiddleware);

$router->get("/health", function (): JsonResponse {
    return new JsonResponse(["status" => "Health check is ok!"], 200);
});

$router->get("/api/v1/token", [TokenController::class, "index"]);
$router->get("/api/v1/customers", [CustomerController::class, "index"], [$jwtMiddleware]);
$router->post("/api/v1/customers", [CustomerController::class, "store"], [$jwtMiddleware]);
$router->get("/api/v1/customers/{code}", [CustomerController::class, "show"], [$jwtMiddleware]);
$router->put("/api/v1/customers/{code}", [CustomerController::class, "update"], [$jwtMiddleware]);
$router->delete("/api/v1/customers/{code}", [CustomerController::class, "delete"], [$jwtMiddleware]);

$response = $router->dispatch(ServerRequestFactory::fromGlobals());
$emitter = new SapiEmitter();
$emitter->emit($response);