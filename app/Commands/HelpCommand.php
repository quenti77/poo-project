<?php

namespace App\Commands;

use Tuto\Base\Command;

class HelpCommand extends Command
{
    public string $name = 'help';
    public string $description = 'Help car c bien';

    /** @var Command[] $commands */
    private array $commands;

    /**
     * @param Command[] $commands
     * @return void
     */
    public function withCommands(array $commands): void
    {
        $this->commands = $commands;
    }

    public function arguments(): array
    {
        return [];
    }

    public function run(array $arguments): int
    {
        $maxSize = max(array_map(static fn (Command $command) => mb_strlen($command->name), $this->commands));
        $maxSize += 3;

        echo "Voici la liste des commandes disponibles\n\n";

        echo sprintf("\t  \e[1;32m%-{$maxSize}s\e[0m| \e[32m%s\e[0m\n", 'name', 'description');
        foreach ($this->commands as $command) {
            echo sprintf("\t- \e[1;32m%-{$maxSize}s\e[0m| \e[32m%s\e[0m\n", $command->name, $command->description);
        }

        echo "\n";
        return self::EXIT_SUCCESS;
    }
}