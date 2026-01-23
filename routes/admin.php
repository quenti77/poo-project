<?php

use Tuto\Routing\Router;

router()->group([
    'prefix' => '{lang?}/admin',
    'where' => ['lang' => '[a-z]{2}-[A-Z]{2}'],
    'name' => 'admin.',
    // 'middleware' => [],
], static function (Router $router) {
    
});

