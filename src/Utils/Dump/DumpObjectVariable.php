<?php

namespace Tuto\Utils\Dump;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Throwable;

class DumpObjectVariable implements DumpInterface
{
    use DumpHelper;

    /**
     * @param object $var
     */
    public function __construct(private readonly object $var)
    {
    }

    /**
     * @param int $depthSize
     * @return array<string, mixed>
     */
    public function render(int $depthSize = 0): array
    {
        if ($depthSize > DumpInterface::MAX_DEPTH_SIZE) {
            return [
                'type' => 'object',
                'class' => get_class($this->var),
                'maxDepthReached' => true,
            ];
        }

        $reflection = new ReflectionClass($this->var);
        return $this->analyzeObject($reflection, $depthSize);
    }

    /**
     * @param ReflectionClass $reflection
     * @param int $depthSize
     * @return array<string, mixed>
     */
    private function analyzeObject(ReflectionClass $reflection, int $depthSize): array
    {
        $result = [
            'type' => 'object',
            'class' => $reflection->getName(),
            'shortName' => $reflection->getShortName(),
            'namespace' => $reflection->getNamespaceName(),
            'isAnonymous' => $reflection->isAnonymous(),
            'isAbstract' => $reflection->isAbstract(),
            'isFinal' => $reflection->isFinal(),
            'isReadOnly' => $reflection->isReadOnly(),
            'file' => $reflection->getFileName() ?: 'internal',
            'line' => $reflection->getStartLine() ?: 0,
        ];

        $parentClass = $reflection->getParentClass();
        if ($parentClass) {
            $result['parent'] = $parentClass->getName();
        }

        $interfaces = $reflection->getInterfaceNames();
        if (!empty($interfaces)) {
            $result['interfaces'] = $interfaces;
        }

        $traits = $reflection->getTraitNames();
        if (!empty($traits)) {
            $result['traits'] = $traits;
        }

        $constants = $reflection->getConstants();
        if (!empty($constants)) {
            $result['constants'] = [];
            foreach ($constants as $name => $value) {
                $valueType = VarType::fromVar($value);
                $valueDumper = $this->getDumpFromVar($value, $valueType);
                $result['constants'][$name] = $valueDumper->render($depthSize + 1);
            }
        }

        $result['properties'] = $this->extractProperties($reflection, $depthSize);
        $result['methods'] = $this->extractMethods($reflection);

        return $result;
    }

    /**
     * @param ReflectionClass $reflection
     * @param int $depthSize
     * @return array<string, array<string, mixed>>
     */
    private function extractProperties(ReflectionClass $reflection, int $depthSize): array
    {
        $properties = [];
        $allProperties = $reflection->getProperties();

        foreach ($allProperties as $property) {
            $propertyInfo = [
                'name' => $property->getName(),
                'visibility' => $this->getVisibility($property),
                'isStatic' => $property->isStatic(),
                'isReadOnly' => $property->isReadOnly(),
                'hasDefaultValue' => $property->hasDefaultValue(),
                'type' => $property->getType()?->__toString() ?? 'mixed',
            ];

            if ($property->isInitialized($this->var)) {
                try {
                    $value = $property->getValue($this->var);
                    $valueType = VarType::fromVar($value);
                    $valueDumper = $this->getDumpFromVar($value, $valueType);
                    $propertyInfo['value'] = $valueDumper->render($depthSize + 1);
                    $propertyInfo['isInitialized'] = true;
                } catch (Throwable $exception) {
                    $propertyInfo['value'] = 'error: ' . $exception->getMessage();
                    $propertyInfo['isInitialized'] = false;
                }
            } else {
                $propertyInfo['isInitialized'] = false;
            }

            if ($property->hasDefaultValue()) {
                try {
                    $defaultValue = $property->getDefaultValue();
                    $valueType = VarType::fromVar($defaultValue);
                    $valueDumper = $this->getDumpFromVar($defaultValue, $valueType);
                    $propertyInfo['defaultValue'] = $valueDumper->render($depthSize + 1);
                } catch (Throwable $exception) {
                    $propertyInfo['defaultValue'] = 'error: ' . $exception->getMessage();
                }
            }

            $declaringClass = $property->getDeclaringClass();
            $propertyInfo['declaringClass'] = $declaringClass->getName();

            $properties[$property->getName()] = $propertyInfo;
        }

        return $properties;
    }

    /**
     * @param ReflectionClass $reflection
     * @return array<string, array<string, mixed>>
     */
    private function extractMethods(ReflectionClass $reflection): array
    {
        $methods = [];
        $allMethods = $reflection->getMethods();

        foreach ($allMethods as $method) {
            $methodInfo = [
                'name' => $method->getName(),
                'visibility' => $this->getVisibility($method),
                'isStatic' => $method->isStatic(),
                'isAbstract' => $method->isAbstract(),
                'isFinal' => $method->isFinal(),
                'isConstructor' => $method->isConstructor(),
                'isDestructor' => $method->isDestructor(),
                'declaringClass' => $method->getDeclaringClass()->getName(),
                'numberOfParameters' => $method->getNumberOfParameters(),
                'numberOfRequiredParameters' => $method->getNumberOfRequiredParameters(),
            ];

            // Type de retour
            if ($method->hasReturnType()) {
                $methodInfo['returnType'] = $method->getReturnType()?->__toString() ?? 'void';
            }

            $methods[$method->getName()] = $methodInfo;
        }

        return $methods;
    }

    /**
     * @param ReflectionProperty|ReflectionMethod $reflection
     * @return string
     */
    private function getVisibility(ReflectionProperty|ReflectionMethod $reflection): string
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
