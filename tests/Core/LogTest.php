<?php

declare(strict_types=1);

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

final class LogTest extends TestCase
{
    private Logger $log;
    private TestHandler $testHandler;

    protected function setUp(): void
    {
        $this->testHandler = new TestHandler();
        $this->log = new Logger("tests");
        $this->log->pushHandler($this->testHandler);
    }

    public function testLoggerInfo(): void
    {
        $this->log->info("My first log", ["username", "password"]);
        $data = $this->testHandler->getRecords();

        $this->assertNotEmpty($data);
        $this->assertIsArray($data);
        $this->assertEquals("My first log", $data[0]["message"]);
        $this->assertEquals("tests", $data[0]["channel"]);
        $this->assertEquals(["username", "password"], $data[0]["context"]);
        $this->assertEquals(Logger::INFO, $data[0]["level"]);
    }
}
