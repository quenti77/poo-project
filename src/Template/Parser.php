<?php

namespace Tuto\Template;

use InvalidArgumentException;
use Tuto\Collections\Collection;
use Tuto\Template\Nodes\ExpressionInterface;
use Tuto\Template\Nodes\Expressions\ArrayExpression;
use Tuto\Template\Nodes\Expressions\BinaryExpression;
use Tuto\Template\Nodes\Expressions\FilterExpression;
use Tuto\Template\Nodes\Expressions\FunctionCallExpression;
use Tuto\Template\Nodes\Expressions\IdentifierExpression;
use Tuto\Template\Nodes\Expressions\LiteralExpression;
use Tuto\Template\Nodes\Expressions\MemberAccessExpression;
use Tuto\Template\Nodes\Expressions\TernaryExpression;
use Tuto\Template\Nodes\Expressions\UnaryExpression;
use Tuto\Template\Nodes\NodeInterface;
use Tuto\Template\Nodes\Statements\BlockNode;
use Tuto\Template\Nodes\Statements\ExtendsNode;
use Tuto\Template\Nodes\Statements\ForNode;
use Tuto\Template\Nodes\Statements\IfNode;
use Tuto\Template\Nodes\Statements\IncludeNode;
use Tuto\Template\Nodes\Statements\PrintNode;
use Tuto\Template\Nodes\Statements\SetNode;
use Tuto\Template\Nodes\Statements\TextNode;
use Tuto\Template\Tokens\BaseToken;
use Tuto\Template\Tokens\TokenType;

class Parser
{
    /** @var Collection<int, BaseToken> $tokens */
    private Collection $tokens;

    /** @var array<string, int> */
    private const array PRECEDENCE = [
        'or' => 10,
        'and' => 20,
        'not' => 25,
        '==' => 30, '!=' => 30,
        '<' => 40, '>' => 40, '<=' => 40, '>=' => 40, 'in' => 40, 'not in' => 40,
        '+' => 50, '-' => 50, '~' => 50,
        '*' => 60, '/' => 60, '%' => 60,
        '-unary' => 70,
    ];

    /**
     * @param Collection<int, BaseToken> $tokens
     * @return Collection<int, NodeInterface>
     */
    public function parse(Collection $tokens): Collection
    {
        $this->tokens = $tokens;

        $nodes = collect();

        while ($this->isEOF() === false) {
            $node = $this->parseNode();
            if ($node !== null) {
                $nodes->push($node);
            }
        }

        return $nodes;
    }

    /**
     * @return NodeInterface|null
     */
    private function parseNode(): NodeInterface|null
    {
        return match ($this->peek()->type) {
            TokenType::TEXT => $this->parseText(),
            TokenType::VAR_START => $this->parsePrint(),
            TokenType::BLOCK_START => $this->parseBlock(),
            TokenType::EOF => null,
            default => throw new InvalidArgumentException("Unexpected token: {$this->peek()->type->value}"),
        };
    }

    /**
     * @return TextNode
     */
    private function parseText(): TextNode
    {
        return new TextNode($this->eat()->value);
    }

    /**
     * @return PrintNode
     */
    private function parsePrint(): PrintNode
    {
        $this->expect(TokenType::VAR_START);
        $expression = $this->parseExpression();
        $this->expect(TokenType::VAR_END);

        return new PrintNode($expression);
    }

    /**
     * @return NodeInterface
     */
    private function parseBlock(): NodeInterface
    {
        $this->expect(TokenType::BLOCK_START);

        return match ($this->peek()->type) {
            TokenType::LET => $this->parseSet(),
            TokenType::IF => $this->parseIf(),
            TokenType::FOR => $this->parseFor(),
            TokenType::EXTENDS => $this->parseExtends(),
            TokenType::BLOCK => $this->parseBlockDef(),
            TokenType::INCLUDE => $this->parseInclude(),
            default => throw new InvalidArgumentException("Unknown block type: {$this->peek()->type->value}"),
        };
    }

    /**
     * @return SetNode
     */
    private function parseSet(): SetNode
    {
        $this->expect(TokenType::LET);
        $name = $this->expect(TokenType::IDENTIFIER)->value;

        $this->expect(TokenType::EQUALS);
        $value = $this->parseExpression();

        $this->expect(TokenType::BLOCK_END);
        return new SetNode($name, $value);
    }

    /**
     * @return IfNode
     */
    private function parseIf(): IfNode
    {
        $this->expect(TokenType::IF);
        $condition = $this->parseExpression();
        $this->expect(TokenType::BLOCK_END);

        $body = $this->parseUntil(TokenType::END_IF, TokenType::ELSE, TokenType::ELSE_IF);
        $node = new IfNode($condition, $body);

        while ($this->isBlockType(TokenType::ELSE_IF)) {
            $this->expect(TokenType::BLOCK_START);
            $this->expect(TokenType::ELSE_IF);
            $elseIfCondition = $this->parseExpression();
            $this->expect(TokenType::BLOCK_END);

            $elseIfBody = $this->parseUntil(TokenType::END_IF, TokenType::ELSE, TokenType::ELSE_IF);
            $node->addElseIf($elseIfCondition, $elseIfBody);
        }

        /** @noinspection DuplicatedCode */
        if ($this->isBlockType(TokenType::ELSE)) {
            $this->expect(TokenType::BLOCK_START);
            $this->expect(TokenType::ELSE);
            $this->expect(TokenType::BLOCK_END);

            $elseBody = $this->parseUntil(TokenType::END_IF);
            $node->setElse($elseBody);
        }

        $this->expect(TokenType::BLOCK_START);
        $this->expect(TokenType::END_IF);
        $this->expect(TokenType::BLOCK_END);

        return $node;
    }

    /**
     * @return ForNode
     */
    private function parseFor(): ForNode
    {
        $this->expect(TokenType::FOR);

        $keyVar = null;
        $valueVar = $this->expect(TokenType::IDENTIFIER)->value;

        if ($this->match(TokenType::COMMA)) {
            $keyVar = $valueVar;
            $valueVar = $this->expect(TokenType::IDENTIFIER)->value;
        }

        $this->expect(TokenType::IN);
        $iterable = $this->parseExpression();
        $this->expect(TokenType::BLOCK_END);

        $body = $this->parseUntil(TokenType::END_FOR, TokenType::ELSE);
        $node = new ForNode($keyVar, $valueVar, $iterable, $body);

        /** @noinspection DuplicatedCode */
        if ($this->isBlockType(TokenType::ELSE)) {
            $this->expect(TokenType::BLOCK_START);
            $this->expect(TokenType::ELSE);
            $this->expect(TokenType::BLOCK_END);

            $elseBody = $this->parseUntil(TokenType::END_FOR);
            $node->setElse($elseBody);
        }

        $this->expect(TokenType::BLOCK_START);
        $this->expect(TokenType::END_FOR);
        $this->expect(TokenType::BLOCK_END);

        return $node;
    }

    /**
     * @return ExtendsNode
     */
    private function parseExtends(): ExtendsNode
    {
        $this->expect(TokenType::EXTENDS);
        $template = $this->parseStringValue();
        $this->expect(TokenType::BLOCK_END);

        return new ExtendsNode($template);
    }

    /**
     * @return BlockNode
     */
    private function parseBlockDef(): BlockNode
    {
        $this->expect(TokenType::BLOCK);
        $name = $this->expect(TokenType::IDENTIFIER)->value;
        $this->expect(TokenType::BLOCK_END);

        $body = $this->parseUntil(TokenType::END_BLOCK);

        $this->expect(TokenType::BLOCK_START);
        $this->expect(TokenType::END_BLOCK);
        $this->expect(TokenType::BLOCK_END);

        return new BlockNode($name, $body);
    }

    /**
     * @return IncludeNode
     */
    private function parseInclude(): IncludeNode
    {
        $this->expect(TokenType::INCLUDE);
        $template = $this->parseStringValue();

        $variables = null;
        $withContext = true;

        if ($this->match(TokenType::WITH)) {
            $variables = $this->parseHashVariables(TokenType::OPEN_BRACES, TokenType::CLOSE_BRACES);
        }
        if ($this->match(TokenType::ONLY)) {
            $withContext = false;
        }

        $this->expect(TokenType::BLOCK_END);
        return new IncludeNode($template, $variables, $withContext);
    }

    /**
     * @param TokenType ...$endTypes
     * @return NodeInterface[]
     */
    private function parseUntil(TokenType ...$endTypes): array
    {
        $nodes = [];
        while ($this->isEOF() === false && $this->isBlockType(...$endTypes) === false) {
            $node = $this->parseNode();
            if ($node !== null) {
                $nodes[] = $node;
            }
        }

        return $nodes;
    }

    /**
     * @param TokenType ...$types
     * @return bool
     */
    private function isBlockType(TokenType ...$types): bool
    {
        if (!$this->is(TokenType::BLOCK_START)) {
            return false;
        }

        return $this->peekNext()->type->in($types);
    }

    /**
     * @param int $minPrecedence
     * @return ExpressionInterface
     */
    private function parseExpression(int $minPrecedence = 0): ExpressionInterface
    {
        $left = $this->parsePrimary();
        $left = $this->parsePostfixExpression($left);

        while (true) {
            $op = $this->peekOperator();
            if ($op === null) {
                break;
            }

            $precedence = self::PRECEDENCE[$op] ?? 0;
            if ($precedence < $minPrecedence) {
                break;
            }

            $this->eat();
            if ($op === 'not in') {
                $this->eat();
            }

            if ($op === '?') {
                $left = $this->parseTernary($left);
                continue;
            }

            $right = $this->parseExpression($precedence + 1);
            $left = new BinaryExpression($left, $op, $right);
        }

        return $left;
    }

    /**
     * @return ExpressionInterface
     */
    private function parsePrimary(): ExpressionInterface
    {
        $token = $this->peek();
        return match ($token->type) {
            TokenType::NUMBER => $this->parseNumber(),
            TokenType::STRING => $this->parseString(),
            TokenType::BOOLEAN => $this->parseBoolean(),
            TokenType::NULL => $this->parseNull(),
            TokenType::IDENTIFIER => $this->parseIdentifierOrFunction(),
            TokenType::OPEN_PARENTHESIS => $this->parseGrouped(),
            TokenType::OPEN_BRACKETS => $this->parseArray(),
            TokenType::OPEN_BRACES => $this->parseHash(),
            TokenType::UNARY_OPERATOR => $this->parseUnary(),
            TokenType::DOT => throw new InvalidArgumentException(
                "Unexpected dot at start of expression. Property access requires an object before the dot."
            ),
            default => throw new InvalidArgumentException("Unexpected token in expression: {$token->type->value}"),
        };
    }

    /**
     * @param ExpressionInterface $expression
     * @return ExpressionInterface
     */
    private function parsePostfixExpression(ExpressionInterface $expression): ExpressionInterface
    {
        while (true) {
            if ($this->match(TokenType::DOT)) {
                $property = $this->expect(TokenType::IDENTIFIER)->value;
                $expression = new MemberAccessExpression($expression, $property);
            } elseif ($this->match(TokenType::OPEN_BRACKETS)) {
                $key = $this->parseExpression();
                $this->expect(TokenType::CLOSE_BRACKETS);
                $expression = new MemberAccessExpression($expression, $key);
            } elseif ($this->match(TokenType::PIPE)) {
                $filterName = $this->expect(TokenType::IDENTIFIER)->value;
                $args = $this->is(TokenType::OPEN_PARENTHESIS)
                    ? $this->parseArguments()
                    : [];
                $expression = new FilterExpression($expression, $filterName, $args);
            } else {
                break;
            }
        }

        return $expression;
    }

    /**
     * @return LiteralExpression
     */
    private function parseNumber(): LiteralExpression
    {
        $token = $this->eat() ?? throw new InvalidArgumentException("Expected number, got EOF");
        $value = str_contains($token->value, '.')
            ? (float)$token->value
            : (int)$token->value;

        return new LiteralExpression($value);
    }

    /**
     * @return LiteralExpression
     */
    private function parseString(): LiteralExpression
    {
        $token = $this->eat() ?? throw new InvalidArgumentException("Expected string, got EOF");
        $value = substr($token->value, 1, -1);

        return new LiteralExpression($value);
    }

    /**
     * @return string
     */
    private function parseStringValue(): string
    {
        $token = $this->expect(TokenType::STRING);
        return substr($token->value, 1, -1);
    }

    /**
     * @return LiteralExpression
     */
    private function parseBoolean(): LiteralExpression
    {
        $token = $this->eat() ?? throw new InvalidArgumentException("Expected boolean, got EOF");
        return new LiteralExpression($token->value === 'true');
    }

    /**
     * @return LiteralExpression
     */
    private function parseNull(): LiteralExpression
    {
        $this->eat() ?? throw new InvalidArgumentException("Expected null, got EOF");
        return new LiteralExpression(null);
    }

    /**
     * @return ExpressionInterface
     */
    private function parseIdentifierOrFunction(): ExpressionInterface
    {
        $name = $this->eat()->value;

        if ($this->is(TokenType::OPEN_PARENTHESIS)) {
            $args = $this->parseArguments();
            return new FunctionCallExpression($name, $args);
        }

        return new IdentifierExpression($name);
    }

    /**
     * @return UnaryExpression
     */
    private function parseUnary(): UnaryExpression
    {
        $op = $this->eat()->value;

        $precedence = match ($op) {
            'not' => self::PRECEDENCE['not'],
            '-' => self::PRECEDENCE['-unary'],
            default => 70,
        };

        $operand = $this->parseExpression($precedence);
        return new UnaryExpression($op, $operand);
    }

    /**
     * @return ExpressionInterface
     */
    private function parseGrouped(): ExpressionInterface
    {
        $this->expect(TokenType::OPEN_PARENTHESIS);
        $expression = $this->parseExpression();
        $this->expect(TokenType::CLOSE_PARENTHESIS);

        return $expression;
    }

    /**
     * @param ExpressionInterface $condition
     * @return TernaryExpression
     */
    private function parseTernary(ExpressionInterface $condition): TernaryExpression
    {
        $trueBranch = null;
        if (!$this->is(TokenType::COLON)) {
            $trueBranch = $this->parseExpression();
        }

        $this->expect(TokenType::COLON);
        $falseBranch = $this->parseExpression();

        return new TernaryExpression($condition, $trueBranch, $falseBranch);
    }

    /**
     * @return ExpressionInterface[]
     */
    private function parseArguments(): array
    {
        $this->expect(TokenType::OPEN_PARENTHESIS);
        $args = [];

        if (!$this->is(TokenType::CLOSE_PARENTHESIS)) {
            $args = $this->parseCommaArgs();
        }

        $this->expect(TokenType::CLOSE_PARENTHESIS);
        return $args;
    }

    /**
     * @return ArrayExpression
     */
    private function parseArray(): ArrayExpression
    {
        $this->expect(TokenType::OPEN_BRACKETS);
        $elements = [];

        if (!$this->is(TokenType::CLOSE_BRACKETS)) {
            $elements = $this->parseCommaArgs();
        }

        $this->expect(TokenType::CLOSE_BRACKETS);
        return new ArrayExpression($elements, false);
    }

    /**
     * @return ArrayExpression
     */
    private function parseHash(): ArrayExpression
    {
        $elements = $this->parseHashVariables(TokenType::OPEN_BRACES, TokenType::CLOSE_BRACES);
        return new ArrayExpression($elements, true);
    }

    /**
     * @param TokenType $open
     * @param TokenType $close
     * @return array<string, ExpressionInterface>
     */
    private function parseHashVariables(TokenType $open, TokenType $close): array
    {
        $this->expect($open);
        $variables = [];

        if (!$this->is($close)) {
            do {
                $key = $this->is(TokenType::STRING)
                    ? $this->parseStringValue()
                    : $this->expect(TokenType::IDENTIFIER)->value;
                $this->expect(TokenType::COLON);
                $variables[$key] = $this->parseExpression();
            } while ($this->match(TokenType::COMMA));
        }

        $this->expect($close);
        return $variables;
    }

    /**
     * @return ExpressionInterface[]
     */
    private function parseCommaArgs(): array
    {
        $args = [];

        do {
            $args[] = $this->parseExpression();
        } while ($this->match(TokenType::COMMA));

        return $args;
    }

    /**
     * @return string|null
     */
    private function peekOperator(): string|null
    {
        if ($this->isEOF()) {
            return null;
        }

        $token = $this->peek();
        if ($token->type === TokenType::BINARY_OPERATOR) {
            return $token->value;
        }
        if ($token->type === TokenType::QUESTION_MARK) {
            return '?';
        }
        if ($token->type === TokenType::IN) {
            return 'in';
        }
        if (
            $token->type === TokenType::UNARY_OPERATOR &&
            $token->value === 'not' &&
            $this->peekNext()->type === TokenType::IN
        ) {
            return 'not in';
        }

        return null;
    }

    /**
     * @return BaseToken
     */
    private function peek(): BaseToken
    {
        return $this->tokens->first() ?? new BaseToken(TokenType::EOF, 'eof');
    }

    /**
     * @return BaseToken
     */
    private function peekNext(): BaseToken
    {
        return $this->tokens->get(1, new BaseToken(TokenType::EOF, 'eof'));
    }

    /**
     * @return BaseToken|null
     */
    private function eat(): BaseToken|null
    {
        return $this->tokens->shift();
    }

    /**
     * @param TokenType $type
     * @return BaseToken
     */
    private function expect(TokenType $type): BaseToken
    {
        $token = $this->peek();
        if ($token->type !== $type) {
            throw new InvalidArgumentException("Expected {$type->value}, got {$token->type->value}");
        }

        return $this->eat();
    }

    /**
     * @param TokenType $type
     * @return bool
     */
    private function is(TokenType $type): bool
    {
        return $this->peek()->type === $type;
    }

    /**
     * @param TokenType $type
     * @return bool
     */
    private function match(TokenType $type): bool
    {
        if ($this->is($type)) {
            $this->eat();
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    private function isEOF(): bool
    {
        return $this->tokens->isEmpty() || $this->is(TokenType::EOF);
    }
}
