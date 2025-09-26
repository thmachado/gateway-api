<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\{MiddlewareInterface, RequestHandler};
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Log\LoggerInterface;

class Router
{
    private LoggerInterface $logger;

    /**
     * @param \Psr\Container\ContainerInterface $container
     * @param array<
     *   string,
     *   array<
     *     string,
     *     array{
     *       handler: callable(ServerRequestInterface): ResponseInterface|array{0: class-string, 1: string},
     *       middlewares: array<MiddlewareInterface>
     *     }
     *   >
     * > $routes
     * @param array<MiddlewareInterface> $middlewares
     */
    public function __construct(
        private ContainerInterface $container,
        private array $routes = [],
        private array $middlewares = []
    ) {
        /** @var LoggerInterface $loggerInterface; */
        $loggerInterface = $this->container->get(LoggerInterface::class);
        $this->logger = $loggerInterface;
    }

    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        if (isset($this->routes[$method]) === false) {
            $this->logger->warning("Method not found", [
                "method" => $method,
                "path" => $path,
                "status" => 405,
            ]);
            return new JsonResponse(["error" => ["code" => 404, "message" => "Method not found"]], 404);
        }

        foreach ($this->routes[$method] as $route => $config) {
            $pattern = '@^' . preg_replace("/\{([a-z]+)\}/", "(?P<$1>[^\/]+)", $route) . '$@i';
            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches);

                $request = $this->addParamsToRequest($request, $route, $matches);
                $handler = $config["handler"];

                $controller = fn(ServerRequestInterface $req) => $this->handle($handler, $req);
                $handler = new RequestHandler($controller, array_merge($this->middlewares, $config["middlewares"]));
                return $handler->handle($request);
            }
        }

        $this->logger->warning("Endpoint not found", [
            "method" => $method,
            "path" => $path,
            "status" => 404,
        ]);

        return new JsonResponse(["error" => ["code" => 404, "message" => "Endpoint not found"]], 404);
    }

    /**
     * Summary of addParamsToRequest
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param string $route
     * @param array<string> $matches
     * @return ServerRequestInterface
     */
    private function addParamsToRequest(ServerRequestInterface $request, string $route, array $matches): ServerRequestInterface
    {
        preg_match_all("/\{([a-z]+)\}/i", $route, $paramNames);
        foreach ($paramNames[1] as $index => $name) {
            if (isset($matches[$index])) {
                $request = $request->withAttribute($name, $matches[$index]);
            }
        }

        return $request;
    }

    /**
     * Summary of handle
     * @param array<string>|callable $handler
     * @param \Psr\Http\Message\ServerRequestInterface $request
     */
    private function handle(array|callable $handler, ServerRequestInterface $request): ResponseInterface
    {
        if (is_callable($handler)) {
            /** @var callable(ServerRequestInterface): ResponseInterface $handler */
            return $handler($request);
        }

        [$class, $method] = $handler;
        /** @var string $class */
        if (class_exists($class) === false) {
            $this->logger->error("Class not found", [
                "method" => $request->getMethod(),
                "path" => $request->getUri()->getPath(),
                "status" => 405,
            ]);
            return new JsonResponse(["error" => ["code" => 404, "message" => "Class not found"]], 404);
        }

        $classInstance = $this->container->get($class);
        /**
         *  @var object|string $classInstance
         *  @var string $method 
         * */
        if (method_exists($classInstance, $method) === false) {
            $this->logger->error("Method not found", [
                "method" => $request->getMethod(),
                "path" => $request->getUri()->getPath(),
                "status" => 405,
            ]);

            return new JsonResponse(["error" => ["code" => 404, "message" => "Method not found"]], 404);
        }

        /** @var callable(ServerRequestInterface): ResponseInterface $callable */
        $callable = [$classInstance, $method];
        return $callable($request);
    }

    /**
     * @param string $method
     * @param string $path
     * @param callable(ServerRequestInterface): ResponseInterface|array{0: class-string, 1: string} $callback
     * @param array<MiddlewareInterface> $middlewares
     */
    private function registerRoute(string $method, string $path, array|callable $callback, array $middlewares = []): void
    {
        $this->routes[$method][$path] = ["handler" => $callback, "middlewares" => $middlewares];
    }

    /**
     * Summary of get
     * @param string $path
     * @param callable(ServerRequestInterface): ResponseInterface|array{0: class-string, 1: string} $callback
     * @param array<MiddlewareInterface> $middlewares

     * @return void
     */

    public function get(string $path, array|callable $callback, array $middlewares = []): void
    {
        $this->registerRoute("GET", $path, $callback, $middlewares);
    }

    /**
     * Summary of post
     * @param string $path
     * @param array<callable, string>|callable $callback
     * @param callable(ServerRequestInterface): ResponseInterface|array{0: class-string, 1: string} $callback
     * @param array<MiddlewareInterface> $middlewares

     * @return void
     */
    public function post(string $path, array|callable $callback, array $middlewares = []): void
    {
        $this->registerRoute("POST", $path, $callback, $middlewares);
    }

    /**
     * Summary of put
     * @param string $path
     * @param callable(ServerRequestInterface): ResponseInterface|array{0: class-string, 1: string} $callback
     * @param array<MiddlewareInterface> $middlewares

     * @return void
     */
    public function put(string $path, array|callable $callback, array $middlewares = []): void
    {
        $this->registerRoute("PUT", $path, $callback, $middlewares);
    }

    /**
     * Summary of delete
     * @param string $path
     * @param callable(ServerRequestInterface): ResponseInterface|array{0: class-string, 1: string} $callback
     * @param array<MiddlewareInterface> $middlewares

     * @return void
     */
    public function delete(string $path, array|callable $callback, array $middlewares = []): void
    {
        $this->registerRoute("DELETE", $path, $callback, $middlewares);
    }
}