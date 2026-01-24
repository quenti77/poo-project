<?php

namespace App\Controllers;

use App\Validators\LoginValidator;
use ReflectionException;
use Tuto\Http\Requests\Request;
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
     * @param Request $request
     * @return RedirectResponse
     */
    public function login(Request $request): RedirectResponse
    {
        $validator = LoginValidator::fromRequest($request);
        $validated = $validator->validated();
        if ($validator->errors()->isEmpty() === false) {
        }

        $username = $validated->get('login-username');
        $password = $validated->get('login-password');

        return redirect(router()->generate('auth.sign-in'));
    }
}
