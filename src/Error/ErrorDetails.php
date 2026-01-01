<?php

namespace Tuto\Error;

use JsonSerializable;
use Tuto\Http\Responses\HttpCode;
use Tuto\Logger\LoggerLevel;

class ErrorDetails implements JsonSerializable
{
    /**
     * @param LoggerLevel $loggerLevel
     * @param string $message
     * @param int $code
     * @param string $file
     * @param int $line
     * @param string $type
     * @param array $trace
     * @param HttpCode $httpCode
     */
    public function __construct(
        public readonly LoggerLevel $loggerLevel,
        public readonly string $message,
        public readonly int $code,
        public readonly string $file,
        public readonly int $line,
        public readonly string $type,
        public readonly array $trace = [],
        public readonly HttpCode $httpCode = HttpCode::INTERNAL_SERVER_ERROR,
    ) {
    }

    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'code' => $this->code,
            'file' => $this->file,
            'line' => $this->line,
            'type' => $this->type,
            'trace' => $this->formatTrace(),
        ];
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return array
     */
    public function formatTrace(): array
    {
        return array_map(
            static fn ($trace) => [
                'file' => $trace['file'] ?? 'unknown',
                'line' => $trace['line'] ?? 0,
                'function' => $trace['function'] ?? 'unknown',
                'class' => $trace['class'] ?? null,
            ],
            $this->trace,
        );
    }
}