<?php

namespace Tuto\Translate;

use FilesystemIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Tuto\Collections\Collection;
use Tuto\Http\Requests\Uri;

class Translator
{
    /** @param Collection<string, Translation> $translations */
    private Collection $translations;

    /** @var Locale|null $current */
    private Locale|null $current;

    public function __construct(
        private readonly string $folder,
        /** @param Collection<int, Locale> $locales */
        private readonly Collection $locales,
        private readonly Locale $fallback,
    ) {
        $this->translations = collect();

        $this->updateCurrentLocale($this->fallback);
    }

    /**
     * @return Collection<int, Locale>
     */
    public function getLocales(): Collection
    {
        return $this->locales;
    }

    /**
     * @return Locale
     */
    public function getFallback(): Locale
    {
        return $this->fallback;
    }

    /**
     * @param Locale $locale
     * @return void
     */
    public function updateCurrentLocale(Locale $locale): void
    {
        $localeFound = $this->locales->find(
            static fn (int $key, Locale $current) => $current->toBCP() === $locale->toBCP(),
        );
        if ($localeFound === null) {
            throw new InvalidArgumentException("Locale '{$locale}' can not be choice");
        }

        $this->current = $localeFound;
        if ($this->translations->hasKeys($locale->toPOSIX())) {
            return;
        }
        $this->load($localeFound);
    }

    /**
     * @param string $key
     * @param int|float $count
     * @param array $context
     * @return string|array
     */
    public function translate(string $key, int|float $count = 1, array $context = [], Locale|null $locale = null): string|array
    {
        if ($this->current === null) {
            throw new RuntimeException("Select locale before used translate");
        }
        $locale ??= $this->current;

        /** @var Translation $dictionary */
        $dictionary = $this->translations[$locale?->toPOSIX()];

        $item = $dictionary->get($key);
        if ($item === null) {
            if ($locale?->toBCP() !== $this->fallback->toBCP()) {
                return $this->translate($key, $count, $context, $this->fallback);
            }
            logger()->warning("Missing translation key '{$key}'", [
                'locale' => $locale?->toBCP(),
                'key' => $key,
            ]);
            return $key;
        }

        if (is_array($item)) {
            return $item;
        }

        $pluralParts = collect(explode('|', $item))
            ->map(static fn(int $key, string $val) => trim($val))
            ->filter(static fn(int $key, string $val) => !empty($val));
        $singular = $pluralParts->shift();
        $plural = $pluralParts->join('|');

        $choice = $count <= 1 || empty($plural) ? $singular : $plural;
        foreach ($context as $contextKey => $contextValue) {
            $choice = str_replace('{' . $contextKey . '}', $contextValue, $choice);
        }

        return $choice;
    }

    /**
     * @param Locale $locale
     * @return void
     */
    private function load(Locale $locale): void
    {
        $data = collect();

        $folderPath = "{$this->folder}/{$locale}";
        $folder = str_replace(DIRECTORY_SEPARATOR, '/', realpath($folderPath));
        if ($folder === false || !is_dir($folder)) {
            throw new InvalidArgumentException("Folder '{$folderPath}' does not exist or is not a directory");
        }

        $rdi = new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS);
        $rii = new RecursiveIteratorIterator($rdi);

        /** @var SplFileInfo $file */
        foreach ($rii as $file) {
            if ($file->getExtension() === 'php') {
                $this->loadFile($data, $folder, $file);
            }
        }

        $this->translations[$locale->toPOSIX()] = new Translation($locale, $data);
    }

    /**
     * @param Collection<string, Collection> $data
     * @param string $folder
     * @param SplFileInfo $file
     * @return void
     */
    private function loadFile(Collection $data, string $folder, SplFileInfo $file): void
    {
        $realPath = $file->getRealPath();
        if ($realPath === false) {
            throw new RuntimeException("Cannot resolve real path for file");
        }

        $dictionary = require $realPath;
        if (!is_array($dictionary)) {
            throw new InvalidArgumentException("This file '{$realPath}' does not return an array");
        }

        $realPath = str_replace(DIRECTORY_SEPARATOR, '/', $realPath);
        $base = $realPath
            |> (static fn(string $v) => str_replace($folder, '', $v))
            |> Uri::trimPath(...)
            |> (static fn(string $v) => str_replace(".{$file->getExtension()}", '', $v))
            |> (static fn($v) => explode('/', $v))
            |> collect(...);

        $selectedDictionary = $this->makeBaseCollection($data, $base);
        $this->makeDictionary($selectedDictionary, $dictionary);
    }

    /**
     * @param Collection<string, Collection> $data
     * @param Collection<int, string> $base
     * @return Collection<string, Collection>
     */
    private function makeBaseCollection(Collection $data, Collection $base): Collection
    {
        $iteration = $base->shift();
        if ($iteration === null) {
            return $data;
        }

        return $data->hasKeys($iteration)
            ? $this->makeBaseCollection($data[$iteration], $base)
            : $this->makeBaseCollection($data[$iteration] = collect(), $base);
    }

    /**
     * @param Collection<string, Collection> $data
     * @param array $dictionary
     * @return void
     */
    private function makeDictionary(Collection $data, array $dictionary): void
    {
        foreach ($dictionary as $key => $value) {
            if (is_array($value) && !$data->hasKeys($key)) {
                $data[$key] = collect();
            }

            if (is_array($value)) {
                $this->makeDictionary($data[$key], $value);
            } else {
                $data[$key] = (string) $value;
            }
        }
    }
}