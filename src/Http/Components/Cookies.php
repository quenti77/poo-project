<?php

namespace Tuto\Http\Components;

use DateTimeImmutable;
use Tuto\Collections\Collection;

/**
 * @implements Collection<string, Cookie>
 */
class Cookies extends Collection
{
    public static function fromGlobals(): self
    {
        $cookies = new self();

        foreach ($_COOKIE as $name => $value) {
            $cookies[$name] = $value;
        }

        return $cookies;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($value instanceof Cookie) {
            parent::offsetSet($offset, $value);
            return;
        }

        $value = (string) $value;
        if (!$this->offsetExists($offset)) {
            parent::offsetSet($offset, new Cookie($offset, $value, new DateTimeImmutable('+1 day')));
            return;
        }

        $cookie = $this[$offset];
        $cookie->value = $value;
        parent::offsetSet($offset, $cookie);
    }

    public function offsetUnset(mixed $offset): void
    {
        if (!$this->offsetExists($offset)) {
            return;
        }
        $this[$offset]->remove();
    }

    public function export(): void
    {
        if (headers_sent()) {
            return;
        }
        foreach ($this->items as $cookie) {
            $cookie->export();
        }
    }
}
