<?php

namespace App\Entities;

use App\ValueObjects\Email;
use App\ValueObjects\Ulid;
use DateTimeImmutable;
use SensitiveParameter;

class User
{
    public function __construct(
        private readonly Ulid $id,
        private Group $group,
        private string $name,
        private Email $email,
        private DateTimeImmutable|null $emailVerifiedAt,
        #[SensitiveParameter]
        private string $password,
        private string|null $token,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
    }

    public function getId(): Ulid
    {
        return $this->id;
    }

    public function getGroup(): Group
    {
        return $this->group;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getEmailVerifiedAt(): DateTimeImmutable|null
    {
        return $this->emailVerifiedAt;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getToken(): string|null
    {
        return $this->token;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}