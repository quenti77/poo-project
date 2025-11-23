<?php

use App\Commands\HelpCommand;
use App\Commands\MigrationUpCommand;
use Tuto\Base\Command;

define('ROOT', realpath(__DIR__));

require ROOT . '/src/Base/Autoloader.php';
require ROOT . '/src/Utils/functions.php';

$helpCommand = new HelpCommand();
$commands = [
    new MigrationUpCommand(),
    $helpCommand,
];
$helpCommand->withCommands($commands);

array_shift($argv);

$commandName = 'help';
if (!empty($argv)) {
    $commandName = array_shift($argv);
}

$commandNames = array_map(static fn (Command $command) => $command->name, $commands);
if (!in_array($commandName, $commandNames, true)) {
    $commandName = 'help';
    echo "\e[1;31mCommand non trouvé ! Commande '{$commandName}'\e[0m\n";
}

/** @var Command $currentCommand */
$currentCommand = array_find($commands, static fn (Command $command) => $command->name === $commandName);
exit($currentCommand->run($argv));
