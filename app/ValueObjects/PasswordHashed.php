<?php

namespace App\ValueObjects;

use SensitiveParameter;
use Tuto\Utils\Hash;

class PasswordHashed
{
    /**
     * @param string $password
     */
    public function __construct(
        #[SensitiveParameter]
        private readonly string $password,
    ) {
    }

    /**
     * @param string $plainPassword
     * @return self
     */
    public static function fromPlainText(string $plainPassword): self
    {
        return new self(Hash::make($plainPassword));
    }

    /**
     * @param string $plainPassword
     * @return bool
     */
    public function verify(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->password);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->password;
    }
}