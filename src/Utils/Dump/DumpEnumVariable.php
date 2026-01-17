<?php

namespace Tuto\Utils\Dump;

use BackedEnum;
use UnitEnum;

class DumpEnumVariable implements DumpInterface
{
    /**
     * @param UnitEnum $var
     */
    public function __construct(private readonly UnitEnum $var)
    {
    }

    /**
     * @param int $depthSize
     * @return array<string, mixed>
     */
    public function render(int $depthSize = 0): array
    {
        $result = [
            'type' => 'enum',
            'name' => $this->var::class,
            'key' => '',
            'value' => '',
            'possibilities' => []
        ];

        if ($this->var instanceof BackedEnum) {
            $result['key'] = $this->var->name;
            $result['value'] = $this->var->value;
        } else {
            $result['value'] = $this->var->name;
        }

        foreach ($this->var::cases() as $case) {
            $caseResult = ['name' => $case->name, 'value' => ''];
            if ($case instanceof BackedEnum) {
                $caseResult['value'] = $case->value;
            }
            $result['possibilities'][] = $caseResult;
        }

        return $result;
    }
}
