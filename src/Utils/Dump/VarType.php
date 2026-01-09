<?php

namespace Tuto\Utils\Dump;

use UnitEnum;

enum VarType: string
{
    case NULL = 'null';
    case SCALAR = 'scalar';
    case ARRAY = 'array';
    case ENUM = 'enum';
    case OBJECT = 'object';
    case CALLABLE = 'callable';

    /**
     * @param mixed $var
     * @return self|null
     */
    public static function fromVar(mixed $var): self|null
    {
        if ($var === null) {
            return self::NULL;
        }
        if (is_scalar($var)) {
            return self::SCALAR;
        }
        if (is_callable($var)) {
            return self::CALLABLE;
        }
        if (is_array($var)) {
            return self::ARRAY;
        }
        if (is_object($var)) {
            return $var instanceof UnitEnum ? self::ENUM : self::OBJECT;
        }
        return null;
    }
}
