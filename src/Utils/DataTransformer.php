<?php

namespace Tuto\Utils;

use DateMalformedStringException;
use DateTimeImmutable;
use JsonException;

trait DataTransformer
{
    /**
     * @throws DateMalformedStringException
     */
    private function transformToDateTime(string $dateTime): DateTimeImmutable
    {
        return new DateTimeImmutable($dateTime);
    }

    /**
     * @throws DateMalformedStringException
     */
    private function transformToDateTimeOrNull(string|null $dateTime): DateTimeImmutable|null
    {
        if ($dateTime === null) {
            return null;
        }
        return $this->transformToDateTime($dateTime);
    }

    /**
     * @param string $ulid
     * @return Ulid
     */
    private function transformToUlid(string $ulid): Ulid
    {
        return new Ulid($ulid);
    }

    /**
     * @param string $json
     * @return array
     * @throws JsonException
     */
    private function transformJsonToArray(string $json): array
    {
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }
}