<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Customer;

interface CustomerRepositoryInterface
{
    /**
     * Summary of findAll
     * @return array<int, array{code: string, document: string, emails: array, external: string, name: string, emails: array<string>, phones: array<string>}>
     */
    public function findAll(): array;
    public function findByCode(string $code): ?Customer;

    /**
     * Summary of save
     * @param array{external: string, name: string, document: string, emails: array<string>, phones: array<string>} $data
     * @return ?Customer
     */
    public function save(array $data): ?Customer;

    /**
     * Summary of update
     * @param \App\Models\Customer $customer
     * @param array{external?: string, name?: string, document?: string, emails?: array<string>, phones?: array<string>} $data
     * @return ?Customer
     */
    public function update(Customer $customer, array $data): ?Customer;
    public function delete(Customer $customer): bool;
}
