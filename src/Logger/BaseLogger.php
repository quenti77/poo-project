<?php

namespace Tuto\Logger;

abstract class BaseLogger implements LoggerInterface
{
    /**
     * @param LoggerLevel $minLevel
     */
    public function __construct(protected LoggerLevel $minLevel = LoggerLevel::DEBUG)
    {
    }

    /**
     * @param LoggerLevel $level
     * @param string $message
     * @param array $context
     * @return void
     */
    abstract protected function write(LoggerLevel $level, string $message, array $context = []): void;

    /**
     * @param LoggerLevel $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public function log(LoggerLevel $level, string $message, array $context = []): void
    {
        if ($level->canLog($this->minLevel)) {
            $this->write($level, $message, $context);
        }
    }

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function emergency(string $message, array $context = []): void
    {
        $this->log(LoggerLevel::EMERGENCY, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function alert(string $message, array $context = []): void
    {
        $this->log(LoggerLevel::ALERT, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log(LoggerLevel::CRITICAL, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(LoggerLevel::ERROR, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(LoggerLevel::WARNING, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function notice(string $message, array $context = []): void
    {
        $this->log(LoggerLevel::NOTICE, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(LoggerLevel::INFO, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(LoggerLevel::DEBUG, $message, $context);
    }

    /**
     * @param LoggerLevel $level
     * @param string $message
     * @param array $context
     * @return string
     */
    protected function formatMessage(LoggerLevel $level, string $message, array $context = []): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context) : '';
        return sprintf('[%s] %s: %s %s', $timestamp, strtoupper($level->label()), $message, $contextStr);
    }
}