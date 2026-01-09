<?php

namespace Tuto\Event;

use Tuto\Event\Contract\EventInterface;

class Event implements EventInterface
{
    /** @var bool $propagationStopped */
    private bool $propagationStopped = false;

    /**
     * @return string
     */
    public function getName(): string
    {
        return static::class;
    }

    /**
     * @return bool
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    /**
     * @return void
     */
    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}
