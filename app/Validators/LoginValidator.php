<?php

namespace App\Validators;

use Tuto\Validators\RequestValidator;
use Tuto\Validators\Rules\RequiredRule;

class LoginValidator extends RequestValidator
{
    /**
     * @return array
     */
    protected function rules(): array
    {
        return [
            'login-username' => [new RequiredRule()],
            'login-password' => [new RequiredRule()],
        ];
    }
}
