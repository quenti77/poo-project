<?php

namespace Tuto\Utils;

use Tuto\Utils\Dump\DumpHelper;
use Tuto\Utils\Dump\DumpInterface;
use Tuto\Utils\Dump\VarType;

final class VarDump
{
    use DumpHelper;

    /**
     * @param int $deepPosition
     */
    public function __construct(private readonly int $deepPosition)
    {
    }

    /**
     * @return array<int, DumpInterface>
     */
    public function dump(mixed ...$vars): array
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $this->deepPosition);
        $caller = $backtrace[$this->deepPosition - 1] ?? ['file' => 'unknown', 'line' => ''];

        $result = [
            'header' => [
                'file' => $caller['file'],
                'line' => $caller['line'],
            ],
            'items' => [],
        ];
        foreach ($vars as $var) {
            $varType = VarType::fromVar($var);
            $dump = $this->getDumpFromVar($var, $varType);
            $result['items'][] = $dump->render();
        }

        return $result;
    }
}
