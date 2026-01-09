<?php

namespace Tuto\Base;

use InvalidArgumentException;
use RuntimeException;
use Tuto\Collections\Collection;

class Environment
{
    private Collection $fields;

    /**
     * @param array<string, string> $fields
     */
    public function __construct(array $fields = [])
    {
        $this->fields = collect();
        foreach ($fields as $key => $value) {
            $this->fields[$key] = $this->processValue($value);
        }
    }

    /**
     * Load a new file, merge the existing fields with this file
     *
     * @param string $path
     * @return void
     */
    public function load(string $path): void
    {
        if (!is_file($path)) {
            throw new InvalidArgumentException("'{$path}' is not a valid file");
        }

        $handler = fopen($path, 'rb');
        if (!$handler) {
            throw new RuntimeException("'{$path}' can not be opened");
        }

        while (($line = fgets($handler)) !== false) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            if (in_array($line[0], [';', '#'])) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            putenv("{$key}={$value}");

            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            $this->fields[$key] = $this->processValue($value);
            $_ENV[$key] = $value;
        }
    }

    /**
     * Get a value of environment variable
     *
     * @param string $key
     * @param bool|float|int|string|null $defaultValue
     * @return bool|float|int|string|null
     */
    public function get(string $key, bool|float|int|string|null $defaultValue = null): bool|float|int|string|null
    {
        if ($this->fields->offsetExists($key)) {
            return $this->fields[$key];
        }

        $env = getenv($key);
        $env = $env === false ? $defaultValue : $this->processValue($env);

        return $this->fields[$key] = $env;
    }

    /**
     * Convert a string value to a valid type
     *
     * @param string $value
     * @return bool|float|int|string|null
     */
    private function processValue(string $value): bool|float|int|string|null
    {
        if ($value === 'true' || $value === 'false') {
            return $value === 'true';
        }
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }
        if ($value === 'null') {
            return null;
        }
        return $value;
    }
}
