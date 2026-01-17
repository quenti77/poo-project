<?php

namespace Tuto\Template\Tokens;

class BlockStartToken extends BaseToken
{
    public const string TOKEN_PART = '{%';

    public function __construct()
    {
        parent::__construct(Token::BLOCK_START, self::TOKEN_PART);
    }
}
