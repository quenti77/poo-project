<?php

namespace Tuto\Http\Exceptions;

use Tuto\Http\Responses\HttpCode;

interface HttpCodeInterface
{
    /**
     * @return HttpCode
     */
    public function getHttpCode(): HttpCode;
}