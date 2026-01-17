<?php

namespace Tuto\Template;

use RuntimeException;
use Tuto\Collections\Collection;
use Tuto\Template\Nodes\BlockNode;
use Tuto\Template\Nodes\ExtendsNode;
use Tuto\Template\Nodes\ForNode;
use Tuto\Template\Nodes\IfNode;
use Tuto\Template\Nodes\IncludeNode;
use Tuto\Template\Nodes\NodeInterface;
use Tuto\Template\Nodes\PrintNode;
use Tuto\Template\Nodes\TextNode;
use Tuto\Template\Tokens\BaseToken;
use Tuto\Template\Tokens\Token;

class Parser
{
    /** @var Collection<int, BaseToken> */
    private Collection $tokens;

    private int $position = 0;

    /**
     * @param Collection<int, BaseToken> $tokens
     * @return Collection<int, NodeInterface>
     */
    public function parse(Collection $tokens): Collection
    {
        $this->tokens = $tokens;
        $this->position = 0;

        return $this->parseNodes();
    }

    /**
     * @param string[] $stopAt
     * @return Collection<int, NodeInterface>
     */
    private function parseNodes(array $stopAt = []): Collection
    {
        $nodes = collect();

        while ($this->position < $this->tokens->count()) {
            $token = $this->tokens[$this->position];

            if ($token->type === Token::BLOCK_START) {
                $next = $this->tokens[$this->position + 1] ?? null;
                if ($next !== null && $next->type === Token::TEXT) {
                    $directive = trim($next->value);
                    foreach ($stopAt as $stop) {
                        if (str_starts_with($directive, $stop)) {
                            return $nodes;
                        }
                    }
                }
            }

            $node = $this->parseNode();
            if ($node !== null) {
                $nodes->push($node);
            }
        }

        return $nodes;
    }

    private function parseNode(): ?NodeInterface
    {
        $token = $this->current();

        return match ($token->type) {
            Token::TEXT => $this->parseText(),
            Token::VAR_START => $this->parseVariable(),
            Token::BLOCK_START => $this->parseDirective(),
            default => $this->advance() ? null : null,
        };
    }

    private function parseText(): TextNode
    {
        $token = $this->current();
        $this->advance();
        return new TextNode($token->value);
    }

    private function parseVariable(): PrintNode
    {
        $this->advance(); // Skip {{

        $expression = '';
        while (!$this->isAtEnd() && $this->current()->type !== Token::VAR_END) {
            $expression .= $this->current()->value;
            $this->advance();
        }

        $this->advance(); // Skip }}

        return new PrintNode(trim($expression));
    }

    private function parseDirective(): ?NodeInterface
    {
        $this->advance(); // Skip {%

        $content = '';
        while (!$this->isAtEnd() && $this->current()->type !== Token::BLOCK_END) {
            $content .= $this->current()->value;
            $this->advance();
        }

        $this->advance(); // Skip %}

        $content = trim($content);

        if (str_starts_with($content, 'if ')) {
            return $this->parseIf(substr($content, 3));
        }

        if (str_starts_with($content, 'for ')) {
            return $this->parseFor(substr($content, 4));
        }

        if (str_starts_with($content, 'block ')) {
            return $this->parseBlock(substr($content, 6));
        }

        if (str_starts_with($content, 'extends ')) {
            return $this->parseExtends(substr($content, 8));
        }

        if (str_starts_with($content, 'include ')) {
            return $this->parseInclude(substr($content, 8));
        }

        return null;
    }

    private function parseIf(string $condition): IfNode
    {
        $node = new IfNode();
        $body = $this->parseNodes(['elseif', 'else', 'endif']);
        $node->addBranch(trim($condition), $body->toArray());

        while (!$this->isAtEnd()) {
            $this->advance(); // Skip {%
            $content = trim($this->current()->value);
            $this->advance(); // Skip content
            $this->advance(); // Skip %}

            if (str_starts_with($content, 'elseif ')) {
                $elseifCondition = substr($content, 7);
                $elseifBody = $this->parseNodes(['elseif', 'else', 'endif']);
                $node->addBranch(trim($elseifCondition), $elseifBody->toArray());
            } elseif ($content === 'else') {
                $elseBody = $this->parseNodes(['endif']);
                $node->setElseBranch($elseBody->toArray());
            } elseif ($content === 'endif') {
                break;
            }
        }

        return $node;
    }

    private function parseFor(string $expression): ForNode
    {
        if (preg_match('/^(\w+)\s*,\s*(\w+)\s+in\s+(.+)$/', $expression, $matches)) {
            $node = new ForNode($matches[2], trim($matches[3]), $matches[1]);
        } elseif (preg_match('/^(\w+)\s+in\s+(.+)$/', $expression, $matches)) {
            $node = new ForNode($matches[1], trim($matches[2]));
        } else {
            throw new RuntimeException("Invalid for syntax: $expression");
        }

        $body = $this->parseNodes(['else', 'endfor']);
        $node->setBody($body->toArray());

        if (!$this->isAtEnd()) {
            $this->advance(); // Skip {%
            $content = trim($this->current()->value);
            $this->advance(); // Skip content
            $this->advance(); // Skip %}

            if ($content === 'else') {
                $elseBody = $this->parseNodes(['endfor']);
                $node->setElseBody($elseBody->toArray());

                // Skip endfor
                $this->advance(); // {%
                $this->advance(); // endfor
                $this->advance(); // %}
            }
        }

        return $node;
    }

    private function parseBlock(string $name): BlockNode
    {
        $node = new BlockNode(trim($name));
        $body = $this->parseNodes(['endblock']);
        $node->setBody($body->toArray());

        // Skip endblock
        $this->advance(); // {%
        $this->advance(); // endblock
        $this->advance(); // %}

        return $node;
    }

    private function parseExtends(string $template): ExtendsNode
    {
        $template = trim($template, " \t\n\r\0\x0B'\"");
        return new ExtendsNode($template);
    }

    private function parseInclude(string $expression): IncludeNode
    {
        if (preg_match('/^[\'"]([^\'"]+)[\'"](?:\s+with\s+(.+))?$/', $expression, $matches)) {
            return new IncludeNode($matches[1], $matches[2] ?? null);
        }

        throw new RuntimeException("Invalid include syntax: $expression");
    }

    private function current(): BaseToken
    {
        return $this->tokens[$this->position];
    }

    private function advance(): bool
    {
        if (!$this->isAtEnd()) {
            $this->position++;
            return true;
        }
        return false;
    }

    private function isAtEnd(): bool
    {
        return $this->position >= $this->tokens->count();
    }
}
