<?php

namespace Tuto\CLI;

use Tuto\Collection\Collection;

class Input
{
    private string|null $scriptName;
    private string|null $commandName;

    /** @var Collection<int, string> $arguments */
    private Collection $arguments;

    /** @var Collection<string, bool|string> $options */
    private Collection $options;

    private function __construct(Collection $argv)
    {
        $this->scriptName = $argv[0] ?? null;
        $this->commandName = $argv[1] ?? null;

        $this->arguments = collect();
        $this->options = collect();

        $rest = $argv->slice(2);
        foreach ($rest as $item) {
            if (str_starts_with($item, '--')) {
                // --key or --key= or --key=value
                $pair = substr($item, 2);

                [$k, $v] = explode('=', $pair, 2) + [null, null];
                $v = trim($v ?? '');

                $this->options[$k] = empty($v) ? true : $v;
            } elseif (str_starts_with($item, '-')) {
                $flags = str_split(substr($item, 1));

                foreach ($flags as $flag) {
                    $this->options[$flag] = true;
                }
            } else {
                $this->arguments->push($item);
            }
        }
    }

    public static function fromArgv(array $argv): self
    {
        return new self(collect($argv));
    }

    public function getScriptName(): string|null
    {
        return $this->scriptName;
    }

    public function getCommandName(): string|null
    {
        return $this->commandName;
    }

    public function getArgument(int $index, string|null $default = null): string|null
    {
        return $this->arguments[$index] ?? $default;
    }

    public function hasOption(string $name): bool
    {
        return $this->options->offsetExists($name);
    }

    public function getOption(string $name, string|bool|null $default = null): string|bool|null
    {
        return $this->options[$name] ?? $default;
    }
}