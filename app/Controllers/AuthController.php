<?php

namespace App\Controllers;

use ReflectionException;
use Tuto\Http\Responses\RedirectResponse;
use Tuto\Http\Responses\ViewResponse;

class AuthController
{
    /**
     * @return ViewResponse
     * @throws ReflectionException
     */
    public function signIn(): ViewResponse
    {
        return view('auth/login.twig');
    }

    /**
     * @return RedirectResponse
     */
    public function login(): RedirectResponse
    {
        return redirect(router()->generate('auth.sign-in'));
    }
}
