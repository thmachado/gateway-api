<?php

declare(strict_types=1);

use App\Core\Token;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PHPUnit\Framework\TestCase;

final class TokenTest extends TestCase
{
    private Token $token;
    private string $secret = "secret-test";

    protected function setUp(): void
    {
        $this->token = new Token($this->secret);
    }

    public function testGenerateToken(): void
    {
        $token = $this->token->generateToken([
            "user" => "test"
        ]);

        $this->assertEquals("eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyIjoidGVzdCJ9.cEDViXbyN4iL1TOK4MRUosg8qNgsFHPU8S0F3QTe07E", $token);
    }

    public function testValidToken(): void
    {
        $token = $this->token->generateToken([
            "user" => "test",
            "data" => "test_data"
        ]);

        $decoded = JWT::decode($token, new Key($this->secret, "HS256"));

        $this->assertEquals("test", $decoded->user);
        $this->assertEquals("test_data", $decoded->data);
    }

    public function testGetSecret(): void
    {
        $this->assertEquals("secret-test", $this->token->getSecret());
    }
}