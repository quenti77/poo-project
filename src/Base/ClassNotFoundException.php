<?php

namespace Tuto\Base;

use RuntimeException;
use Throwable;

class ClassNotFoundException extends RuntimeException
{
    /**
     * @param string $className
     * @param Throwable|null $previous
     */
    public function __construct(private readonly string $className, Throwable|null $previous = null)
    {
        parent::__construct("Class not found '{$this->className}'", 0, $previous);
    }

    public function getClassName(): string
    {
        return $this->className;
    }
}
