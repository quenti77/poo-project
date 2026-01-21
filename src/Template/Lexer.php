<?php

namespace Tuto\Template;

use InvalidArgumentException;
use Tuto\Collections\Collection;
use Tuto\Template\Tokens\BaseToken;
use Tuto\Template\Tokens\BinaryOperator;
use Tuto\Template\Tokens\Keywords;
use Tuto\Template\Tokens\TokenType;

class Lexer
{
    /** @var Collection<int, string> $parts */
    private Collection $parts;

    /** @var Collection<int, BaseToken> $tokens */
    private Collection $tokens;

    /**
     * @param string $varStart
     * @param string $varEnd
     * @param string $blockStart
     * @param string $blockEnd
     * @param string $commentStart
     * @param string $commentEnd
     */
    public function __construct(
        private readonly string $varStart = '{{',
        private readonly string $varEnd = '}}',
        private readonly string $blockStart = '{%',
        private readonly string $blockEnd = '%}',
        private readonly string $commentStart = '{#',
        private readonly string $commentEnd = '#}',
    ) {
        $this->parts = collect();
        $this->tokens = collect();
    }

    /**
     * @param string $source
     * @return Collection
     */
    public function tokenize(string $source): Collection
    {
        $this->parts = $this->getPattern()
            |> (static fn (string $p) => preg_split($p, $source, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY))
            |> collect(...);
        $this->tokens = collect();

        while ($this->isEOF() === false) {
            $part = $this->eat();
            if ($part === null) {
                $this->pushBaseToken(TokenType::EOF, 'eof');
                break;
            }

            if ($part === $this->varStart) {
                $this->pushBaseToken(TokenType::VAR_START, $part);
                $this->tokenizeBlock(new BaseToken(TokenType::VAR_END, $this->varEnd));
            } elseif ($part === $this->blockStart) {
                $this->pushBaseToken(TokenType::BLOCK_START, $part);
                $this->tokenizeBlock(new BaseToken(TokenType::BLOCK_END, $this->blockEnd));
            } else {
                $this->tokenizeOther($part);
            }
        }

        return $this->tokens;
    }

    /**
     * @return string
     */
    private function getPattern(): string
    {
        return sprintf(
            '/(%s|%s|%s|%s|%s.*?%s)/s',
            $this->varStart,
            $this->varEnd,
            $this->blockStart,
            $this->blockEnd,
            $this->commentStart,
            $this->commentEnd,
        );
    }

    /**
     * @param BaseToken $closeToken
     * @return void
     */
    private function tokenizeBlock(BaseToken $closeToken): void
    {
        while ($this->isEOF() === false) {
            $part = $this->eat();
            if ($part === $closeToken->value) {
                $this->tokens->push($closeToken);
                break;
            }

            $this->tokenizeExpression(trim($part));
        }
    }

    /**
     * @param string $part
     * @return void
     */
    private function tokenizeExpression(string $part): void
    {
        $chars = collect(mb_str_split($part));

        while ($chars->isEmpty() === false) {
            $current = $chars->shift() ?? '';

            if ($this->processOperator($current, $chars)) {
                $this->pushBaseToken(TokenType::BINARY_OPERATOR, $current);
            } elseif ($current === '-') {
                $this->pushBaseToken(TokenType::UNARY_OPERATOR, $current);
            } elseif ($current === '(') {
                $this->pushBaseToken(TokenType::OPEN_PARENTHESIS, $current);
            } elseif ($current === ')') {
                $this->pushBaseToken(TokenType::CLOSE_PARENTHESIS, $current);
            } elseif ($current === '[') {
                $this->pushBaseToken(TokenType::OPEN_BRACKETS, $current);
            } elseif ($current === ']') {
                $this->pushBaseToken(TokenType::CLOSE_BRACKETS, $current);
            } elseif ($current === '{') {
                $this->pushBaseToken(TokenType::OPEN_BRACES, $current);
            } elseif ($current === '}') {
                $this->pushBaseToken(TokenType::CLOSE_BRACES, $current);
            } elseif ($current === ',') {
                $this->pushBaseToken(TokenType::COMMA, $current);
            } elseif ($current === '|') {
                $this->pushBaseToken(TokenType::PIPE, $current);
            } elseif ($current === ':') {
                $this->pushBaseToken(TokenType::COLON, $current);
            } elseif ($current === '?') {
                $this->pushBaseToken(TokenType::QUESTION_MARK, $current);
            } elseif ($this->isStartLogicalOperator($current)) {
                $op = $current;
                if ($chars->get(0, '') === '=') {
                    $op .= $chars->shift();
                }
                $this->pushBaseToken($op === '=' ? TokenType::EQUALS : TokenType::BINARY_OPERATOR, $op);
            } elseif ($current === '.') {
                $next = $chars->get(0, '');
                if ($this->isNumber($next)) {
                    $this->processNumber($current, $chars);
                } else {
                    $this->pushBaseToken(TokenType::DOT, $current);
                }
            } elseif ($this->isNumber($current)) {
                $this->processNumber($current, $chars);
            } elseif (in_array($current, ['"', "'"], true)) {
                $this->processString($current, $chars);
            } elseif ($this->isIdentifier($current, false)) {
                $token = $current;
                while ($this->isIdentifier($chars->get(0, ''), true)) {
                    $token .= $chars->shift();
                }

                $tokenType = Keywords::MAPPING[$token] ?? TokenType::IDENTIFIER;
                $this->pushBaseToken($tokenType, $token);
            }
        }
    }

    /**
     * @param string $current
     * @param Collection<int, string> $chars
     * @return bool
     */
    private function processOperator(string $current, Collection $chars): bool
    {
        if ($this->isStartLogicalOperator($current)) {
            return false;
        }
        if (!BinaryOperator::values()->has($current)) {
            return false;
        }

        $second = $chars->get(0, '');

        /** @noinspection NotOptimalRegularExpressionsInspection */
        return ($current !== '-' || preg_match('/[a-zA-Z0-9_]|\(|\)/', $second) === false);
    }

    /**
     * @param string $char
     * @return bool
     */
    private function isStartLogicalOperator(string $char): bool
    {
        return in_array($char, ['=', '!', '<', '>'], true);
    }

    /**
     * @param string $current
     * @param Collection $chars
     * @return void
     */
    private function processNumber(string $current, Collection $chars): void
    {
        $hasDot = $current === '.';

        while ($chars->isEmpty() === false && $this->isNumberPart($chars->get(0, ''))) {
            $next = $chars->shift();
            if ($next === '.') {
                if ($hasDot) {
                    break;
                }
                $hasDot = true;
            }
            $current .= $next;
        }

        $this->pushBaseToken(TokenType::NUMBER, $current);
    }

    /**
     * @param string $char
     * @return bool
     */
    private function isNumberPart(string $char): bool
    {
        return $this->isNumber($char) || $char === '.';
    }

    /**
     * @param string|null $char
     * @return bool
     */
    private function isNumber(string|null $char): bool
    {
        if ($char === null) {
            return false;
        }
        return mb_ord($char) >= 48 && mb_ord($char) <= 57;
    }

    /**
     * @param string $stop
     * @param Collection<int, string> $chars
     * @return void
     */
    private function processString(string $stop, Collection $chars): void
    {
        $value = $stop;

        while ($chars->isEmpty() === false && $chars->get(0, '') !== $stop) {
            $current = $chars->shift();
            if ($current === '\\' && $chars->get(0, '') === $stop) {
                $value .= '\\' . $stop;
                $chars->shift();
            } else {
                $value .= $current;
            }
        }

        if ($chars->isEmpty()) {
            throw new InvalidArgumentException("String not terminated. Current string {$value}");
        }

        $value .= $chars->shift();
        $this->pushBaseToken(TokenType::STRING, $value);
    }

    /**
     * @param string $char
     * @param bool $withNumber
     * @return bool
     */
    private function isIdentifier(string $char, bool $withNumber): bool
    {
        /** @noinspection NotOptimalRegularExpressionsInspection */
        return $withNumber
            ? preg_match('/[a-zA-Z0-9_]/', $char)
            : preg_match('/[a-zA-Z_]/', $char);
    }

    /**
     * @param string $part
     * @return void
     */
    private function tokenizeOther(string $part): void
    {
        if (str_starts_with($part, $this->commentStart) && str_ends_with($part, $this->commentEnd)) {
            return;
        }
        $this->pushBaseToken(TokenType::TEXT, $part);
    }

    /**
     * @param TokenType $type
     * @param string $value
     * @return void
     */
    private function pushBaseToken(TokenType $type, string $value): void
    {
        $this->tokens->push(new BaseToken($type, $value));
    }

    /**
     * @return bool
     */
    private function isEOF(): bool
    {
        return $this->parts->isEmpty();
    }

    /**
     * @return string|null
     */
    private function eat(): string|null
    {
        return $this->isEOF() ? null : $this->parts->shift();
    }
}
