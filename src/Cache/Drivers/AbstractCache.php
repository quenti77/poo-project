<?php

namespace Tuto\Cache\Drivers;

use DateTimeInterface;
use Tuto\Cache\CacheInterface;

abstract class AbstractCache implements CacheInterface
{
    /**
     * @param string $key
     * @param DateTimeInterface|int|null $ttl
     * @param callable(): mixed $callback
     * @return mixed
     */
    public function remember(string $key, DateTimeInterface|int|null $ttl, callable $callback): mixed
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }
}
