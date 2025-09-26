<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Builders\CustomerBuilder;
use App\Models\Customer;
use PDO;

class CustomerRepository implements CustomerRepositoryInterface
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Summary of findAll
     * @return array<int, array{code: string, document: string, external: string, name: string, emails: array<string>, phones: array<string>}>
     */
    public function findAll(): array
    {
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

        return $data;
    }

    public function findByCode(string $code): ?Customer
    {
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

        return $customer;
    }

    public function delete(Customer $customer): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM customers WHERE code = :code");
        $stmt->bindValue(":code", $customer->getCode(), PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() === 1) {
            return true;
        }

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