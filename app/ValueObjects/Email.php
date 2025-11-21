<?php

namespace App\ValueObjects;

use InvalidArgumentException;

class Email
{
    public function __construct(public readonly string $username, public readonly string $domain)
    {
    }

    public static function makeByAddress(string $email): self
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException("Email address is invalid");
        }

        [$username, $domain] = explode('@', $email, 2);
        return new self($username, $domain);
    }

    public function __toString(): string
    {
        return "{$this->username}@{$this->domain}";
    }
}