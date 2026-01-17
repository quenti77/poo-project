<?php

namespace Tuto\TemplateOld\Tokens;

class BlockStartToken extends BaseToken
{
    public const string TOKEN_PART = '{%';

    public function __construct()
    {
        parent::__construct(Token::BLOCK_START, self::TOKEN_PART);
    }
}
