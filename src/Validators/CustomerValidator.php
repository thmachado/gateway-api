<?php

declare(strict_types=1);

namespace App\Validators;

use App\Exceptions\ValidationException;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

class CustomerValidator
{
    /**
     * Summary of validate
     * @param array{external?: string, name?: string, document?: string, emails?: array<string>, phones?: array<string>} $data
     * @param bool $notPartial
     * @throws \App\Exceptions\ValidationException
     * @return void
     */
    public function validate(array $data, bool $notPartial = true): void
    {
        $validator = Validator::arrayType()
            ->key("external", Validator::stringType()->notEmpty()->length(1, 255), $notPartial)
            ->key("name", Validator::stringType()->notEmpty()->length(1, 255), $notPartial)
            ->key("document", Validator::stringType()->notEmpty()->length(1, 255), $notPartial)
            ->key("emails", Validator::arrayType()->notEmpty()->each(Validator::email()), $notPartial)
            ->key("phones", Validator::arrayType(), $notPartial);
        try {
            $validator->assert($data);
        } catch (NestedValidationException $exception) {
            throw new ValidationException($exception->getMessages());
        }
    }
}