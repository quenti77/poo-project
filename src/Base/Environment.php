<?php

namespace Tuto\Base;

use InvalidArgumentException;
use RuntimeException;

class Environment
{
    private array $fields = [];

    public function __construct(array $fields = [])
    {
        foreach ($fields as $key => $value) {
            $this->fields[$key] = $this->processValue($value);
        }
    }

    public function load(string $path): void
    {
        if (!is_file($path)) {
            throw new InvalidArgumentException("'{$path}' is not a valid file");
        }

        $handler = fopen($path, 'rb');
        if (!$handler) {
            throw new RuntimeException("'{$path}' cannot be opened");
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

            $this->fields[$key] = $value;
            $_ENV[$key] = $value;
        }
    }

    public function get(string $key, bool|int|float|string|null $defaultValue = null): bool|int|float|string|null
    {
        if (array_key_exists($key, $this->fields)) {
            return $this->fields[$key];
        }

        $env = getenv($key);
        $env = $env === false ? $defaultValue : $this->processValue($env);

        return $this->fields[$key] = $env;
    }

    private function processValue(string $value): mixed
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