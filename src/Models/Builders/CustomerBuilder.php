<?php

declare(strict_types=1);

namespace App\Models\Builders;

use App\Models\Customer;

class CustomerBuilder
{
    private string $external;
    private string $name;
    private string $document;
    /**
     * Summary of emails
     * @var array<string>
     */
    private array $emails = [];
    /**
     * Summary of phones
     * @var array<string>
     */
    private array $phones = [];

    public function withExternal(string $external): self
    {
        $this->external = $external;
        return $this;
    }

    public function withName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function withDocument(string $document): self
    {
        $this->document = $document;
        return $this;
    }

    /**
     * Summary of withEmails
     * @param array<string> $data
     * @return CustomerBuilder
     */
    public function withEmails(array $data): self
    {
        $this->emails = $data;
        return $this;
    }

    /**
     * Summary of withPhones
     * @param array<string> $data
     * @return CustomerBuilder
     */
    public function withPhones(array $data): self
    {
        $this->phones = $data;
        return $this;
    }

    public function build(): Customer
    {
        return new Customer(
            external: $this->external,
            name: $this->name,
            document: $this->document,
            emails: $this->emails,
            phones: $this->phones
        );
    }
}