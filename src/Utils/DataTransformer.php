<?php

namespace Tuto\Utils;

use DateMalformedStringException;
use DateTimeImmutable;

trait DataTransformer
{
    /**
     * @throws DateMalformedStringException
     */
    public function transformToDateTime(string $dateTime): DateTimeImmutable
    {
        return new DateTimeImmutable($dateTime);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function transformToDateTimeOrNull(string|null $dateTime): DateTimeImmutable|null
    {
        if ($dateTime === null) {
            return null;
        }
        return $this->transformToDateTime($dateTime);
    }
}