<?php

namespace Tuto\TemplateOld\Tokens;

class VarStartToken extends BaseToken
{
    public const string TOKEN_PART = '{{';

    public function __construct()
    {
        parent::__construct(Token::VAR_START, self::TOKEN_PART);
    }
}
