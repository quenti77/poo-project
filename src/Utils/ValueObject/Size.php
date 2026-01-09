<?php

namespace Tuto\Utils\ValueObject;

class Size
{
    /**
     * @param int $width
     * @param int $height
     */
    public function __construct(
        public readonly int $width,
        public readonly int $height,
    ) {
    }
}
