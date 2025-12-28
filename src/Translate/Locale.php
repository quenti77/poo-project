<?php

namespace Tuto\Translate;

use InvalidArgumentException;

class Locale
{
    private const string FORMAT = '/^(?P<language>[a-z]{2})([-_])(?P<country>[a-z]{2})$/i';

    public function __construct(
        private readonly string $language,
        private readonly string $country,
    ) {
    }

    /**
     * @param string $locale
     * @return self
     * @throws InvalidArgumentException
     */
    public static function fromLocale(string $locale): self
    {
        $matches = [];
        if (!preg_match(self::FORMAT, $locale, $matches)) {
            throw new InvalidArgumentException("The locale '{$locale}' is not valid");
        }

        return new self(
            mb_strtolower($matches['language']),
            mb_strtoupper($matches['country']),
        );
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return mb_strtolower($this->language);
    }

    /**
     * @return string
     */
    public function getCountry(): string
    {
        return mb_strtoupper($this->country);
    }

    /**
     * @return string return "local_COUNTRY" version
     */
    public function toPOSIX(): string
    {
        return "{$this->getLanguage()}_{$this->getCountry()}";
    }

    /**
     * @return string return "local-COUNTRY" version
     */
    public function toBCP(): string
    {
        return "{$this->getLanguage()}-{$this->getCountry()}";
    }
}