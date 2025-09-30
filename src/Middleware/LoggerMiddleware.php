<?php

declare(strict_types=1);

namespace App\Middleware;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class LoggerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $log
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $start = microtime(true);

        try {
            $response = $handler->handle($request);
        } catch (Throwable $e) {
            $this->log->error("Exception", [
                "exception" => $e->getMessage(),
                "method" => $request->getMethod(),
                "path" => $request->getUri()->getPath()
            ]);

            return new JsonResponse(["error" => ["code" => 500, "message" => "Server Error"]], 500);
        }

        $duration = round((microtime(true) - $start) * 1000, 2);

        $this->log->info("HTTP Request handled", [
            "method" => $request->getMethod(),
            "path" => $request->getUri()->getPath(),
            "status" => $response->getStatusCode(),
            "duration" => $duration,
        ]);

        return $response;
    }
}
