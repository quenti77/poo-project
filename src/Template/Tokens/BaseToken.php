<?php

namespace Tuto\Template\Tokens;

abstract class BaseToken
{
    /**
     * @param Token $type
     * @param string $value
     */
    public function __construct(
        public readonly Token $type,
        public readonly string $value,
    ) {
    }
}
