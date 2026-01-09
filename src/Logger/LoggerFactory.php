<?php

namespace Tuto\Logger;

use ReflectionException;
use Tuto\Container\DependencyInjectionContainer;
use Tuto\Logger\LoggerType\DailyFileLogger;
use Tuto\Logger\LoggerType\SyslogLogger;

class LoggerFactory
{
    public static function make(DependencyInjectionContainer $container): LoggerInterface
    {
        $driver = self::getValue($container, 'logger.driver', 'daily');
        $level = self::getValue($container, 'logger.min_level', LoggerLevel::DEBUG);
        $identifier = self::getValue($container, 'logger.identifier', 'app');
        $path = self::getValue($container, 'logger.path', 'storage/logs');
        $environment = self::getValue($container, 'app.env', 'local');

        $logLevel = LoggerLevel::fromLabel($level);

        if (!str_starts_with($path, '/')) {
            $path = ROOT . "/{$path}";
        }

        return match ($driver) {
            'daily' => new DailyFileLogger($path, $identifier, $logLevel, $environment),
            'syslog' => new SyslogLogger($identifier, $logLevel, $environment),
        };
    }

    /**
     * @param DependencyInjectionContainer $container
     * @param string $key
     * @param mixed $defaultValue
     * @return mixed
     */
    private static function getValue(DependencyInjectionContainer $container, string $key, mixed $defaultValue): mixed
    {
        try {
            return $container->get($key);
        } catch (ReflectionException) {
            return $defaultValue;
        }
    }
}
