<?php

namespace Tuto\Template\Tokens;

class Keywords
{
    /** @var array<string, TokenType> */
    public const array MAPPING = [
        'null' => TokenType::NULL,
        'true' => TokenType::BOOLEAN,
        'false' => TokenType::BOOLEAN,
        'set' => TokenType::LET,
        'if' => TokenType::IF,
        'else' => TokenType::ELSE,
        'elseif' => TokenType::ELSE_IF,
        'endif' => TokenType::END_IF,
        'for' => TokenType::FOR,
        'endfor' => TokenType::END_FOR,
        'in' => TokenType::IN,
        'not' => TokenType::UNARY_OPERATOR,
        'and' => TokenType::BINARY_OPERATOR,
        'or' => TokenType::BINARY_OPERATOR,
        'extends' => TokenType::EXTENDS,
        'block' => TokenType::BLOCK,
        'endblock' => TokenType::END_BLOCK,
        'include' => TokenType::INCLUDE,
        'with' => TokenType::WITH,
        'only' => TokenType::ONLY,
    ];
}
