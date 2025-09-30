<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CacheRepository;

class CacheService
{
    public function __construct(
        private CacheRepository $cacheRepository
    ) {}

    public function get(string $key): mixed
    {
        return $this->cacheRepository->get($key);
    }

    /**
     * @param string $key
     * @param array<mixed>|string $value
     */
    public function set(string $key, array|string $value): bool
    {
        return $this->cacheRepository->set($key, $value);
    }

    public function del(string $key): bool
    {
        return $this->cacheRepository->del($key);
    }

    public function pipeline(callable $callback): void
    {
        $this->cacheRepository->pipeline($callback);
    }
}
