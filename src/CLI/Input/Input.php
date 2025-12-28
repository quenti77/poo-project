<?php

namespace Tuto\CLI\Input;

use Tuto\Collections\Collection;

class Input
{
    private string|null $scriptName;
    private string|null $commandName;

    /** @var Collection<int, string> $arguments */
    private Collection $arguments;

    /** @var Collection<string, bool|string> $options */
    private Collection $options;

    /**
     * @param Collection<int, string> $argv
     */
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
                // -a -b or -ab
                $flags = str_split(substr($item, 1));

                foreach ($flags as $flag) {
                    $this->options[$flag] = true;
                }
            } else {
                $this->arguments->push($item);
            }
        }
    }

    /**
     * @param array $argv
     * @return self
     */
    public static function fromArgv(array $argv): self
    {
        return new self(collect($argv));
    }

    /**
     * @return string|null
     */
    public function getScriptName(): string|null
    {
        return $this->scriptName;
    }

    /**
     * @return string|null
     */
    public function getCommandName(): string|null
    {
        return $this->commandName;
    }

    /**
     * @param int $index
     * @param string|null $default
     * @return string|null
     */
    public function getArgument(int $index, string|null $default = null): string|null
    {
        return $this->arguments[$index] ?? $default;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasOption(string $name): bool
    {
        return $this->options->offsetExists($name);
    }

    /**
     * @param string $name
     * @param string|bool|null $default
     * @return string|bool|null
     */
    public function getOption(string $name, string|bool|null $default = null): string|bool|null
    {
        return $this->options[$name] ?? $default;
    }
}