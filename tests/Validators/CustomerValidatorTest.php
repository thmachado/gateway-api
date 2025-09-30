<?php

declare(strict_types=1);

use App\Exceptions\ValidationException;
use App\Validators\CustomerValidator;
use PHPUnit\Framework\TestCase;

final class CustomerValidatorTest extends TestCase
{
    private CustomerValidator $customerValidator;

    protected function setUp(): void
    {
        $this->customerValidator = new CustomerValidator();
    }

    public function testDataEmpty(): void
    {
        $this->expectExceptionMessage("Validation failed");
        $this->expectException(ValidationException::class);
        $this->customerValidator->validate([]);
    }

    public function testDataInvalid(): void
    {
        $this->expectExceptionMessage("Validation failed");
        $this->expectException(ValidationException::class);
        $this->customerValidator->validate([
            "external" => "",
            "name" => "Thiago",
            "document" => "405823379",
            "emails" => ["thiago@email.com"],
            "phones" => ["1145131617"]
        ]);
    }

    public function testInvalidEmails(): void
    {
        $this->expectExceptionMessage("Validation failed");
        $this->expectException(ValidationException::class);
        $this->customerValidator->validate([
            "external" => "hashexternal",
            "name" => "Thiago",
            "document" => "405823379",
            "emails" => ["thiago"],
            "phones" => ["1145131617"]
        ]);
    }

    public function testValidData(): void
    {
        $this->customerValidator->validate([
            "external" => "hashexternal",
            "name" => "Thiago",
            "document" => "405823379",
            "emails" => ["thiago@email.com"],
            "phones" => ["1145131617"]
        ]);

        $this->addToAssertionCount(1);
    }

    public function testPartialDataOnlyOne(): void
    {
        $this->customerValidator->validate(["name" => "Thiago"], false);
        $this->addToAssertionCount(1);
    }

    public function testPartialData(): void
    {
        $this->customerValidator->validate(["name" => "Thiago", "document" => "4053344852"], false);
        $this->addToAssertionCount(1);
    }
}
