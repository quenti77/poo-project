<?php

namespace Tuto\Utils\Dump;

use InvalidArgumentException;

class DumpBasicVariable implements DumpInterface
{
    /**
     * @param mixed $var
     */
    public function __construct(private readonly mixed $var)
    {
        if ($var !== null && !is_scalar($this->var))  {
            throw new InvalidArgumentException('This dumper must be contains only null or scalar variable');
        }
    }

    /**
     * @param int $depthSize
     * @return array<string, mixed>
     */
    public function render(int $depthSize = 0): array
    {
        return [
            'type' => gettype($this->var),
            'value' => $this->var,
        ];
    }
}
