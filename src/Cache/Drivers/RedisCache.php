<?php

namespace Tuto\Cache\Drivers;

use DateTimeInterface;
use RedisException;
use Tuto\Cache\CacheException;
use Tuto\Database\Redis\RedisConnection;
use Tuto\Utils\CurrentTime;

class RedisCache extends AbstractCache
{
    /**
     * @param RedisConnection $redis
     * @param CurrentTime $currentTime
     * @param string $prefix
     */
    public function __construct(
        private readonly RedisConnection $redis,
        private readonly CurrentTime $currentTime,
        private readonly string $prefix = 'cache',
    ) {
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        try {
            return $this->redis->exists($this->prefixKey($key)) > 0;
        } catch (RedisException $exception) {
            throw new CacheException("Failed to check cache key '{$key}': {$exception->getMessage()}", 0, $exception);
        }
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        try {
            $value = $this->redis->get($this->prefixKey($key));
            if ($value === false) {
                return $default;
            }
            return unserialize($value, ['allowed_classes' => true]);
        } catch (RedisException $exception) {
            throw new CacheException("Failed to get cache key '{$key}': {$exception->getMessage()}", 0, $exception);
        }
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param DateTimeInterface|int|null $ttl
     * @return bool
     */
    public function set(string $key, mixed $value, DateTimeInterface|int|null $ttl = null): bool
    {
        try {
            $serialized = serialize($value);
            $prefixedKey = $this->prefixKey($key);
            $ttlSeconds = $this->calculateTtlSeconds($ttl);

            return $ttlSeconds === null
                ? $this->redis->set($prefixedKey, $serialized)
                : $this->redis->setex($prefixedKey, $ttlSeconds, $serialized);
        } catch (RedisException $exception) {
            throw new CacheException("Failed to set cache key '{$key}': {$exception->getMessage()}", 0, $exception);
        }
    }

    /**
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        try {
            $keys = $this->redis->keys($this->prefixKey($key));
            return $this->redis->del($keys) > 0;
        } catch (RedisException $exception) {
            throw new CacheException("Failed to delete cache key '{$key}': {$exception->getMessage()}", 0, $exception);
        }
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        try {
            return $this->delete($this->prefixKey('*'));
        } catch (RedisException $exception) {
            throw new CacheException("Failed to clear cache: {$exception->getMessage()}", 0, $exception);
        }
    }

    /**
     * @param string $key
     * @param int $value
     * @return int|false
     */
    public function increment(string $key, int $value = 1): int|false
    {
        try {
            return $this->redis->incrBy($this->prefixKey($key), $value);
        } catch (RedisException $exception) {
            throw new CacheException("Failed to increment cache key '{$key}': {$exception->getMessage()}", 0, $exception);
        }
    }

    /**
     * @param string $key
     * @param int $value
     * @return int|false
     */
    public function decrement(string $key, int $value = 1): int|false
    {
        try {
            return $this->redis->decrBy($this->prefixKey($key), $value);
        } catch (RedisException $exception) {
            throw new CacheException("Failed to decrement cache key '{$key}': {$exception->getMessage()}", 0, $exception);
        }
    }

    /**
     * @param string $key
     * @return string
     */
    private function prefixKey(string $key): string
    {
        return "{$this->prefix}:{$key}";
    }

    private function calculateTtlSeconds(DateTimeInterface|int|null $ttl): int|null
    {
        if ($ttl === null) {
            return null;
        }

        if ($ttl instanceof DateTimeInterface) {
            $seconds = $ttl->getTimestamp() - $this->currentTime->now()->getTimestamp();
            $ttl = max(1, $seconds);
        }

        return $ttl;
    }
}
