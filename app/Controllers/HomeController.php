<?php

namespace App\Controllers;

use ReflectionException;
use Tuto\Http\Response\ViewResponse;

class HomeController
{
    /**
     * @throws ReflectionException
     */
    public function index(): ViewResponse
    {
        return view('home');
    }
}