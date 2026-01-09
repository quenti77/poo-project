<?php

namespace Tuto\Utils\Dump;

trait DumpHelper
{
    /**
     * @param mixed $var
     * @param VarType|null $varType
     * @return DumpInterface
     */
    public function getDumpFromVar(mixed $var, VarType|null $varType): DumpInterface
    {
        return match ($varType) {
            VarType::NULL, VarType::SCALAR => new DumpBasicVariable($var),
            VarType::ARRAY => new DumpArrayVariable($var),
            VarType::ENUM => new DumpEnumVariable($var),
            VarType::OBJECT => new DumpObjectVariable($var),
            VarType::CALLABLE => new DumpCallableVariable($var),
            default => null,
        };
    }
}
