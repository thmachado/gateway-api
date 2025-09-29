<?php

declare(strict_types=1);

use App\Core\Redis;
use PHPUnit\Framework\TestCase;
use Predis\Client;

final class RedisTest extends TestCase
{
    private Client $redis;
    private string $key = "app:tests";
    private int $ttl = 10;

    protected function setUp(): void
    {
        $redis = Redis::getInstance();
        if ($redis instanceof Client === false) {
            throw new RuntimeException("Redis error!");
        }

        $this->redis = $redis;
    }

    public function testRedisInstance(): void
    {
        $this->assertInstanceOf(Client::class, $this->redis);
    }

    public function testSetExAndGet(): void
    {
        $data = ["Palmeiras", "Raphael Veiga", "Vitor Roque", "Flaco López"];
        $this->redis->setex($this->key, $this->ttl, json_encode($data));

        $dataRedis = $this->redis->get($this->key) ?? "";
        /** @var array<string> $dataJSON */
        $dataJSON = json_decode((string) $dataRedis, true);

        $this->assertCount(4, $dataJSON);
        $this->assertEquals($data, $dataJSON);
        $this->assertEquals("Raphael Veiga", $dataJSON[1]);
        $this->assertEquals("Flaco López", $dataJSON[3]);
    }
}