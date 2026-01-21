<?php

namespace App\Entities;

use App\Traits\EntityTimestampsTrait;
use App\ValueObjects\Level;
use DateTimeImmutable;
use Tuto\Utils\ValueObject\Ulid;

class Group
{
    use EntityTimestampsTrait;

    public function __construct(
        private readonly Ulid $id,
        private string $name,
        private Level $level,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ) {
        $this->createdAt = $createdAt;
        $this->update($updatedAt);
    }

    /**
     * @return Ulid
     */
    public function getId(): Ulid
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return void
     */
    public function rename(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return Level
     */
    public function getLevel(): Level
    {
        return $this->level;
    }

    /**
     * @param Level $level
     * @return void
     */
    public function changeLevel(Level $level): void
    {
        $this->level = $level;
    }

}