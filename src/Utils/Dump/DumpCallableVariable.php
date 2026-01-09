<?php

namespace Tuto\Utils\Dump;

use Closure;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

class DumpCallableVariable implements DumpInterface
{
    use DumpHelper;

    /**
     * @param callable $var
     */
    public function __construct(private readonly mixed $var)
    {
    }

    /**
     * @param int $depthSize
     * @return array<string, mixed>
     */
    public function render(int $depthSize = 0): array
    {
        try {
            $result = $this->analyzeCallable($depthSize);
        } catch (ReflectionException $e) {
            $result = [
                'type' => 'unknown',
                'error' => $e->getMessage(),
            ];
        }

        return $result;
    }

    /**
     * @param int $depthSize
     * @return array<string, mixed>
     * @throws ReflectionException
     */
    private function analyzeCallable(int $depthSize): array
    {
        // Closure
        if ($this->var instanceof Closure) {
            return $this->analyzeClosure($this->var, $depthSize);
        }

        if (is_string($this->var)) {
            if (str_contains($this->var, '::')) {
                [$class, $method] = explode('::', $this->var, 2);
                return $this->analyzeMethod($class, $method, $depthSize);
            }
            return $this->analyzeFunction($this->var, $depthSize);
        }

        if (is_array($this->var) && count($this->var) === 2) {
            [$classOrObject, $method] = $this->var;
            $isStatic = is_string($classOrObject);
            $class = is_object($classOrObject) ? get_class($classOrObject) : $classOrObject;
            return $this->analyzeMethod($class, $method, $isStatic);
        }

        if (is_object($this->var)) {
            return $this->analyzeInvokable($this->var, $depthSize);
        }

        return ['type' => 'unknown'];
    }

    /**
     * @param Closure $closure
     * @param int $depthSize
     * @return array<string, mixed>
     * @throws ReflectionException
     */
    private function analyzeClosure(Closure $closure, int $depthSize): array
    {
        $reflection = new ReflectionFunction($closure);

        $result = [
            'type' => 'closure',
            'name' => $reflection->getName(),
            'file' => $reflection->getFileName() ?: 'unknown',
            'line' => $reflection->getStartLine() ?: 0,
            'parameters' => $this->extractParameters($reflection, $depthSize),
            'returnType' => $this->extractReturnType($reflection),
        ];

        $staticVars = $reflection->getStaticVariables();
        if (!empty($staticVars)) {
            $result['use'] = [];
            foreach ($staticVars as $name => $value) {
                $valueType = VarType::fromVar($value);
                $valueDumper = $this->getDumpFromVar($value, $valueType);
                $result['use'][$name] = $valueDumper->render($depthSize);
            }
        }

        return $result;
    }

    /**
     * @param string $functionName
     * @param int $depthSize
     * @return array<string, mixed>
     */
    private function analyzeFunction(string $functionName, int $depthSize): array
    {
        if (!function_exists($functionName)) {
            return [
                'type' => 'function',
                'name' => $functionName,
                'exists' => false,
            ];
        }

        $reflection = new ReflectionFunction($functionName);

        return [
            'type' => 'function',
            'name' => $functionName,
            'exists' => true,
            'file' => $reflection->getFileName() ?: 'internal',
            'line' => $reflection->getStartLine() ?: 0,
            'isInternal' => $reflection->isInternal(),
            'parameters' => $this->extractParameters($reflection, $depthSize),
            'returnType' => $this->extractReturnType($reflection),
        ];
    }

    /**
     * @param string $class
     * @param string $method
     * @param int $depthSize
     * @return array<string, mixed>
     */
    private function analyzeMethod(string $class, string $method, int $depthSize): array
    {
        if (!class_exists($class) && !interface_exists($class) && !trait_exists($class)) {
            return [
                'type' => 'method',
                'class' => $class,
                'method' => $method,
                'exists' => false,
            ];
        }

        if (!method_exists($class, $method)) {
            return [
                'type' => 'method',
                'class' => $class,
                'method' => $method,
                'exists' => false,
            ];
        }

        $reflection = new ReflectionMethod($class, $method);

        return [
            'type' => 'method',
            'class' => $class,
            'method' => $method,
            'exists' => true,
            'isStatic' => $reflection->isStatic(),
            'visibility' => $this->getVisibility($reflection),
            'file' => $reflection->getFileName() ?: 'internal',
            'line' => $reflection->getStartLine() ?: 0,
            'isInternal' => $reflection->isInternal(),
            'parameters' => $this->extractParameters($reflection, $depthSize),
            'returnType' => $this->extractReturnType($reflection),
        ];
    }

    /**
     * @param object $object
     * @param int $depthSize
     * @return array<string, mixed>
     */
    private function analyzeInvokable(object $object, int $depthSize): array
    {
        $class = get_class($object);

        if (!method_exists($object, '__invoke')) {
            return [
                'type' => 'invokable',
                'class' => $class,
                'hasInvoke' => false,
            ];
        }

        $reflection = new ReflectionMethod($object, '__invoke');

        return [
            'type' => 'invokable',
            'class' => $class,
            'hasInvoke' => true,
            'visibility' => $this->getVisibility($reflection),
            'file' => $reflection->getFileName() ?: 'internal',
            'line' => $reflection->getStartLine() ?: 0,
            'parameters' => $this->extractParameters($reflection, $depthSize),
            'returnType' => $this->extractReturnType($reflection),
        ];
    }

    /**
     * @param ReflectionFunction|ReflectionMethod $reflection
     * @param int $depthSize
     * @return array<int, array<string, mixed>>
     */
    private function extractParameters(ReflectionFunction|ReflectionMethod $reflection, int $depthSize): array
    {
        $parameters = [];

        foreach ($reflection->getParameters() as $param) {
            $paramInfo = [
                'name' => $param->getName(),
                'position' => $param->getPosition(),
                'type' => $param->getType()?->__toString() ?? 'mixed',
                'optional' => $param->isOptional(),
                'variadic' => $param->isVariadic(),
                'hasDefault' => $param->isDefaultValueAvailable(),
            ];

            if ($param->isDefaultValueAvailable()) {
                $defaultValue = $param->getDefaultValue();
                $valueType = VarType::fromVar($defaultValue);
                $valueDumper = $this->getDumpFromVar($defaultValue, $valueType);
                $paramInfo['default'] = $valueDumper->render($depthSize);
            }

            $parameters[] = $paramInfo;
        }

        return $parameters;
    }

    /**
     * @param ReflectionFunction|ReflectionMethod $reflection
     * @return string
     */
    private function extractReturnType(ReflectionFunction|ReflectionMethod $reflection): string
    {
        if (!$reflection->hasReturnType()) {
            return 'void';
        }

        return $reflection->getReturnType()?->__toString() ?? 'void';
    }

    /**
     * @param ReflectionMethod $reflection
     * @return string
     */
    private function getVisibility(ReflectionMethod $reflection): string
    {
        if ($reflection->isPublic()) {
            return 'public';
        }
        if ($reflection->isProtected()) {
            return 'protected';
        }
        if ($reflection->isPrivate()) {
            return 'private';
        }
        return 'unknown';
    }
}
