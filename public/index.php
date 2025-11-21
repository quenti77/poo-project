<?php

define('ROOT', dirname(__DIR__));

require ROOT . '/src/Base/Autoloader.php';
require ROOT . '/src/Utils/functions.php';

$app = null;
try {
    $app = app();
    $response = $app->run(request());
    $app->render($response);
} catch (JsonException|ReflectionException $exception) {
    /** @noinspection PhpUnhandledExceptionInspection */
    $app?->render(json([
        'error' => true,
        'message' => 'An error occurred during request',
        'details' => $exception->getMessage(),
    ], 500));
}