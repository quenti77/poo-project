<?php

namespace Tuto\Template\Tokens;

class BaseToken
{
    /**
     * @param TokenType $type
     * @param string $value
     */
    public function __construct(
        public readonly TokenType $type,
        public readonly string $value,
    ) {
    }
}
