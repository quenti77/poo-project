<?php
/**
 * @var Router $router
 */

use App\Controllers\HomeController;
use App\Controllers\UsersController;
use Tuto\Routing\Router;

$router->get('', [HomeController::class, 'index'], 'home');
$router->get('/users', [UsersController::class, 'index'], 'users.index');
$router->get('/users/{userId}', [UsersController::class, 'show'], 'users.show');
