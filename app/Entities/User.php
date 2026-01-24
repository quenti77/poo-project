<?php

namespace App\Entities;

use App\Traits\EntityTimestampsTrait;
use App\ValueObjects\Email;
use App\ValueObjects\PasswordHashed;
use DateTimeImmutable;
use Tuto\Utils\ValueObject\Ulid;

class User
{
    use EntityTimestampsTrait;

    /**
     * @param Ulid $id
     * @param Ulid $groupId
     * @param string $name
     * @param Email $email
     * @param DateTimeImmutable|null $emailVerifiedAt
     * @param PasswordHashed $password
     * @param string|null $token
     * @param DateTimeImmutable $createdAt
     * @param DateTimeImmutable $updatedAt
     */
    public function __construct(
        private readonly Ulid $id,
        private Group $group,
        private string $name,
        private Email $email,
        private DateTimeImmutable|null $emailVerifiedAt,
        private PasswordHashed $password,
        private string|null $token,
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
     * @return Group
     */
    public function getGroup(): Group
    {
        return $this->group;
    }

    /**
     * @param Group $group
     * @return void
     */
    public function moveToGroup(Group $group): void
    {
        $this->group = $group;
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
    public function changeName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return Email
     */
    public function getEmail(): Email
    {
        return $this->email;
    }

    /**
     * @param Email $email
     * @return void
     */
    public function changeEmail(Email $email): void
    {
        $this->email = $email;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getEmailVerifiedAt(): DateTimeImmutable|null
    {
        return $this->emailVerifiedAt;
    }

    /**
     * @param DateTimeImmutable $current
     * @return void
     */
    public function verifyEmail(DateTimeImmutable $current): void
    {
        $this->emailVerifiedAt = $current;
    }

    /**
     * @return PasswordHashed
     */
    public function getPassword(): PasswordHashed
    {
        return $this->password;
    }

    /**
     * @param PasswordHashed|string $password
     * @return void
     */
    public function changePassword(PasswordHashed|string $password): void
    {
        if (is_string($password)) {
            $password = PasswordHashed::fromPlainText($password);
        }
        $this->password = $password;
    }

    /**
     * @return string|null
     */
    public function getToken(): string|null
    {
        return $this->token;
    }

    /**
     * @param string $token
     * @return void
     */
    public function setRememberToken(string $token): void
    {
        $this->token = $token;
    }
}