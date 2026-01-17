<?php

namespace Tuto\Template\Tokens;

class VarEndToken extends BaseToken
{
    public const string TOKEN_PART = '}}';

    public function __construct()
    {
        parent::__construct(Token::VAR_END, self::TOKEN_PART);
    }
}
