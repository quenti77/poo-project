<?php

use Tuto\Routing\Router;

router()->group([
    'prefix' => '{lang?}/',
    'where' => ['lang' => '[a-z]{2}-[A-Z]{2}'],
], static function (Router $router) {
    // TODO: Route localization here
});
