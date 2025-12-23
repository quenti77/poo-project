<?php

define('ROOT', dirname(__DIR__));

require ROOT . '/src/Base/Autoloader.php';
require ROOT . '/src/Utils/functions.php';

app()->boot();
