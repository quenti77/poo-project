<?php

namespace Tuto\Logger;

interface LoggerLevelInterface
{
    /**
     * @return LoggerLevel
     */
    public function getLoggerLevel(): LoggerLevel;
}