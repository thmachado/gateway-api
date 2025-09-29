<?php

declare(strict_types=1);

namespace App\Repositories;

use Exception;
use Predis\Client;
use Psr\Log\LoggerInterface;

class CacheRepository
{
    public function __construct(
        private LoggerInterface $log,
        private ?Client $client = null,
        private int $ttl = 60,
    ) {
    }

    /**
     * Summary of get
     * @param string $key
     * @return array<int, array{code: string, document: string, external: string, name: string, emails: array<string>, phones: array<string>}>
     */
    public function get(string $key): ?array
    {
        if ($this->client === null) {
            return null;
        }

        try {
            $cachedKey = $this->client->get($key);
            if ($cachedKey) {
                /** @var array<int, array{code: string, document: string, external: string, name: string, emails: array<string>, phones: array<string>}> $decoded */
                $decoded = json_decode($cachedKey, true);
                return $decoded;
            }
        } catch (Exception $e) {
            $this->log->error("Error", ["exception" => "Cache get error", "error" => $e->getMessage()]);
        }

        return null;
    }

    /**
     * @param string $key
     * @param array<mixed>|string $value
     */
    public function set(string $key, array|string $value): bool
    {
        if ($this->client === null) {
            return false;
        }

        try {
            $this->client->setex($key, $this->ttl, json_encode($value));
        } catch (Exception $e) {
            $this->log->error("Error", ["exception" => "Cache set error", "error" => $e->getMessage()]);
        }

        return true;
    }

    public function del(string $key): bool
    {
        if ($this->client === null) {
            return false;
        }

        try {
            $this->client->del($key);
        } catch (Exception $e) {
            $this->log->error("Error", ["exception" => "Cache del error", "error" => $e->getMessage()]);
        }

        return true;
    }

    public function pipeline(callable $callback): void
    {
        if ($this->client === null) {
            return;
        }

        try {
            $this->client->pipeline($callback);
        } catch (Exception $e) {
            $this->log->error("Error", ["exception" => "Cache pipeline error", "error" => $e->getMessage()]);
        }
    }
}