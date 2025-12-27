<?php

namespace Tuto\Http\Exceptions;

use Throwable;
use Tuto\Http\Requests\Request;
use Tuto\Http\Responses\HttpCode;
use Tuto\Logger\LoggerLevel;

class HttpUnauthorizedException extends HttpException
{
    /**
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        Request $request,
        string $message = '',
        int $code = 0,
        Throwable|null $previous = null,
    ) {
        parent::__construct(HttpCode::UNAUTHORIZED, LoggerLevel::WARNING, $request, $message, $code, $previous);
    }
}