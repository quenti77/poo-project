<?php

namespace Tuto\Template\Tokens;

class BlockEndToken extends BaseToken
{
    public const string TOKEN_PART = '%}';

    public function __construct()
    {
        parent::__construct(Token::BLOCK_END, self::TOKEN_PART);
    }
}
