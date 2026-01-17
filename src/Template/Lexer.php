<?php

namespace Tuto\Template;

use Tuto\Collections\Collection;
use Tuto\Template\Tokens\BaseToken;
use Tuto\Template\Tokens\BlockEndToken;
use Tuto\Template\Tokens\BlockStartToken;
use Tuto\Template\Tokens\TextToken;
use Tuto\Template\Tokens\VarEndToken;
use Tuto\Template\Tokens\VarStartToken;

class Lexer
{
    /**
     * @param string $source
     * @return Collection<int, BaseToken>
     */
    public function tokenize(string $source): Collection
    {
        $tokens = collect();

        $pattern = sprintf(
            '/(%s|%s|%s|%s|{#.*?#})/s',
            VarStartToken::TOKEN_PART,
            VarEndToken::TOKEN_PART,
            BlockStartToken::TOKEN_PART,
            BlockEndToken::TOKEN_PART,
        );

        $parts = preg_split($pattern, $source, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        foreach ($parts as $part) {
            $token = match ($part) {
                VarStartToken::TOKEN_PART => new VarStartToken(),
                VarEndToken::TOKEN_PART => new VarEndToken(),
                BlockStartToken::TOKEN_PART => new BlockStartToken(),
                BlockEndToken::TOKEN_PART => new BlockEndToken(),
                default => $this->parseDefault($part),
            };

            if ($token !== null) {
                $tokens->push($token);
            }
        }

        return $tokens;
    }

    /**
     * @param string $part
     * @return TextToken|null
     */
    private function parseDefault(string $part): TextToken|null
    {
        if (str_starts_with($part, '{#')) {
            return null;
        }

        return new TextToken($part);
    }
}
