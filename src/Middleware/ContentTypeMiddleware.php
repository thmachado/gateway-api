<?php

declare(strict_types=1);

namespace App\Middleware;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\{ServerRequestInterface, ResponseInterface};
use Psr\Http\Server\RequestHandlerInterface;

class ContentTypeMiddleware implements MiddlewareInterface
{
    /**
     * Summary of __construct
     * @param array<string> $types
     */
    public function __construct(
        private array $types = ["application/json"],
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $header = strtok($request->getHeaderLine("Content-Type"), ";");
        if ($header === false) {
            return new JsonResponse(["error" => ["code" => 415, "message" => "Content-Type header is required"]], 415);
        }

        $contentType = trim($header);
        if (empty($contentType)) {
            return new JsonResponse(["error" => ["code" => 415, "message" => "Content-Type header is required"]], 415);
        }

        if (in_array(strtolower($contentType), $this->types) === false) {
            return new JsonResponse(["error" => ["code" => 415, "message" => "application/json is required."]], 415);
        }

        return $handler->handle($request);
    }
}
