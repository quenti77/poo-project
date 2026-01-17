<?php

namespace Tuto\Template;

use InvalidArgumentException;
use ReflectionMethod;
use ReflectionProperty;
use Tuto\Collections\Collection;
use Tuto\Template\Exceptions\InstantiateClassNotAllowedException;

class ExpressionEvaluator
{

    /**
     * @param FilterRegistry $filters
     * @param array $allowedClasses
     */
    public function __construct(
        private readonly FilterRegistry $filters,
        private array $allowedClasses = [],
    ) {
    }

    /**
     * @param string $alias
     * @param string $className
     * @return void
     */
    public function addAllowedClass(string $alias, string $className): void
    {
        $this->allowedClasses[$alias] = $className;
    }

    /**
     * @param string $expression
     * @param array $context
     * @return mixed
     */
    public function evaluate(string $expression, array $context): mixed
    {
        $expression = trim($expression);

        $filters = $this->splitByPipe($expression);
        $mainExpression = $filters->shift();

        $value = $this->evaluateExpression($mainExpression, $context);
        foreach ($filters as $filter) {
            $value = $this->applyFilter($filter, $value, $context);
        }

        return $value;
    }

    /**
     * @param string $expression
     * @return Collection<int, string>
     */
    private function splitByPipe(string $expression): Collection
    {
        $parts = collect();

        $current = '';
        $depth = 0;
        $inString = false;
        $stringChar = '';

        $lastChar = null;
        $chars = mb_str_split($expression);
        foreach ($chars as $char) {
            $isCharString = $char === '"' || $char === "'";
            if (!$inString && $isCharString) {
                $inString = true;
                $stringChar = $char;
            } elseif ($inString && $char === $stringChar && ($lastChar ?? '') !== '\\') {
                $inString = false;
                $stringChar = '';
            } elseif (!$inString) {
                if ($char === '(' || $char === '[') {
                    $depth += 1;
                } elseif ($char === ')' || $char === ']') {
                    $depth -= 1;
                } elseif ($char === '|' && $depth === 0) {
                    $parts->push(trim($current));
                    $current = '';
                    continue;
                }
            }

            $current .= $char;
            $lastChar = $char;
        }

        $current = trim($current);
        if ($current !== '') {
            $parts->push($current);
        }

        return $parts;
    }

    /**
     * @param string $expression
     * @param array $context
     * @return mixed
     */
    private function evaluateExpression(string $expression, array $context): mixed
    {
        $expression = trim($expression);

        // String literal
        if (preg_match('/^(["\'])(.*)\\1$/', $expression, $matches)) {
            return $matches[2];
        }

        // Number
        if (is_numeric($expression)) {
            return str_contains($expression, '.') ? (float) $expression : (int) $expression;
        }

        if ($expression === 'true') {
            return true;
        }
        if ($expression === 'false') {
            return false;
        }
        if ($expression === 'null') {
            return null;
        }

        if (str_starts_with($expression, 'new')) {
            return $this->instantiate($expression, $context);
        }

        if ((str_starts_with($expression, '[') && str_ends_with($expression, ']'))
            || (str_starts_with($expression, '{') && str_ends_with($expression, '}'))) {
            return $this->parseArrayLiteral($expression, $context);
        }

        /** @noinspection NotOptimalRegularExpressionsInspection */
        if (preg_match('/^([A-Z][a-zA-Z0-9_]*)::(\w+)$/', $expression, $matches)) {
            return $this->resolveConstant($matches[1], $matches[2], $context);
        }

        if ($this->isArithmeticExpression($expression)) {
            return $this->evaluateArithmetic($expression, $context);
        }

        if (str_contains($expression, '?') && str_contains($expression, ':')) {
            return $this->evaluateTernary($expression, $context);
        }

        return $this->resolveVariable($expression, $context);
    }

    private function applyFilter(string $filterExpr, mixed $value, array $context): mixed
    {
        if (preg_match('/^(\w+)(?:\((.*)\))?$/', $filterExpr, $matches)) {
            $filterName = $matches[1];
            $args = isset($matches[2]) && $matches[2] !== ''
                ? $this->parseArguments($matches[2], $context)
                : [];

            return $this->filters->apply($filterName, $value, $args);
        }

        throw new InvalidArgumentException("Invalid filter syntax: {$filterExpr}");
    }



    /**
     * @param string $expression
     * @param array $context
     * @return object
     */
    private function instantiate(string $expression, array $context): object
    {
        if (!preg_match('/^new\s+(\w+)\s*(?:\((.*)\))?$/', $expression, $matches)) {
            throw new InvalidArgumentException("Invalid instantiation syntax: $expression");
        }

        $className = $matches[1];
        $argsString = $matches[2] ?? '';

        if (!array_key_exists($className, $this->allowedClasses)) {
            throw new InstantiateClassNotAllowedException($className, $this->allowedClasses);
        }

        $fullClassName = $this->allowedClasses[$className];
        $args = $this->parseArguments($argsString, $context);

        return new $fullClassName(...$args);
    }

    /**
     * @param string $expression
     * @param array $context
     * @return array
     */
    private function parseArrayLiteral(string $expression, array $context): array
    {
        $inner = mb_substr($expression, 1, -1) |> trim(...);
        if ($inner === '') {
            return [];
        }

        $result = [];
        $elements = $this->splitArrayElements($inner);
        foreach ($elements as $element) {
            $element = trim($element);

            // Support both => (PHP) and : (Twig) syntax for key-value pairs
            if (preg_match('/^([\'"]?)(\w+)\\1\s*(?:=>|:)\s*(.+)$/', $element, $matches)) {
                $key = $matches[2];
                $value = $this->evaluateExpression(trim($matches[3]), $context);
                $result[$key] = $value;
            } else {
                $result[] = $this->evaluateExpression($element, $context);
            }
        }

        return $result;
    }

    /**
     * @param string $inner
     * @return Collection<int, string>
     */
    private function splitArrayElements(string $inner): Collection
    {
        $elements = collect();

        $current = '';
        $depth = 0;
        $inString = false;
        $stringChar = '';

        $lastChar = null;
        $chars = mb_str_split($inner);
        foreach ($chars as $char) {
            $isCharString = $char === '"' || $char === "'";
            if (!$inString && $isCharString) {
                $inString = true;
                $stringChar = $char;
                $current .= $char;
            } elseif ($inString && $char === $stringChar && ($lastChar ?? '') !== '\\') {
                $inString = false;
                $stringChar = '';
                $current .= $char;
            } elseif (!$inString) {
                if ($char === '[' || $char === '(' || $char === '{') {
                    $depth += 1;
                    $current .= $char;
                } elseif ($char === ']' || $char === ')' || $char === '}') {
                    $depth -= 1;
                    $current .= $char;
                } elseif ($char === ',' && $depth === 0) {
                    $elements->push(trim($current));
                    $current = '';
                } else {
                    $current .= $char;
                }
            } else {
                $current .= $char;
            }

            $lastChar = $char;
        }

        $current = trim($current);
        if ($current !== '') {
            $elements->push($current);
        }

        return $elements;
    }

    /**
     * @param string $className
     * @param string $constName
     * @param array $context
     * @return mixed
     */
    private function resolveConstant(string $className, string $constName, array $context): mixed
    {
        $fullClassName = $context['__classes'][$className]
            ?? $this->allowedClasses[$className]
            ?? (class_exists($className) ? $className : null);

        if ($fullClassName === null) {
            throw new InvalidArgumentException("Class '{$className}' not found");
        }

        $constant = "{$fullClassName}::{$constName}";
        if (!defined($constant)) {
            throw new InvalidArgumentException("Constant '{$constant}' not found");
        }

        return constant($constant);
    }

    /**
     * @param string $expression
     * @return bool
     */
    private function isArithmeticExpression(string $expression): bool
    {
        return $expression
            |> (static fn (string $str) => preg_replace('/(["\']).*?\\1/', '', $str))
            |> (static fn (string $str) => preg_replace('/\w+\([^)]*\)/', '', $str))
            |> (static fn (string $str) => (bool) preg_match('/[+\-*\/%]/', $str));
    }

    /**
     * @param string $expression
     * @param array $context
     * @return mixed
     */
    private function evaluateArithmetic(string $expression, array $context): mixed
    {
        $tokens = $this->tokenizeArithmetic($expression);
        return (count($tokens) < 3)
            ? $this->evaluateExpression($expression, $context)
            : $this->evaluateArithmeticTokens($tokens, $context);
    }

    /**
     * @param string $expression
     * @return Collection<int, string>
     */
    private function tokenizeArithmetic(string $expression): Collection
    {
        $tokens = collect();
        $operators = ['+', '-', '*', '/', '%'];

        $current = '';
        $depth = 0;
        $inString = false;
        $stringChar = '';

        $lastChar = null;
        $lastToken = null;
        $chars = mb_str_split($expression);
        foreach ($chars as $char) {
            $isCharString = $char === '"' || $char === "'";
            if (!$inString && $isCharString) {
                $inString = true;
                $stringChar = $char;
                $current .= $char;
            } elseif ($inString && $char === $stringChar && ($lastChar ?? '') !== '\\') {
                $inString = false;
                $stringChar = '';
                $current .= $char;
            } elseif (!$inString) {
                if ($char === '(' || $char === '[') {
                    $depth += 1;
                    $current .= $char;
                } elseif ($char === ')' || $char === ']') {
                    $depth -= 1;
                    $current .= $char;
                } elseif ($depth === 0 && in_array($char, $operators, true)) {
                    if ($char === '-' && (trim($current) === '' || in_array($lastToken, $operators, true))) {
                        $current .= $char;
                    } else {
                        $current = trim($current);
                        if ($current !== '') {
                            $tokens->push($current);
                        }
                        $tokens->push($char);
                        $lastToken = $char;
                        $current = '';
                    }
                } else {
                    $current .= $char;
                }
            } else {
                $current .= $char;
            }
        }

        $current = trim($current);
        if ($current !== '') {
            $tokens->push($current);
        }

        return $tokens;
    }

    /**
     * @param Collection<int, string> $tokens
     * @param array $context
     * @return mixed
     */
    private function evaluateArithmeticTokens(Collection $tokens, array $context): mixed
    {
        $values = [];
        $operators = [];

        foreach ($tokens as $index => $token) {
            if ($index % 2 === 0) {
                $values[] = $this->evaluateExpression($token, $context);
            } else {
                $operators[] = $token;
            }
        }

        $i = 0;
        while ($i < count($operators)) {
            if (in_array($operators[$i], ['*', '/', '%'])) {
                $left = $values[$i];
                $right = $values[$i + 1];
                if (is_string($right) && is_numeric($right)) {
                    $right = str_contains($right, '.') ? (float) $right : (int) $right;
                }

                $result = match ($operators[$i]) {
                    '*' => $left * $right,
                    '/' => $right !== 0 ? $left / $right : 0,
                    '%' => $right !== 0 ? $left % $right : 0,
                };

                // Remplacer les deux valeurs par le résultat
                array_splice($values, $i, 2, [$result]);
                array_splice($operators, $i, 1);
            } else {
                $i++;
            }
        }

        // Deuxième passe: + -
        $result = $values[0];
        foreach ($operators as $i => $iValue) {
            $result = match ($iValue) {
                '+' => $result + $values[$i + 1],
                '-' => $result - $values[$i + 1],
                default => $result
            };
        }

        return $result;
    }

    private function evaluateTernary(string $expression, array $context): mixed
    {
        $questionPos = $this->findOperatorPosition($expression, '?');
        if ($questionPos === false) {
            return $this->resolveVariable($expression, $context);
        }

        $condition = substr($expression, 0, $questionPos);
        $rest = substr($expression, $questionPos + 1);

        $colonPos = $this->findOperatorPosition($rest, ':');
        if ($colonPos === false) {
            return $this->resolveVariable($expression, $context);
        }

        $trueValue = substr($rest, 0, $colonPos);
        $falseValue = substr($rest, $colonPos + 1);

        $conditionResult = $this->evaluateExpression(trim($condition), $context);

        return $conditionResult
            ? $this->evaluateExpression(trim($trueValue), $context)
            : $this->evaluateExpression(trim($falseValue), $context);
    }

    private function findOperatorPosition(string $expression, string $operator): int|false
    {
        $depth = 0;
        $inString = false;
        $stringChar = '';

        for ($i = 0, $iMax = strlen($expression); $i < $iMax; $i++) {
            $char = $expression[$i];

            if (!$inString && ($char === '"' || $char === "'")) {
                $inString = true;
                $stringChar = $char;
            } elseif ($inString && $char === $stringChar) {
                $inString = false;
            } elseif (!$inString) {
                if ($char === '(' || $char === '[') {
                    $depth++;
                } elseif ($char === ')' || $char === ']') {
                    $depth--;
                } elseif ($depth === 0 && $char === $operator) {
                    return $i;
                }
            }
        }

        return false;
    }

    private function resolveVariable(string $path, array $context): mixed
    {
        $segments = $this->parsePath($path);
        $value = $context;

        foreach ($segments as $segment) {
            $value = $this->resolveSegment($value, $segment, $context);

            if ($value === null) {
                return null;
            }
        }

        return $value;
    }

    private function parsePath(string $path): array
    {
        $segments = [];
        $current = '';
        $depth = 0;
        $inString = false;
        $stringChar = '';

        for ($i = 0, $iMax = strlen($path); $i < $iMax; $i++) {
            $char = $path[$i];

            if (!$inString && ($char === '"' || $char === "'")) {
                $inString = true;
                $stringChar = $char;
                $current .= $char;
            } elseif ($inString && $char === $stringChar) {
                $inString = false;
                $current .= $char;
            } elseif ($char === '(' || $char === '[') {
                $depth++;
                $current .= $char;
            } elseif ($char === ')' || $char === ']') {
                $depth--;
                $current .= $char;
            } elseif ($char === '.' && $depth === 0 && !$inString) {
                if ($current !== '') {
                    $segments[] = $this->parseSegment($current);
                    $current = '';
                }
            } else {
                $current .= $char;
            }
        }

        if ($current !== '') {
            $segments[] = $this->parseSegment($current);
        }

        return $segments;
    }

    private function parseSegment(string $segment): array
    {
        // Méthode avec arguments: getName() ou isActive(true, "test")
        if (preg_match('/^(\w+)\((.*)\)$/', $segment, $matches)) {
            return [
                'type' => 'method',
                'name' => $matches[1],
                'args_raw' => $matches[2]
            ];
        }

        // Propriété ou clé de tableau
        return [
            'type' => 'property',
            'name' => $segment
        ];
    }

    private function parseArguments(string $argsString, array $context): array
    {
        if (trim($argsString) === '') {
            return [];
        }

        $args = [];
        $elements = $this->splitArrayElements($argsString);

        foreach ($elements as $element) {
            $args[] = $this->evaluateExpression(trim($element), $context);
        }

        return $args;
    }

    private function resolveSegment(mixed $value, array $segment, array $context): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($segment['type'] === 'method') {
            $args = $this->parseArguments($segment['args_raw'], $context);
            return $this->callMethod($value, $segment['name'], $args);
        }

        return $this->accessProperty($value, $segment['name']);
    }

    private function accessProperty(mixed $value, string $name): mixed
    {
        // Tableau avec clé string ou int
        if (is_array($value)) {
            $key = is_numeric($name) ? (int) $name : $name;
            return $value[$key] ?? null;
        }

        // Objet
        if (is_object($value)) {
            // 1. Propriété publique
            if (property_exists($value, $name)) {
                $reflection = new ReflectionProperty($value, $name);
                if ($reflection->isPublic()) {
                    return $value->$name;
                }
            }

            // 2. Getter (getName, isName, hasName)
            $getters = [
                'get' . ucfirst($name),
                'is' . ucfirst($name),
                'has' . ucfirst($name),
                $name // Méthode directe sans arguments
            ];

            foreach ($getters as $getter) {
                if (method_exists($value, $getter)) {
                    $reflection = new ReflectionMethod($value, $getter);
                    if ($reflection->isPublic() && $reflection->getNumberOfRequiredParameters() === 0) {
                        return $value->$getter();
                    }
                }
            }

            // 3. __get magic method
            if (method_exists($value, '__get')) {
                return $value->$name;
            }
        }

        return null;
    }

    private function callMethod(mixed $value, string $method, array $args): mixed
    {
        if (!is_object($value)) {
            throw new InvalidArgumentException(
                "Cannot call method '$method' on non-object"
            );
        }

        if (!method_exists($value, $method)) {
            throw new InvalidArgumentException(
                "Method '$method' does not exist on " . get_class($value)
            );
        }

        $reflection = new ReflectionMethod($value, $method);

        if (!$reflection->isPublic()) {
            throw new InvalidArgumentException(
                "Method '$method' is not public on " . get_class($value)
            );
        }

        return $value->$method(...$args);
    }
}
