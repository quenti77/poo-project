<?php

namespace Tuto\Console\Components\Output\Borders;

class SimpleBorder implements BorderInterface
{

    /**
     * @return string
     */
    public function horizontal(): string
    {
        return '─';
    }

    /**
     * @return string
     */
    public function vertical(): string
    {
        return '│';
    }

    /**
     * @return string
     */
    public function topLeft(): string
    {
        return '┌';
    }

    /**
     * @return string
     */
    public function topRight(): string
    {
        return '┐';
    }

    /**
     * @return string
     */
    public function bottomLeft(): string
    {
        return '└';
    }

    /**
     * @return string
     */
    public function bottomRight(): string
    {
        return '┘';
    }

    /**
     * @return string
     */
    public function leftT(): string
    {
        return '├';
    }

    /**
     * @return string
     */
    public function rightT(): string
    {
        return '┤';
    }

    /**
     * @return string
     */
    public function topT(): string
    {
        return '┬';
    }

    /**
     * @return string
     */
    public function bottomT(): string
    {
        return '┴';
    }

    /**
     * @return string
     */
    public function cross(): string
    {
        return '┼';
    }
}
