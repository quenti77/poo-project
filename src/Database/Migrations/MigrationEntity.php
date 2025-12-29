<?php

namespace Tuto\Database\Migrations;

use DateTimeImmutable;
use SplFileInfo;
use Tuto\Utils\Ulid;

class MigrationEntity
{
    /**
     * @param Ulid $id
     * @param SplFileInfo $file
     * @param int $step
     * @param DateTimeImmutable $createAt
     */
    public function __construct(
        private readonly Ulid $id,
        private readonly SplFileInfo $file,
        private readonly int $step,
        private readonly DateTimeImmutable $createAt,
    ) {
    }

    /**
     * @return Ulid
     */
    public function getId(): Ulid
    {
        return $this->id;
    }

    /**
     * @return SplFileInfo
     */
    public function getFile(): SplFileInfo
    {
        return $this->file;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->file->getBasename(".{$this->file->getExtension()}");
    }

    /**
     * @return int
     */
    public function getStep(): int
    {
        return $this->step;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getCreateAt(): DateTimeImmutable
    {
        return $this->createAt;
    }
}
