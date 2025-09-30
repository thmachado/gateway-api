<?php

declare(strict_types=1);

use App\Traits\Validate;
use PHPUnit\Framework\TestCase;

final class ValidateTest extends TestCase
{
    use Validate;

    public function testInvalidPattern(): void
    {
        $this->assertEquals(false, $this->validatePattern("teste"));
        $this->assertEquals(false, $this->validatePattern(""));
        $this->assertEquals(false, $this->validatePattern("dffefeff-ffee-fbff-bfff"));
    }

    public function testValidPattern(): void
    {
        $this->assertEquals(true, $this->validatePattern("5887517f-37ee-4bf2-b677-cae19bd7d579"));
        $this->assertEquals(true, $this->validatePattern("d485ca89-eb67-4a01-958c-4b0afafe41b8"));
        $this->assertEquals(true, $this->validatePattern("a6322955-8a47-4ca1-b35b-e5cd7fc29379"));
    }
}
