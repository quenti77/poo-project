<?php

use App\Commands\MaintenanceCommand;
use App\Commands\Migrate\MigrationDownCommand;
use App\Commands\Migrate\MigrationMakeCommand;
use App\Commands\Migrate\MigrationUpCommand;
use Tuto\Application\CliApplication;
use Tuto\Console\Components\Input;
use Tuto\Console\Components\Output;
use Tuto\Console\Terminal\Terminal;

define('ROOT', realpath(__DIR__));

require ROOT . '/src/Base/Autoloader.php';
require ROOT . '/src/Utils/functions.php';

$input = Input::fromArgv($argv ?? $_SERVER['argv'] ?? []);
$output = new Output(new Terminal());

$cliApplication = new CliApplication($input, $output);
$cliApplication->load();

// Base commands
$cliApplication->addCommand(MaintenanceCommand::class);

// Migrate commands
$cliApplication->addCommand(MigrationMakeCommand::class);
$cliApplication->addCommand(MigrationUpCommand::class);
$cliApplication->addCommand(MigrationDownCommand::class);

$cliApplication->boot();
