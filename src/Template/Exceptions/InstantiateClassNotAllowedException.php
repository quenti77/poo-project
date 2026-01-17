<?php

namespace Tuto\Template\Exceptions;

use InvalidArgumentException;

class InstantiateClassNotAllowedException extends InvalidArgumentException
{
    public function __construct(
        public readonly string $className,
        public readonly array $allowedClasses,
    ) {
        parent::__construct("Class '{$this->className}' is not allowed for instantiation.");
    }
}
