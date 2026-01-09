<?php

namespace Tuto\Translate;

use Tuto\Collections\Collection;

class Translation
{
    public function __construct(
        private readonly Locale $locale,
        /** @param Collection<string, string|Collection> $dictionary */
        private readonly Collection $dictionary,
    ) {
    }

    /**
     * @return Locale
     */
    public function getLocale(): Locale
    {
        return $this->locale;
    }

    /**
     * @param string $key
     * @return string|array|null
     */
    public function get(string $key): string|array|null
    {
        return $this->recursiveGet(collect(explode('.', $key)), $this->dictionary);
    }

    /**
     * @param Collection<int, string> $keys
     * @param Collection<string, string|Collection>|string $dictionary
     * @return string|array|null
     */
    private function recursiveGet(Collection $keys, Collection|string $dictionary): string|array|null
    {
        $currentKey = $keys->shift();
        if (is_numeric($currentKey)) {
            $currentKey = (int) $currentKey;
        }

        if ($currentKey === null) {
            return is_string($dictionary) ? $dictionary : $dictionary->all();
        }
        if (is_string($dictionary)) {
            return null;
        }
        if (!$dictionary->hasKeys($currentKey)) {
            return null;
        }
        return $this->recursiveGet($keys, $dictionary[$currentKey]);
    }
}
