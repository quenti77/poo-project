<?php

namespace Tuto\Error;

use Throwable;
use Tuto\Http\Exceptions\HttpCodeInterface;
use Tuto\Http\Responses\HttpCode;
use Tuto\Logger\LoggerLevel;
use Tuto\Logger\LoggerLevelInterface;

class ErrorFactory
{
    private const array ERROR_TYPE = [
        E_ERROR => 'E_ERROR',
        E_WARNING => 'E_WARNING',
        E_PARSE => 'E_PARSE',
        E_NOTICE => 'E_NOTICE',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_CORE_WARNING => 'E_CORE_WARNING',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR => 'E_USER_ERROR',
        E_USER_WARNING => 'E_USER_WARNING',
        E_USER_NOTICE => 'E_USER_NOTICE',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED',
    ];

    /**
     * @param Throwable $throwable
     * @param LoggerLevel $loggerLevel
     * @param HttpCode $httpCode
     * @return ErrorDetails
     */
    public static function fromThrowable(
        Throwable $throwable,
        LoggerLevel $loggerLevel = LoggerLevel::CRITICAL,
        HttpCode $httpCode = HttpCode::INTERNAL_SERVER_ERROR,
    ): ErrorDetails {
        if ($throwable instanceof LoggerLevelInterface) {
            $loggerLevel = $throwable->getLoggerLevel();
        }
        if ($throwable instanceof HttpCodeInterface) {
            $httpCode = $throwable->getHttpCode();
        }

        return new ErrorDetails(
            loggerLevel: $loggerLevel,
            message: $throwable->getMessage(),
            code: $throwable->getCode(),
            file: $throwable->getFile(),
            line: $throwable->getLine(),
            type: $throwable::class,
            trace: $throwable->getTrace(),
            httpCode: $httpCode,
        );
    }

    /**
     * @param LoggerLevel $loggerLevel
     * @param string $message
     * @param int $code
     * @param string $file
     * @param int $line
     * @return ErrorDetails
     */
    public static function fromError(
        LoggerLevel $loggerLevel,
        string $message,
        int $code,
        string $file,
        int $line,
    ): ErrorDetails {
        return new ErrorDetails(
            loggerLevel: $loggerLevel,
            message: $message,
            code: $code,
            file: $file,
            line: $line,
            type: self::ERROR_TYPE[$code] ?? 'UNKNOWN',
            trace: debug_backtrace(),
        );
    }
}
