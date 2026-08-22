<?php

namespace App\Services\Infrastructure;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class LocalHeavyResourceLock
{
    public function enabled(): bool
    {
        return (bool) config(
            'security.local_heavy_resource_lock.enabled',
            true,
        );
    }

    public function acquire(): ?string
    {
        $token = (string) Str::uuid();

        $acquired = Redis::eval(<<<'LUA'
            if redis.call('exists', KEYS[1]) == 0 then
                redis.call('set', KEYS[1], ARGV[1], 'EX', ARGV[2])
                return 1
            end

            return 0
            LUA,
            1,
            $this->key(),
            $token,
            $this->timeout(),
        );

        return (int) $acquired === 1
            ? $token
            : null;
    }

    public function refresh(string $token): bool
    {
        $refreshed = Redis::eval(<<<'LUA'
            if redis.call('get', KEYS[1]) == ARGV[1] then
                return redis.call('expire', KEYS[1], ARGV[2])
            end

            return 0
            LUA,
            1,
            $this->key(),
            $token,
            $this->timeout(),
        );

        return (int) $refreshed === 1;
    }

    public function release(string $token): bool
    {
        $released = Redis::eval(<<<'LUA'
            if redis.call('get', KEYS[1]) == ARGV[1] then
                return redis.call('del', KEYS[1])
            end

            return 0
            LUA,
            1,
            $this->key(),
            $token,
        );

        return (int) $released === 1;
    }

    private function key(): string
    {
        return (string) config(
            'security.local_heavy_resource_lock.key',
            'rag:local:heavy-resource',
        );
    }

    private function timeout(): int
    {
        return (int) config(
            'security.local_heavy_resource_lock.timeout',
            600,
        );
    }
}
