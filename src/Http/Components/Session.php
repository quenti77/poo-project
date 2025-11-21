<?php

namespace Tuto\Http\Components;

use Tuto\Collection\Collection;

class Session extends Collection
{
    public static function fromGlobals(): self
    {
        self::assertSessionRunning();
        return new self($_SESSION);
    }

    public static function assertSessionRunning(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function offsetExists(mixed $offset): bool
    {
        self::assertSessionRunning();
        return parent::offsetExists($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        self::assertSessionRunning();
        return parent::offsetGet($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        self::assertSessionRunning();

        $_SESSION[$offset] = $value;
        parent::offsetSet($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        self::assertSessionRunning();

        unset($_SESSION[$offset]);
        parent::offsetUnset($offset);
    }
}