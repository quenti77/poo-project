<?php

namespace Tuto\Http\Exceptions;

use RuntimeException;
use Throwable;
use Tuto\Http\Requests\Request;
use Tuto\Http\Responses\HttpCode;
use Tuto\Logger\LoggerLevel;
use Tuto\Logger\LoggerLevelInterface;

class HttpException extends RuntimeException implements HttpCodeInterface, LoggerLevelInterface
{
    /**
     * @param HttpCode $httpCode
     * @param LoggerLevel $loggerLevel
     * @param Request $request
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        protected readonly HttpCode $httpCode,
        protected readonly LoggerLevel $loggerLevel,
        protected readonly Request $request,
        string $message = '',
        int $code = 0,
        Throwable|null $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return HttpCode
     */
    public function getHttpCode(): HttpCode
    {
        return $this->httpCode;
    }

    /**
     * @return LoggerLevel
     */
    public function getLoggerLevel(): LoggerLevel
    {
        return $this->loggerLevel;
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }
}