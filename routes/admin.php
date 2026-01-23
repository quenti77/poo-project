<?php

use App\Controllers\AdminDashboardController;
use App\Middlewares\AuthMiddleware;
use Tuto\Routing\Router;

router()->group([
    'prefix' => '{lang?}/admin',
    'where' => ['lang' => '[a-z]{2}-[A-Z]{2}'],
    'name' => 'admin.',
    'middleware' => [container(AuthMiddleware::class)->withMinimumLevel(30)],
], static function (Router $router) {

    $router->get('dashboard', [AdminDashboardController::class, 'dashboard'], 'dashboard');

});

