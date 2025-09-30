<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class ValidationException extends RuntimeException
{
    /**
     * Summary of __construct
     * @param array<string> $errors
     * @param string $message
     * @param int $code
     */
    public function __construct(
        private array $errors,
        string $message = "Validation failed",
        int $code = 422
    ) {
        parent::__construct($message, $code);
    }

    /**
     * Summary of getErrors
     * @return array<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
