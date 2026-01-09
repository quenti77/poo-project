<?php

namespace Tuto\Utils\Dump;

interface DumpInterface
{
    public const int MAX_DEPTH_SIZE = 10;

    /**
     * @param int $depthSize
     * @return array<string, mixed>
     */
    public function render(int $depthSize = 0): array;
}
