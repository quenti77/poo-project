<?php

namespace Tuto\Utils;

use DateTimeImmutable;

class CurrentTime
{
    /**
     * @return DateTimeImmutable
     */
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}