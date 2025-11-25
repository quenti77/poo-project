<?php

use App\Commands\MigrateUpCommand;
use Tuto\CLI\ConsoleApplication;
use Tuto\CLI\Input;
use Tuto\CLI\Output;

define('ROOT', realpath(__DIR__));

require ROOT . '/src/Base/Autoloader.php';
require ROOT . '/src/Utils/functions.php';

$app = new ConsoleApplication(ROOT . '/config', new Output());
$app->addCommand(MigrateUpCommand::class);

exit($app->run(Input::fromArgv($argv ?? $_SERVER['argv'] ?? [])));
