<?php

namespace Tuto\Event\Contract;

interface EventInterface
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return bool
     */
    public function isPropagationStopped(): bool;

    /**
     * @return void
     */
    public function stopPropagation(): void;
}
