<?php

declare(strict_types=1);

namespace App\Traits;

trait Validate
{
    public function validatePattern(string $code): bool
    {
        $pattern = "/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i";
        if (preg_match($pattern, $code) === 0 || preg_match($pattern, $code) === false) {
            return false;
        }

        return true;
    }
}