<?php

use App\Controllers\AuthController;
use Tuto\Routing\Router;

router()->group([
    'prefix' => '{lang?}/',
    'where' => ['lang' => '[a-z]{2}-[A-Z]{2}'],
], static function (Router $router) {

    $router->get('sign-in', [AuthController::class, 'signIn'], 'auth.sign-in');
    $router->post('login', [AuthController::class, 'login'], 'auth.login');

    $router->get('', static function () {
        return view('layouts/front.twig');
    });

    $router->group([
        'prefix' => 'posts/',
        'name' => 'posts.'
    ], static function (Router $router) {
    });

    $router->group([
        'prefix' => 'comments/',
        'name' => 'comments.'
    ], static function (Router $router) {
    });

});
