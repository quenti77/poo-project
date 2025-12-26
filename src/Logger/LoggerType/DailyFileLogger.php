<?php

namespace Tuto\Logger\LoggerType;

use RuntimeException;
use Tuto\Logger\BaseLogger;
use Tuto\Logger\LoggerLevel;

class DailyFileLogger extends BaseLogger
{
    /**
     * @param string $logDirectory
     * @param string $filePrefix
     * @param LoggerLevel $minLevel
     */
    public function __construct(
        private readonly string $logDirectory,
        private readonly string $filePrefix = 'app',
        LoggerLevel $minLevel = LoggerLevel::DEBUG,
    ) {
        parent::__construct($minLevel);
    }

    /**
     * @param LoggerLevel $level
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function write(LoggerLevel $level, string $message, array $context = []): void
    {
        $formattedMessage = $this->formatMessage($level, $message, $context);

        $filename = sprintf('%s_%s.log', $this->filePrefix, date('Y_m_d'));
        $filePath = $this->logDirectory . DIRECTORY_SEPARATOR . $filename;

        $this->assertDirectoryExist();
        file_put_contents($filePath, $formattedMessage . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * @return void
     */
    private function assertDirectoryExist(): void
    {
        if (is_dir($this->logDirectory)) {
            return;
        }
        if (!mkdir($dir = $this->logDirectory, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }
    }
}