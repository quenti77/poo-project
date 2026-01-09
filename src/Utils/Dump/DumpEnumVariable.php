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
            'name' => $this->var->name,
            'value' => '',
            'possibilities' => []
        ];

        if ($this->var instanceof BackedEnum) {
            $result['value'] = $this->var->value;
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
