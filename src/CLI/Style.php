<?php

namespace Tuto\CLI;

class Style
{
    private string $prefix = "";
    private string $suffix = Ansi::RESET;

    public static function create(): self
    {
        return new self();
    }

    public static function success(): self
    {
        return self::create()->fgStandard(Ansi::FG_GREEN);
    }

    public static function error(): self
    {
        return self::create()->fgStandard(Ansi::FG_RED)->bold();
    }

    public static function warning(): self
    {
        return self::create()->fgStandard(Ansi::FG_YELLOW);
    }

    public static function info(): self
    {
        return self::create()->fgStandard(Ansi::FG_CYAN);
    }

    public static function comment(): self
    {
        return self::create()->dim();
    }

    public function bold(): self
    {
        $this->prefix .= Ansi::BOLD;
        return $this;
    }

    public function underline(): self
    {
        $this->prefix .= Ansi::UNDERLINE;
        return $this;
    }

    public function dim(): self
    {
        $this->prefix .= Ansi::DIM;
        return $this;
    }

    public function invert(): self
    {
        $this->prefix .= Ansi::INVERT;
        return $this;
    }

    public function fg(int $r, int $g, int $b): self
    {
        $this->prefix .= Ansi::fgRgb($r, $g, $b);
        return $this;
    }

    public function bg(int $r, int $g, int $b): self
    {
        $this->prefix .= Ansi::bgRgb($r, $g, $b);
        return $this;
    }

    public function fg256(int $code): self
    {
        $this->prefix .= Ansi::fg256($code);
        return $this;
    }

    public function bg256(int $code): self
    {
        $this->prefix .= Ansi::bg256($code);
        return $this;
    }

    public function fgStandard(string $code): self
    {
        $this->prefix .= $code;
        return $this;
    }

    public function bgStandard(string $code): self
    {
        $this->prefix .= $code;
        return $this;
    }

    public function apply(string $text): string
    {
        return $this->prefix . $text . $this->suffix;
    }
}
