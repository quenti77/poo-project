<?php

namespace Tuto\Template\Tokens;

use Tuto\Collections\Collection;
use Tuto\Utils\EasyEnum;

enum TokenType: string
{
    use EasyEnum;

    case TEXT = 'text';
    case EOF = 'eof';

    case VAR_START = 'var_start';
    case VAR_END = 'var_end';

    case BLOCK_START = 'block_start';
    case BLOCK_END = 'block_end';

    case NULL = 'null';
    case BOOLEAN = 'boolean';
    case NUMBER = 'number';
    case STRING = 'string';

    case IDENTIFIER = 'identifier';
    case OPEN_PARENTHESIS = 'open_parenthesis';
    case CLOSE_PARENTHESIS = 'close_parenthesis';
    case OPEN_BRACKETS = 'open_brackets';
    case CLOSE_BRACKETS = 'close_brackets';
    case OPEN_BRACES = 'open_braces';
    case CLOSE_BRACES = 'close_braces';

    case EQUALS = 'equals';
    case UNARY_OPERATOR = 'unary_operator';
    case BINARY_OPERATOR = 'binary_operator';

    case LET = 'let';
    case IF = 'if';
    case ELSE = 'else';
    case ELSE_IF = 'else_if';
    case END_IF = 'end_if';
    case FOR = 'for';
    case IN = 'in';
    case END_FOR = 'end_for';

    case EXTENDS = 'extends';
    case BLOCK = 'block';
    case END_BLOCK = 'end_block';
    case INCLUDE = 'include';
    case WITH = 'with';
    case ONLY = 'only';

    case COMMA = 'comma';
    case DOT = 'dot';
    case PIPE = 'pipe';
    case COLON = 'colon';
    case QUESTION_MARK = 'question_mark';

    /**
     * @param Collection<int, TokenType>|array<int, TokenType> $searches
     * @return bool
     */
    public function in(Collection|array $searches): bool
    {
        if ($searches instanceof Collection) {
            $searches = $searches->all();
        }

        return in_array($this, $searches, true);
    }
}
