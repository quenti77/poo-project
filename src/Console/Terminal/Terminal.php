<?php

namespace Tuto\Console\Terminal;

class Terminal
{
    use InteractiveTerminalTrait;

    public function __construct()
    {
        $this->ansiEnabled = $this->getAnsiEnabled();
        $this->updateTerminalSize();
        $this->forceShowCursor();
    }

    public function __destruct()
    {
        $this->forceShowCursor();
    }

    /**
     * @return bool
     */
    private function getAnsiEnabled(): bool
    {
        if (PHP_SAPI !== 'cli') {
            return false;
        }

        if (!$this->isWindows()) {
            return true;
        }
        if (!function_exists('sapi_windows_vt100_support')) {
            return false;
        }
        return sapi_windows_vt100_support(STDOUT, true) && sapi_windows_vt100_support(STDERR, true);
    }
}
