<?php

namespace Tuto\TemplateOld\Tokens;

class TextToken extends BaseToken
{
    /**
     * @param string $value
     */
    public function __construct(string $value)
    {
        parent::__construct(Token::TEXT, $value);
    }
}
