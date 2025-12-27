<?php

namespace Tuto\Logger\LoggerType;

use JsonException;
use Tuto\Logger\BaseLogger;
use Tuto\Logger\LoggerLevel;

class SyslogLogger extends BaseLogger
{
    /**
     * @param string $ident
     * @param LoggerLevel $minLevel
     * @param string $environment
     */
    public function __construct(
        private readonly string $ident = 'app',
        LoggerLevel $minLevel = LoggerLevel::DEBUG,
        string $environment = 'local',
    ) {
        parent::__construct($minLevel, $environment);

        openlog($this->ident, LOG_PID | LOG_PERROR, LOG_USER);
    }

    public function __destruct()
    {
        closelog();
    }

    /**
     * @param LoggerLevel $level
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function write(LoggerLevel $level, string $message, array $context = []): void
    {
        $priority = $this->levelToPriority($level);

        $data = '';
        try {
            $data = !empty($context) ? json_encode($context, JSON_THROW_ON_ERROR) : '';
        } catch (JsonException) {
            $data = '';
        }

        syslog($priority, "{$message} {$data}");
    }

    /**
     * @param LoggerLevel $level
     * @return int
     */
    private function levelToPriority(LoggerLevel $level): int
    {
        return match ($level) {
            LoggerLevel::EMERGENCY => LOG_EMERG,
            LoggerLevel::ALERT => LOG_ALERT,
            LoggerLevel::CRITICAL => LOG_CRIT,
            LoggerLevel::ERROR => LOG_ERR,
            LoggerLevel::WARNING => LOG_WARNING,
            LoggerLevel::NOTICE => LOG_NOTICE,
            LoggerLevel::INFO => LOG_INFO,
            LoggerLevel::DEBUG => LOG_DEBUG,
        };
    }
}
