<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;

class RequestHandler implements RequestHandlerInterface
{
    /**
     * @param array<MiddlewareInterface> $middlewares
     * @param callable(ServerRequestInterface): ResponseInterface $controller
     * @param int $index
     */
    public function __construct(
        private $controller,
        private array $middlewares = [],
        private int $index = 0
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->index < count($this->middlewares)) {
            $middleware = $this->middlewares[$this->index];
            $this->index++;
            return $middleware->process($request, $this);
        }

        return ($this->controller)($request);
    }
}
