<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\Customer;
use App\Repositories\CustomerRepository;
use App\Validators\CustomerValidator;

class CustomerService
{
    public function __construct(
        private CustomerRepository $customerRepository,
        private CustomerValidator $customerValidator
    ) {
    }

    /**
     * Summary of getCustomers
     * @return array<int, array{code: string, document: string, emails: array, external: string, name: string, emails: array<string>, phones: array<string>}>
     */
    public function getCustomers(): array
    {
        return $this->customerRepository->findAll();
    }

    public function getCustomerByCode(string $code): ?Customer
    {
        return $this->customerRepository->findByCode($code);
    }

    /**
     * Summary of createCustomer
     * @param array{external: string, name: string, document: string, emails: array<string>, phones: array<string>} $data
     * @throws \App\Exceptions\ValidationException
     * @return Customer|null
     */
    public function createCustomer(array $data): ?Customer
    {
        $this->customerValidator->validate($data);
        if ($this->customerRepository->findByExternal($data["external"])) {
            throw new ValidationException([], "External code already used", 422);
        }

        return $this->customerRepository->save($data);
    }

    /**
     * Summary of updateCustomer
     * @param \App\Models\Customer $customer
     * @param array{name?: string, document?: string} $data
     * @return Customer|null
     */
    public function updateCustomer(Customer $customer, array $data): ?Customer
    {
        $this->customerValidator->validate($data, false);
        return $this->customerRepository->update($customer, $data);
    }

    public function deleteCustomer(Customer $customer): bool
    {
        return $this->customerRepository->delete($customer);
    }
}