<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Token;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use OpenApi\Attributes as OA;

#[OA\Get(
    path: "/api/v1/token",
    operationId: "getToken",
    tags: ["Token"],
    summary: "Generate JWT token",
    responses: [
        new OA\Response(
            response: 200,
            description: "JWT token generated",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "token", type: "string", description: "JWT token")
                ]
            )
        )
    ]
)]
class TokenController
{
    public function __construct(
        private Token $token
    ) {}

    public function index(): ResponseInterface
    {
        $now = time();

        return new JsonResponse([
            "token" => $this->token->generateToken([
                "sub" => "1917",
                "name" => "Palmeiras",
                "role" => "guest",
                "iat" => $now,
                "exp" => $now + 3600,
                "nbf" => $now
            ])
        ]);
    }
}
