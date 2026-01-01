<?php

use Tuto\Application\CliApplication;
use Tuto\Console\Commands\Database\DbSeedCommand;
use Tuto\Console\Commands\MaintenanceCommand;
use Tuto\Console\Commands\Migrate\MigrationDownCommand;
use Tuto\Console\Commands\Migrate\MigrationMakeCommand;
use Tuto\Console\Commands\Migrate\MigrationUpCommand;
use Tuto\Console\Commands\Queue\QueueWorkCommand;
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

// Database seeder
$cliApplication->addCommand(DbSeedCommand::class);

// Queue worker
$cliApplication->addCommand(QueueWorkCommand::class);

$cliApplication->boot();
