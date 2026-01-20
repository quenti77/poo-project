<?php

use Tuto\Routing\Router;

router()->group([
    'prefix' => '{lang?}/',
    'where' => ['lang' => '[a-z]{2}-[A-Z]{2}'],
], static function (Router $router) {

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

    $router->group([
        'prefix' => 'admin/',
        'name' => 'admin.',
        // 'middleware' => [],
    ], static function (Router $router) {
    });

});
