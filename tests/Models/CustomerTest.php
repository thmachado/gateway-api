<?php

declare(strict_types=1);

use App\Models\Builders\CustomerBuilder;
use App\Models\Customer;
use PHPUnit\Framework\TestCase;

final class CustomerTest extends TestCase
{
    private Customer $customer;

    protected function setUp(): void
    {
        $this->customer = (new CustomerBuilder())
            ->withExternal("hashcreatedbyexternal")
            ->withName("Thiago")
            ->withDocument("110112335")
            ->withEmails(["thiago@email.com", "machado@email.com"])
            ->withPhones(["11991123450", "1145131617"])
            ->build();
    }

    public function testCustomerCreate(): void
    {
        $this->assertEquals("hashcreatedbyexternal", $this->customer->getExternal());
        $this->assertEquals("Thiago", $this->customer->getName());
        $this->assertEquals("110112335", $this->customer->getDocument());
        $this->assertEquals("machado@email.com", $this->customer->getEmails()[1]);
        $this->assertEquals("11991123450", $this->customer->getPhones()[0]);
    }

    public function testSetId(): void
    {
        $this->customer->setId(10);
        $this->assertEquals(10, $this->customer->getId());
    }
}
