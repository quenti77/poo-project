<?php

namespace Tuto\Utils\Dump;

class DumpArrayVariable implements DumpInterface
{
    use DumpHelper;

    /**
     * @param array $var
     */
    public function __construct(private readonly array $var)
    {
    }

    /**
     * @param int $depthSize
     * @return array<string, mixed>
     */
    public function render(int $depthSize = 0): array
    {
        if ($depthSize > DumpInterface::MAX_DEPTH_SIZE) {
            return [];
        }

        $depthSize += 1;
        $result = [
            'total' => count($this->var),
            'type' => 'array',
            'items' => []
        ];

        foreach ($this->var as $key => $value) {
            $keyType = VarType::fromVar($key);
            $keyDumper = $this->getDumpFromVar($key, $keyType);
            $valueType = VarType::fromVar($value);
            $valueDumper = $this->getDumpFromVar($value, $valueType);

            $result['items'][] = [
                'key' => $keyDumper->render($depthSize),
                'value' => $valueDumper->render($depthSize),
            ];
        }

        return $result;
    }
}
