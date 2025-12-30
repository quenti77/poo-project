<?php

namespace Tuto\Translate;

use InvalidArgumentException;
use Tuto\Utils\File;

class TranslatorFactory
{
    /**
     * @param string $folder
     * @return Translator
     */
    public static function make(string $folder, array $locales, string $fallback): Translator
    {
        $collectionLocales = collect($locales)
            ->map(static fn(int $key, string $locale) => Locale::fromLocale($locale));

        $fallbackLocale = Locale::fromLocale($fallback);
        $fallbackLocale = $collectionLocales->find(
            static fn(int $key, Locale $current) => $current->toBCP() === $fallbackLocale->toBCP(),
        );
        if ($fallbackLocale === null) {
            throw new InvalidArgumentException("Fallback locale '{$fallback}' does not exist in locales [" . implode(', ', $locales) . "]");
        }

        return new Translator(File::absolute($folder), $collectionLocales, $fallbackLocale);
    }
}