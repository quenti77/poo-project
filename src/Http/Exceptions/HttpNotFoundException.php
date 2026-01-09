<?php

namespace Tuto\Http\Exceptions;

use Throwable;
use Tuto\Http\Requests\Request;
use Tuto\Http\Responses\HttpCode;
use Tuto\Logger\LoggerLevel;

class HttpNotFoundException extends HttpException
{
    /**
     * @param Request $request
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        Request $request,
        int $code = 0,
        Throwable|null $previous = null,
    ) {
        $message = sprintf(
            "Route not found for method [%s] with uri '%s'",
            $request->method->value,
            $request->uri,
        );

        parent::__construct(HttpCode::NOT_FOUND, LoggerLevel::DEBUG, $request, $message, $code, $previous);
    }
}
