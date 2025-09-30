<?php

declare(strict_types=1);

use App\Core\{Log, Redis};
use App\Models\Builders\CustomerBuilder;
use App\Models\Customer;
use App\Repositories\{CacheRepository, CustomerRepository};
use App\Services\CacheService;
use PHPUnit\Framework\TestCase;
use Predis\Client;

final class CustomerRepositoryTest extends TestCase
{
    private Client $redis;
    private CacheService $cacheService;
    private CacheRepository $cacheRepository;
    private string $cacheKey = "app:customers_tests";

    protected function setUp(): void
    {
        $redis = Redis::getInstance();
        if ($redis instanceof Client === false) {
            throw new RuntimeException("Redis error!");
        }

        $this->redis = $redis;
        $this->cacheRepository = new CacheRepository(Log::getInstance(), $this->redis,  60);
        $this->cacheService = new CacheService($this->cacheRepository);
    }

    public function testFindAllRepository(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method("fetchAll")
            ->willReturn([
                [
                    "id" => 1,
                    "code" => "uuid001",
                    "external" => "externalhash001",
                    "name" => "Thiago",
                    "document" => "405823371",
                    "emails" => json_encode(["thiago@email.com"]),
                    "phones" => json_encode(["1145131617"])
                ]
            ]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method("prepare")->willReturn($stmt);

        $repository = new CustomerRepository($pdo, $this->cacheService, $this->cacheKey);
        $customers = $repository->findAll();

        $this->assertCount(1, $customers);
        $this->assertEquals("uuid001", $customers[0]["code"]);
        $this->assertEquals("405823371", $customers[0]["document"]);
        $this->assertEquals("1145131617", $customers[0]["phones"][0]);
    }

    public function testFindByCodeRepository(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method("fetch")
            ->willReturn([
                "id" => 1,
                "code" => "uuid001",
                "external" => "externalhash001",
                "name" => "Thiago",
                "document" => "405823371",
                "emails" => json_encode(["thiago@email.com"]),
                "phones" => json_encode(["1145131617"])
            ]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method("prepare")->willReturn($stmt);
        $repository = new CustomerRepository($pdo, $this->cacheService, $this->cacheKey);
        $customer = $repository->findByCode("uuid001");

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertEquals("externalhash001", $customer->getExternal());
        $this->assertEquals("thiago@email.com", $customer->getEmails()[0]);
        $this->assertEquals(1, $customer->getId());
    }

    public function testSaveRepository(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method("execute")->willReturn(true);

        $stmtQuery = $this->createMock(PDOStatement::class);
        $stmtQuery->method("execute")->willReturn(true);
        $stmtQuery->method("fetch")->willReturn([
            "id" => 1,
            "code" => "uuid001",
            "external" => "externalhash002",
            "name" => "Thiago",
            "document" => "405823371",
            "emails" => json_encode(["thiago@email.com"]),
            "phones" => json_encode(["1145131617"]),
        ]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method("prepare")
            ->willReturnOnConsecutiveCalls($stmt, $stmtQuery);
        $pdo->method("lastInsertId")->willReturn("1");

        $repository = new CustomerRepository($pdo, $this->cacheService, $this->cacheKey);
        $customer = $repository->save([
            "external" => "externalhash002",
            "name" => "Thiago",
            "document" => "405823371",
            "emails" => ["thiago@email.com"],
            "phones" => ["1145131617"]
        ]);

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertEquals(1, $customer->getId());
        $this->assertEquals("Thiago", $customer->getName());
        $this->assertEquals("1145131617", $customer->getPhones()[0]);
    }

    public function testUpdateRepository(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method("execute")->willReturn(true);
        $pdo = $this->createMock(PDO::class);
        $pdo->method("prepare")->willReturn($stmt);

        $repository = new CustomerRepository($pdo, $this->cacheService, $this->cacheKey);

        $customer = (new CustomerBuilder())
            ->withExternal("externalhash002")
            ->withName("Thiago")
            ->withDocument("405823371")
            ->withEmails(["old@email.com"])
            ->withPhones(["99999999"])
            ->build();

        $customer->setId(1);
        $customer->setCode("uuid001");

        $updated = $repository->update($customer, [
            "name" => "Thiago Atualizado",
            "document" => "415823371"
        ]);

        if ($updated === null) {
            throw new RuntimeException("Customer not found");
        }

        $this->assertEquals("Thiago Atualizado", $updated->getName());
        $this->assertEquals("415823371", $updated->getDocument());
    }

    public function testDeleteRepository(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('rowCount')->willReturn(1);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $customer = (new CustomerBuilder())
            ->withExternal("externalhash002")
            ->withName("Thiago")
            ->withDocument("405823371")
            ->withEmails(["old@email.com"])
            ->withPhones(["99999999"])
            ->build();

        $customer->setId(1);
        $customer->setCode("uuid001");

        $repository = new CustomerRepository($pdo, $this->cacheService, $this->cacheKey);
        $result = $repository->delete($customer);
        $this->assertTrue($result);
    }
}
