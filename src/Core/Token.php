<?php

declare(strict_types=1);

namespace App\Core;

use Firebase\JWT\JWT;

class Token
{
    public function __construct(
        private string $secret = "Palmeiras"
    ) {
    }

    /**
     * Summary of generateToken
     * @param array<string, int<1, max>|string> $payload
     * @return string
     */
    public function generateToken(array $payload = []): string
    {
        return JWT::encode($payload, $this->secret, "HS256");
    }

    public function getSecret(): string
    {
        return $this->secret;
    }
}