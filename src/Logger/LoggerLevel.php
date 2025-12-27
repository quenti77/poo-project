<?php

namespace Tuto\Logger;

use InvalidArgumentException;
use Tuto\Utils\EasyEnum;

enum LoggerLevel: int
{
    use EasyEnum;

    case DEBUG = 1;
    case INFO = 2;
    case NOTICE = 3;
    case WARNING = 4;
    case ERROR = 5;
    case CRITICAL = 6;
    case ALERT = 7;
    case EMERGENCY = 8;

    public static function fromLabel(string $label): self
    {
        return match(strtolower($label)) {
            'debug' => self::DEBUG,
            'info' => self::INFO,
            'notice' => self::NOTICE,
            'warning' => self::WARNING,
            'error' => self::ERROR,
            'critical' => self::CRITICAL,
            'alert' => self::ALERT,
            'emergency' => self::EMERGENCY,
            default => throw new InvalidArgumentException("Unknown log level: {$label}"),
        };
    }

    /**
     * @return string
     */
    public function label(): string
    {
        return match($this) {
            self::DEBUG => 'debug',
            self::INFO => 'info',
            self::NOTICE => 'notice',
            self::WARNING => 'warning',
            self::ERROR => 'error',
            self::CRITICAL => 'critical',
            self::ALERT => 'alert',
            self::EMERGENCY => 'emergency',
        };
    }

    /**
     * @param LoggerLevel $minLevel
     * @return bool
     */
    public function canLog(LoggerLevel $minLevel): bool
    {
        return $this->value >= $minLevel->value;
    }
}
