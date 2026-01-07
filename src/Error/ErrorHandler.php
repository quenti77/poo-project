<?php

namespace Tuto\Error;

use Throwable;
use Tuto\Http\Exceptions\HttpNotFoundException;
use Tuto\Http\Responses\ErrorResponse;
use Tuto\Http\Responses\HttpCode;
use Tuto\Logger\LoggerLevel;

class ErrorHandler
{
    /** @var bool $registered */
    private static bool $registered = false;

    /**
     * @var array<class-string<Throwable>>
     */
    protected static array $dontReport = [
        HttpNotFoundException::class,
    ];

    /**
     * @return void
     */
    public static function register(): void
    {
        if (static::$registered) {
            return;
        }

        set_error_handler([static::class, 'handleError']);
        set_exception_handler([static::class, 'handleException']);
        register_shutdown_function([static::class, 'handleShutdown']);

        static::$registered = true;
    }

    /**
     * @param int $code
     * @param string $message
     * @param string $file
     * @param int $line
     * @return bool
     */
    public static function handleError(int $code, string $message, string $file, int $line): bool
    {
        // Do not process error if we used '@'. Bad code if it's used
        if (!(error_reporting() & $code)) {
            return false;
        }

        $error = ErrorFactory::fromError(LoggerLevel::CRITICAL, $message, $code, $file, $line);
        static::renderError($error);

        return true;
    }

    /**
     * @param Throwable $throwable
     * @return void
     */
    public static function handleException(Throwable $throwable): void
    {
        $error = ErrorFactory::fromThrowable($throwable);
        static::renderError($error);
    }

    /**
     * @return void
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error === null) {
            return;
        }

        if (!in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            return;
        }

        $errorDetails = ErrorFactory::fromError(
            loggerLevel: LoggerLevel::EMERGENCY,
            message: $error['message'] ?? 'Unknown Error',
            code: $error['type'] ?? 0,
            file: $error['file'] ?? 'unknown file',
            line: $error['line'] ?? 0,
        );
        static::renderError($errorDetails);
    }

    /**
     * @param ErrorDetails $errorDetails
     * @return void
     */
    protected static function renderError(ErrorDetails $errorDetails): void
    {
        static::logError($errorDetails);

        // Clean output buffers
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        try {
            $response = new ErrorResponse($errorDetails);
            request()->cookies->export();
            $response->renderHeaders();
            echo $response->getBody();
        } catch (Throwable $exception) {
            // Call fallback
            static::logError(ErrorFactory::fromThrowable($exception, LoggerLevel::EMERGENCY));
            static::renderFallbackError($errorDetails, $exception);
        }
    }

    /**
     * @param ErrorDetails $errorDetails
     * @param Throwable $throwable
     * @return void
     */
    protected static function renderFallbackError(ErrorDetails $errorDetails, Throwable $throwable): void
    {
        http_response_code(HttpCode::INTERNAL_SERVER_ERROR->value);
        header('Content-Type: text/plain; charset=utf-8');

        echo "Internal Server Error\n";
        echo "Original Error: {$errorDetails->message}\n";
        echo "At: {$errorDetails->file} #{$errorDetails->line}\n\n";
        echo "Rendering Error: {$throwable->getMessage()}";
    }

    /**
     * @param ErrorDetails $errorDetails
     * @return void
     */
    protected static function logError(ErrorDetails $errorDetails): void
    {
        if (!static::shouldReport($errorDetails)) {
            return;
        }

        try {
            logger()->log($errorDetails->loggerLevel, $errorDetails->message, $errorDetails->toArray());
        } catch (Throwable) {
            // Ignore errors can be throws
        }
    }

    /**
     * @param ErrorDetails $errorDetails
     * @return bool
     */
    protected static function shouldReport(ErrorDetails $errorDetails): bool
    {
        return !array_any(static::$dontReport, static fn($type) => $errorDetails->type === $type);
    }
}