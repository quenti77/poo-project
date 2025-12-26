<?php

use App\Infrastructure\Controllers\TestController;

router()->get('', [TestController::class, 'index'], 'home.index');
