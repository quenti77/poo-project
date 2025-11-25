<?php

namespace Tuto\Database\Migrations;

use App\ValueObjects\Ulid;
use DateTimeImmutable;
use SplFileInfo;

class MigrationEntity
{
    public function __construct(
        private readonly Ulid $id,
        private readonly SplFileInfo $file,
        private readonly int $step,
        private readonly DateTimeImmutable $createAt,
    ) {
    }

    public function getId(): Ulid
    {
        return $this->id;
    }

    public function getFile(): SplFileInfo
    {
        return $this->file;
    }

    public function getName(): string
    {
        return $this->file->getBasename(".{$this->file->getExtension()}");
    }

    public function getStep(): int
    {
        return $this->step;
    }

    public function getCreateAt(): DateTimeImmutable
    {
        return $this->createAt;
    }
}
