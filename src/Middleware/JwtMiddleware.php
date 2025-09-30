<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Token;
use Exception;
use Firebase\JWT\{ExpiredException, JWT, Key, SignatureInvalidException};
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;

class JwtMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Token $token
    ) {}
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authorization = $request->getHeaderLine("Authorization");
        if (empty($authorization)) {
            return new JsonResponse(["error" => ["code" => 401, "message" => "Token not provided"]], 401);
        }

        [$authHeader, $bearer] = explode(" ", $request->getHeaderLine("Authorization"));
        if (empty($authHeader) || empty($bearer) || $authHeader !== "Bearer") {
            return new JsonResponse(["error" => ["code" => 401, "message" => "Token not provided"]], 401);
        }

        try {
            $decoded = JWT::decode($bearer, new Key($this->token->getSecret(), "HS256"));
            $request = $request->withAttribute("user", $decoded);
        } catch (ExpiredException) {
            return new JsonResponse(["error" => ["code" => 401, "message" => "Expired token"]], 401);
        } catch (SignatureInvalidException) {
            return new JsonResponse(["error" => ["code" => 401, "message" => "Invalid token"]], 401);
        } catch (Exception) {
            return new JsonResponse(["error" => ["code" => 401, "message" => "Authentication is failed"]], 401);
        }

        return $handler->handle($request);
    }
}
