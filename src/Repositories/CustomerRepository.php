<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Builders\CustomerBuilder;
use App\Models\Customer;
use App\Services\CacheService;
use PDO;

class CustomerRepository implements CustomerRepositoryInterface
{
    public function __construct(
        private PDO $pdo,
        private CacheService $cacheService,
        private string $cacheKey = "app:customers"
    ) {
    }

    /**
     * Summary of findAll
     * @return array<int, array{code: string, document: string, external: string, name: string, emails: array<string>, phones: array<string>}>
     */
    public function findAll(): array
    {   
        /**
         * @var array<int, array{code: string, document: string, external: string, name: string, emails: array<string>, phones: array<string>}> $cachedCustomers
         */
        $cachedCustomers = $this->cacheService->get($this->cacheKey);
        if ($cachedCustomers) {
            return $cachedCustomers;
        }

        $data = [];
        $stmt = $this->pdo->prepare("SELECT id, code, external, name, document, emails, phones FROM customers ORDER BY name ASC");
        $stmt->execute();

        /**
         * @var array<int, array<string, string>> $customers
         */
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($customers as $customer) {

            /**
             * @var array<string> $emails
             */
            $emails = json_decode((string) $customer["emails"], true) ?? [];
            /**
             * @var array<string> $phones
             */
            $phones = json_decode((string) $customer["phones"], true) ?? [];

            $customerModel = (new CustomerBuilder())
                ->withExternal($customer["external"])
                ->withName($customer["name"])
                ->withDocument($customer["document"])
                ->withEmails((array) $emails)
                ->withPhones((array) $phones)
                ->build();

            $customerModel->setId((int) $customer["id"]);
            $customerModel->setCode($customer["code"]);
            $data[] = $customerModel->toArray();
        }

        if (empty($data) === false) {
            $this->cacheService->set($this->cacheKey, $data);
        }

        return $data;
    }

    public function findByCode(string $code): ?Customer
    {
         /**
         * @var array<string, string>|false $cachedUser
         */
        $cachedUser = $this->cacheService->get("{$this->cacheKey}:{$code}");
        if ($cachedUser) {
            /**
             * @var array<string> $emails
             */
            $emails = json_decode((string) $cachedUser["emails"], true) ?? [];
            /**
             * @var array<string> $phones
             */
            $phones = json_decode((string) $cachedUser["phones"], true) ?? [];

            $customerModel = (new CustomerBuilder())
                ->withExternal($cachedUser["external"])
                ->withName($cachedUser["name"])
                ->withDocument($cachedUser["document"])
                ->withEmails((array) $emails)
                ->withPhones((array) $phones)
                ->build();

            $customerModel->setId((int) $cachedUser["id"]);
            $customerModel->setCode($cachedUser["code"]);
            return $customerModel;
        }

        $stmt = $this->pdo->prepare("SELECT id, code, external, name, document, emails, phones FROM customers WHERE code = :code");
        $stmt->bindValue(":code", $code, PDO::PARAM_STR);
        $stmt->execute();
        /**
         * @var array<string, string>|false $customer
         */
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($customer === false) {
            return null;
        }

        /**
         * @var array<string> $emails
         */
        $emails = json_decode((string) $customer["emails"], true) ?? [];
        /**
         * @var array<string> $phones
         */
        $phones = json_decode((string) $customer["phones"], true) ?? [];

        $customerModel = (new CustomerBuilder())
            ->withExternal($customer["external"])
            ->withName($customer["name"])
            ->withDocument($customer["document"])
            ->withEmails((array) $emails)
            ->withPhones((array) $phones)
            ->build();

        $customerModel->setId((int) $customer["id"]);
        $customerModel->setCode($customer["code"]);
        $this->cacheService->set("{$this->cacheKey}:" . $customer["code"], $customerModel->toArray());
        return $customerModel;
    }

    public function save(array $data): ?Customer
    {
        $stmt = $this->pdo->prepare("INSERT INTO customers (external, name, document, emails, phones) VALUES (:external, :name, :document, :emails, :phones)");
        $stmt->bindValue(":external", $data["external"], PDO::PARAM_STR);
        $stmt->bindValue(":name", $data["name"], PDO::PARAM_STR);
        $stmt->bindValue(":document", $data["document"], PDO::PARAM_STR);
        $stmt->bindValue(":emails", json_encode($data["emails"]), PDO::PARAM_STR);
        $stmt->bindValue(":phones", json_encode($data["phones"]), PDO::PARAM_STR);
        $stmt->execute();

        $customerid = (int) $this->pdo->lastInsertId();

        $stmt = $this->pdo->prepare("SELECT id, code, external, name, document, emails, phones FROM customers WHERE id = :id");
        $stmt->bindValue(":id", $customerid, PDO::PARAM_INT);

        $stmt->execute();
        /**
         * @var array<string, string>|false $customer
         */
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($customer === false) {
            return null;
        }

        /**
         * @var array<string> $emails
         */
        $emails = json_decode((string) $customer["emails"], true) ?? [];
        /**
         * @var array<string> $phones
         */
        $phones = json_decode((string) $customer["phones"], true) ?? [];

        $customerModel = (new CustomerBuilder())
            ->withExternal($customer["external"])
            ->withName($customer["name"])
            ->withDocument($customer["document"])
            ->withEmails((array) $emails)
            ->withPhones((array) $phones)
            ->build();

        $customerModel->setId((int) $customer["id"]);
        $customerModel->setCode($customer["code"]);
        $this->cacheService->del($this->cacheKey);

        return $customerModel;
    }

    /**
     * Summary of update
     * @param \App\Models\Customer $customer
     * @param array{name?: string, document?: string} $data
     * @return Customer
     */
    public function update(Customer $customer, array $data): ?Customer
    {
        $fields = ["name", "document"];
        $updateFields = [];
        $bindings = [":id" => $customer->getId()];

        foreach ($data as $key => $value) {
            if (in_array($key, $fields)) {
                $updateFields[] = "{$key} = :{$key}";

                $method = "set" . ucfirst($key);
                if (method_exists($customer, $method)) {
                    $customer->$method($value);
                }

                $bindings[":{$key}"] = $value;
            }
        }

        if (empty($updateFields)) {
            return $customer;
        }

        $stmt = $this->pdo->prepare("UPDATE customers SET " . implode(",", $updateFields) . " WHERE id = :id");
        $stmt->execute($bindings);

        $this->cacheService->del("{$this->cacheKey}:{$customer->getCode()}");
        $this->cacheService->del($this->cacheKey);
        return $customer;
    }

    public function delete(Customer $customer): bool
    {
        $code = $customer->getCode();
        $stmt = $this->pdo->prepare("DELETE FROM customers WHERE code = :code");
        $stmt->bindValue(":code", $code, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() === 1) {
            return true;
        }

        $this->cacheService->pipeline(function () use ($code): void {
            $this->cacheService->del("{$this->cacheKey}:{$code}");
            $this->cacheService->del($this->cacheKey);
        });

        return false;
    }

    public function findByExternal(string $external): bool
    {
        $stmt = $this->pdo->prepare("SELECT id FROM customers WHERE external = :external");
        $stmt->bindValue(":external", $external, PDO::PARAM_STR);
        $stmt->execute();
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($customer === false) {
            return false;
        }

        return true;
    }
}