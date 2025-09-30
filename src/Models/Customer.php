<?php

declare(strict_types=1);

namespace App\Models;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Customer",
    type: "object",
    properties: [
        new OA\Property(property: "code", type: "string"),
        new OA\Property(property: "external", type: "string"),
        new OA\Property(property: "name", type: "string"),
        new OA\Property(property: "document", type: "string"),
        new OA\Property(property: "emails", type: "array", items: new OA\Items(type: "string")),
        new OA\Property(property: "phones", type: "array", items: new OA\Items(type: "string")),
    ]
)]
class Customer
{
    private int $id;
    private string $code;

    /**
     * Summary of __construct
     * @param string $external
     * @param string $name
     * @param string $document
     * @param array<string> $emails
     * @param array<string> $phones
     */
    public function __construct(
        private string $external,
        private string $name,
        private string $document,
        private array $emails = [],
        private array $phones = []
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function setExternal(string $external): void
    {
        $this->external = $external;
    }

    public function getExternal(): string
    {
        return $this->external;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setDocument(string $document): void
    {
        $this->document = $document;
    }

    public function getDocument(): string
    {
        return $this->document;
    }

    /**
     * Summary of setEmails
     * @param array<string> $data
     * @return void
     */
    public function setEmails(array $data): void
    {
        $this->emails = $data;
    }

    /**
     * Summary of getEmails
     * @return array<string>
     */
    public function getEmails(): array
    {
        return $this->emails;
    }

    /**
     * Summary of setPhones
     * @param array<string> $data
     * @return void
     */
    public function setPhones(array $data): void
    {
        $this->phones = $data;
    }

    /**
     * Summary of getPhones
     * @return array<string>
     */
    public function getPhones(): array
    {
        return $this->phones;
    }

    /**
     * Summary of toArray
     * @return array{code: string, document: string, emails: string[], external: string, name: string, phones: string[]}
     */
    public function toArray(): array
    {
        return [
            "code" => $this->getCode(),
            "external" => $this->getExternal(),
            "name" => $this->getName(),
            "document" => $this->getDocument(),
            "emails" => $this->getEmails(),
            "phones" => $this->getPhones()
        ];
    }
}
