<?php

use App\Commands\MaintenanceCommand;
use Tuto\Application\CliApplication;
use Tuto\CLI\Input\Input;
use Tuto\CLI\Output\Output;
use Tuto\CLI\Terminal;

define('ROOT', realpath(__DIR__));

require ROOT . '/src/Base/Autoloader.php';
require ROOT . '/src/Utils/functions.php';

$input = Input::fromArgv($argv ?? $_SERVER['argv'] ?? []);
$output = new Output(new Terminal());

$cliApplication = new CliApplication($input, $output);
$cliApplication->addCommand(MaintenanceCommand::class);

$cliApplication->boot();
