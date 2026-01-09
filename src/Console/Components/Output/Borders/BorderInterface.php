<?php

namespace Tuto\Console\Components\Output\Borders;

interface BorderInterface
{
    /**
     * @return string
     */
    public function horizontal(): string;

    /**
     * @return string
     */
    public function vertical(): string;

    /**
     * @return string
     */
    public function topLeft(): string;

    /**
     * @return string
     */
    public function topRight(): string;

    /**
     * @return string
     */
    public function bottomLeft(): string;

    /**
     * @return string
     */
    public function bottomRight(): string;

    /**
     * @return string
     */
    public function leftT(): string;

    /**
     * @return string
     */
    public function rightT(): string;

    /**
     * @return string
     */
    public function topT(): string;

    /**
     * @return string
     */
    public function bottomT(): string;

    /**
     * @return string
     */
    public function cross(): string;
}
