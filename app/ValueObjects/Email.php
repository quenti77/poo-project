<?php

namespace App\ValueObjects;

use InvalidArgumentException;

class Email
{
    /**
     * @param string $account
     * @param string $domain
     */
    public function __construct(
        public readonly string $account,
        public readonly string $domain,
    ) {
        if (!self::verifyEmail((string) $this)) {
            throw new InvalidArgumentException("Email not used a valid format");
        }
    }

    /**
     * @param string $email
     * @return self
     */
    public static function fromString(string $email): self
    {
        $parts = explode('@', $email, 2);
        if (count($parts) !== 2) {
            throw new InvalidArgumentException("Email not used a valid format");
        }
        return new self($parts[0], $parts[1]);
    }

    /**
     * @param string $email
     * @return bool
     */
    public static function verifyEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return "{$this->account}@{$this->domain}";
    }
}