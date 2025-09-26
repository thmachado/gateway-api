<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Token;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;

class TokenController
{
    public function __construct(
        private Token $token
    ) {

    }

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