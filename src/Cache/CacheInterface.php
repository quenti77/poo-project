<?php

namespace Tuto\Cache;

use DateTimeInterface;

interface CacheInterface
{
    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * @param string $key
     * @param mixed $value
     * @param DateTimeInterface|int|null $ttl
     * @return bool
     */
    public function set(string $key, mixed $value, DateTimeInterface|int|null $ttl = null): bool;

    /**
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool;

    /**
     * @return bool
     */
    public function clear(): bool;

    /**
     * @param string $key
     * @param DateTimeInterface|int|null $ttl
     * @param callable(): mixed $callback
     * @return mixed
     */
    public function remember(string $key, DateTimeInterface|int|null $ttl, callable $callback): mixed;

    /**
     * @param string $key
     * @param int $value
     * @return int|false
     */
    public function increment(string $key, int $value = 1): int|false;

    /**
     * @param string $key
     * @param int $value
     * @return int|false
     */
    public function decrement(string $key, int $value = 1): int|false;
}
