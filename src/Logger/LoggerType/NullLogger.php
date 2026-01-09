<?php

namespace Tuto\Logger\LoggerType;

use Tuto\Logger\BaseLogger;
use Tuto\Logger\LoggerLevel;

class NullLogger extends BaseLogger
{
    /**
     * @param LoggerLevel $level
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function write(LoggerLevel $level, string $message, array $context = []): void
    {
        // Nothing ...
    }
}
