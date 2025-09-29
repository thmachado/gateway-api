<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;

class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        return $response
            ->withHeader("Content-Type", "application/json")
            ->withHeader("X-Content-Type-Options", "nosniff")
            ->withHeader("X-Frame-Options", "DENY")
            ->withHeader("X-XSS-Protection", "1; mode=block")
            ->withHeader("Referrer-Policy", "no-referrer")
            ->withHeader("Content-Security-Policy", "default-src 'self'")
            ->withHeader("Strict-Transport-Security", "max-age=31536000; includeSubDomains; preload")
            ->withHeader("X-Powered-By", "")
            ->withHeader("Server", "");
    }
}