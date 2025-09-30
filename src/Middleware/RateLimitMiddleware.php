<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Middleware\MiddlewareInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Predis\Client;
use Psr\Http\Message\{ServerRequestInterface, ResponseInterface};
use Psr\Http\Server\RequestHandlerInterface;

class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Client $client,
        private int $attempts = 5,
        private int $jail = 450,
        private int $spaces = 450
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /**
         * @var string $ip
         */
        $ip = $request->getServerParams()["REMOTE_ADDR"] ?? "undefined";
        $attemptsKey = "attempts:{$ip}";
        $jailKey = "jail:{$ip}";

        if ($this->client->exists($jailKey)) {
            return new JsonResponse(["error" => ["code" => 429, "message" => "Wait {$this->client->ttl($jailKey)} seconds."]], 429);
        }

        $response = $handler->handle($request);
        if ($response->getStatusCode() === 401) {
            $attempts = $this->client->incr($attemptsKey);
            $this->client->expire($attemptsKey, $this->spaces);

            $remaining = $this->attempts - $attempts;
            if ($attempts >= $this->attempts) {
                $this->client->setex($jailKey, $this->jail, "1");
                return new JsonResponse(["error" => ["code" => 429, "message" => "Too many attempts. Account locked for {$this->jail} seconds."]], 429);
            }

            return $response->withHeader('X-RateLimit-Remaining', (string) $remaining);
        }

        if ($response->getStatusCode() === 200) {
            $this->client->del([$attemptsKey, $jailKey]);
        }

        return $response;
    }
}
