<?php

namespace Tuto\CLI\Components;

use Tuto\CLI\Ansi;
use Tuto\CLI\Output;

class ListComponent
{
    private Output $out;
    private string $bullet;
    private string $color;
    private int $indent;

    public const string BULLET_DOT = "•";
    public const string BULLET_DASH = "-";
    public const string BULLET_STAR = "*";
    public const string BULLET_ARROW = "→";
    public const string BULLET_CHECK = "✓";
    public const string BULLET_CROSS = "✗";

    public function __construct(
        Output $out,
        string $bullet = self::BULLET_DOT,
        string $color = Ansi::FG_CYAN,
        int $indent = 2
    ) {
        $this->out = $out;
        $this->bullet = $bullet;
        $this->color = $color;
        $this->indent = $indent;
    }

    /**
     * Display an unordered list
     * @param array<string> $items
     */
    public function unordered(array $items): void
    {
        foreach ($items as $item) {
            $prefix = str_repeat(' ', $this->indent);
            $styledBullet = $this->color . $this->bullet . Ansi::RESET;
            $this->out->writeln("{$prefix}{$styledBullet} {$item}");
        }
    }

    /**
     * Display an ordered list
     * @param array<string> $items
     */
    public function ordered(array $items): void
    {
        foreach ($items as $index => $item) {
            $number = $index + 1;
            $prefix = str_repeat(' ', $this->indent);
            $styledNumber = $this->color . $number . '.' . Ansi::RESET;
            $this->out->writeln("{$prefix}{$styledNumber} {$item}");
        }
    }

    /**
     * Display a nested list
     * @param array<string, string|array> $items
     */
    public function nested(array $items, int $level = 0): void
    {
        $baseIndent = $this->indent + ($level * 4);

        foreach ($items as $key => $value) {
            $prefix = str_repeat(' ', $baseIndent);
            $styledBullet = $this->color . $this->bullet . Ansi::RESET;

            if (is_array($value)) {
                $this->out->writeln("{$prefix}{$styledBullet} {$key}");
                $this->nested($value, $level + 1);
            } else {
                $this->out->writeln("{$prefix}{$styledBullet} {$value}");
            }
        }
    }
}
