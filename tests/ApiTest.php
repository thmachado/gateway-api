<?php

declare(strict_types=1);

use App\Core\Database;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

final class ApiTest extends TestCase
{
    private Client $client;
    private string $bearerToken;
    private PDO $pdo;

    protected function setUp(): void
    {
        $pdo = Database::getInstance();
        if ($pdo instanceof PDO === false) {
            throw new RuntimeException("Database error!");
        }

        $this->pdo = $pdo;
        $this->pdo->beginTransaction();
        $this->client = new Client([
            "base_uri" => "http://server:80/api/v1/",
            "http_errors" => false
        ]);

        $response = $this->client->get("token");
        /** @var array<string> $body */
        $body = json_decode((string) $response->getBody(), true);
        $this->bearerToken = $body["token"];
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function testGetToken(): void
    {
        $response = $this->client->get("token");
        $body = (array) json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey("token", $body);
        $this->assertCount(1, $body);
        $this->assertNotEmpty($body["token"]);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testApiWithoutToken(): void
    {
        $response = $this->client->get("customers");
        /** @var array<string, array<string>> $body */
        $body = (array) json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey("error", $body);
        $this->assertEquals(401, $body["error"]["code"]);
        $this->assertEquals("Token not provided", $body["error"]["message"]);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testApiIndex(): void
    {
        $response = $this->client->get("customers", [
            "headers" => ["Authorization" => "Bearer {$this->bearerToken}"]
        ]);
        /** @var array<string, array<string>> $body */
        $body = (array) json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey("count", $body);
        $this->assertArrayHasKey("customers", $body);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testApiStoreJsonWithoutContentType(): void
    {
        $response = $this->client->post("customers", [
            "headers" => ["Authorization" => "Bearer {$this->bearerToken}"]
        ]);
        /** @var array<string, array<string>> $body */
        $body = (array) json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey("error", $body);
        $this->assertEquals(415, $body["error"]["code"]);
        $this->assertEquals("Content-Type header is required", $body["error"]["message"]);
        $this->assertEquals(415, $response->getStatusCode());
    }

    public function testApiStoreJsonInvalid(): void
    {
        $response = $this->client->post("customers", [
            "headers" => ["Authorization" => "Bearer {$this->bearerToken}", "Content-Type" => "application/json"]
        ]);
        /** @var array<string, array<string>> $body */
        $body = (array) json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey("error", $body);
        $this->assertEquals(422, $body["error"]["code"]);
        $this->assertEquals("Invalid format (only json)", $body["error"]["message"]);
        $this->assertEquals(422, $response->getStatusCode());
    }

    public function testApiStoreJsonEmpty(): void
    {
        $response = $this->client->post("customers", [
            "headers" => ["Authorization" => "Bearer {$this->bearerToken}", "Content-Type" => "application/json"],
            "json" => []
        ]);
        /** @var array<string, array<string>> $body */
        $body = (array) json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey("error", $body);
        $this->assertEquals(400, $body["error"]["code"]);
        $this->assertEquals("No fields provided", $body["error"]["message"]);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testApiStoreJsonWrong(): void
    {
        $response = $this->client->post("customers", [
            "headers" => ["Authorization" => "Bearer {$this->bearerToken}", "Content-Type" => "application/json"],
            "json" => ["external" => "externalcode", "name" => "Thiago", "document" => "1917"]
        ]);
        /** @var array<string, array<string, array<string>>> $body */
        $body = (array) json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey("error", $body);
        $this->assertEquals(422, $body["error"]["code"]);
        $this->assertEquals("Validation failed", $body["error"]["message"]);
        $this->assertEquals("emails must be present", $body["error"]["errors"]["emails"]);
        $this->assertEquals("phones must be present", $body["error"]["errors"]["phones"]);
        $this->assertEquals(422, $response->getStatusCode());
    }

    public function testApiStoreJson(): void
    {
        $response = $this->client->post("customers", [
            "headers" => ["Authorization" => "Bearer {$this->bearerToken}", "Content-Type" => "application/json"],
            "json" => ["external" => bin2hex(random_bytes(24)), "name" => "Thiago", "document" => "1917", "emails" => ["thiago@email.com"], "phones" => ["1145131617"]]
        ]);
        /** @var array<string, array<string>> $body */
        $body = (array) json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey("customer", $body);
        $this->assertArrayHasKey("code", $body["customer"]);
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testApiCodeInvalid(): void
    {
        $response = $this->client->get("customers/1917", [
            "headers" => ["Authorization" => "Bearer {$this->bearerToken}"]
        ]);
        /** @var array<string, array<string>> $body */
        $body = (array) json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey("error", $body);
        $this->assertEquals(400, $body["error"]["code"]);
        $this->assertEquals("Invalid customer code", $body["error"]["message"]);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testApiCodeNotFound(): void
    {
        $response = $this->client->get("customers/19142025-37ee-4bf2-b677-cae19bd7d579", [
            "headers" => ["Authorization" => "Bearer {$this->bearerToken}"]
        ]);
        /** @var array<string, array<string>> $body */
        $body = (array) json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey("error", $body);
        $this->assertEquals(404, $body["error"]["code"]);
        $this->assertEquals("Customer not found", $body["error"]["message"]);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testApiShow(): void
    {
        $response = $this->client->post("customers", [
            "headers" => ["Authorization" => "Bearer {$this->bearerToken}", "Content-Type" => "application/json"],
            "json" => ["external" => bin2hex(random_bytes(24)), "name" => "Thiago", "document" => "1917", "emails" => ["thiago@email.com"], "phones" => ["1145131617"]]
        ]);
        /** @var array<string, array<string>> $body */
        $body = (array) json_decode((string) $response->getBody(), true);

        $customer = $body["customer"]["code"];

        $response = $this->client->get("customers/{$customer}", [
            "headers" => ["Authorization" => "Bearer {$this->bearerToken}"]
        ]);

        /** @var array<string, array<string>> $body */
        $body = (array) json_decode((string) $response->getBody(), true);

        $this->assertArrayHasKey("customer", $body);
        $this->assertArrayHasKey("code", $body["customer"]);
        $this->assertEquals("Thiago", $body["customer"]["name"]);
        $this->assertEquals("thiago@email.com", $body["customer"]["emails"][0]);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testApiUpdate(): void
    {
        $response = $this->client->post("customers", [
            "headers" => ["Authorization" => "Bearer {$this->bearerToken}", "Content-Type" => "application/json"],
            "json" => ["external" => bin2hex(random_bytes(24)), "name" => "Thiago", "document" => "1917", "emails" => ["thiago@email.com"], "phones" => ["1145131617"]]
        ]);
        /** @var array<string, array<string>> $body */
        $body = (array) json_decode((string) $response->getBody(), true);

        $customer = $body["customer"]["code"];

        $response = $this->client->put("customers/{$customer}", [
            "headers" => ["Authorization" => "Bearer {$this->bearerToken}", "Content-Type" => "application/json"],
            "json" => ["name" => "Name updated", "document" => "1914"]
        ]);
        /** @var array<string, array<string>> $body */
        $body = (array) json_decode((string) $response->getBody(), true);

        $this->assertArrayHasKey("customer", $body);
        $this->assertArrayHasKey("code", $body["customer"]);
        $this->assertEquals("Name updated", $body["customer"]["name"]);
        $this->assertEquals("1914", $body["customer"]["document"]);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testApiDelete(): void
    {
        $response = $this->client->post("customers", [
            "headers" => ["Authorization" => "Bearer {$this->bearerToken}", "Content-Type" => "application/json"],
            "json" => ["external" => bin2hex(random_bytes(24)), "name" => "Thiago", "document" => "1917", "emails" => ["thiago@email.com"], "phones" => ["1145131617"]]
        ]);

        /** @var array<string, array<string>> $body */
        $body = (array) json_decode((string) $response->getBody(), true);

        $customer = $body["customer"]["code"];
        $response = $this->client->delete("customers/{$customer}", [
            "headers" => ["Authorization" => "Bearer {$this->bearerToken}"]
        ]);

        /** @var array<string, array<string>> $body */
        $body = (array) json_decode((string) $response->getBody(), true);
        $this->assertEquals(204, $response->getStatusCode());
    }
}
