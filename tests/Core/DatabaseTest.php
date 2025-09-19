<?php

declare(strict_types=1);

use App\Core\Database;
use PHPUnit\Framework\TestCase;

final class DatabaseTest extends TestCase
{
    private ?PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = Database::getInstance();
        $this->pdo->beginTransaction();
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS users_test (
        id SERIAL PRIMARY KEY,
            firstname VARCHAR(255),
            lastname VARCHAR(255),
            email VARCHAR(255)
        )");
    }

    protected function tearDown(): void
    {
        $this->pdo->rollBack();
    }

    public function createUser(): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO users_test (firstname, lastname, email) VALUES (:firstname, :lastname, :email)");
        $stmt->bindValue(":firstname", "Thiago", PDO::PARAM_STR);
        $stmt->bindValue(":lastname", "Machado", PDO::PARAM_STR);
        $stmt->bindValue(":email", "thiago@email.com", PDO::PARAM_STR);
        $stmt->execute();
        return (int) $this->pdo->lastInsertId();
    }

    public function testInsertUser(): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO users_test (firstname, lastname, email) VALUES (:firstname, :lastname, :email)");
        $stmt->bindValue(":firstname", "Thiago", PDO::PARAM_STR);
        $stmt->bindValue(":lastname", "Machado", PDO::PARAM_STR);
        $stmt->bindValue(":email", "thiago@email.com", PDO::PARAM_STR);
        $result = $stmt->execute();

        $this->assertTrue($result);
        $this->assertEquals(1, $stmt->rowCount());
    }

    public function testSelectUserEmpty(): void
    {
        $stmt = $this->pdo->query("SELECT firstname, lastname FROM users_test ORDER BY firstname, lastname ASC");
        $query = $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->assertTrue($query);
        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function testSelectUserWithUsers(): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO users_test (firstname, lastname, email) VALUES (:firstname, :lastname, :email)");
        $stmt->bindValue(":firstname", "Thiago", PDO::PARAM_STR);
        $stmt->bindValue(":lastname", "Machado", PDO::PARAM_STR);
        $stmt->bindValue(":email", "thiago@email.com", PDO::PARAM_STR);
        $stmt->execute();

        $stmt = $this->pdo->query("SELECT firstname, lastname FROM users_test ORDER BY firstname, lastname ASC");
        $query = $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->assertTrue($query);
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals("Thiago", $result[0]["firstname"]);
        $this->assertEquals("Machado", $result[0]["lastname"]);
    }

    public function testUpdateUser(): void
    {
        $userid = $this->createUser();

        $stmt = $this->pdo->prepare("UPDATE users_test SET firstname = :firstname, lastname = :lastname, email = :email WHERE id = :id");
        $stmt->bindValue(":firstname", "Flaco", PDO::PARAM_STR);
        $stmt->bindValue(":lastname", "Lopez", PDO::PARAM_STR);
        $stmt->bindValue(":email", "flaco@lopez.com", PDO::PARAM_STR);
        $stmt->bindValue(":id", (int) $userid, PDO::PARAM_INT);
        $stmt->execute();
        $rowCount = $stmt->rowCount();

        $stmt = $this->pdo->prepare("SELECT firstname, lastname FROM users_test WHERE id = :id");
        $stmt->bindValue(":id", (int) $userid, PDO::PARAM_INT);
        $query = $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals(1, $rowCount);
        $this->assertTrue($query);
        $this->assertIsArray($result);
        $this->assertEquals("Flaco", $result["firstname"]);
        $this->assertEquals("Lopez", $result["lastname"]);
    }

    public function testDeleteUser(): void
    {
        $userid = $this->createUser();

        $stmt = $this->pdo->prepare("DELETE FROM users_test WHERE id = :id");
        $stmt->bindValue(":id", (int) $userid, PDO::PARAM_INT);
        $query = $stmt->execute();

        $this->assertEquals(1, $stmt->rowCount());
        $this->assertTrue($query);
    }
}