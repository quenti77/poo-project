<?php

namespace Tuto\Console\Terminal\Actions;

trait KeyActionTrait
{
    use RenderActionTrait;

    /**
     * @param int $lines
     * @return void
     */
    public function moveUp(int $lines = 1): void
    {
        $this->renderCursorAction(Key::KEY_UP, ['n' => $lines]);
    }

    /**
     * @param int $lines
     * @return void
     */
    public function moveDown(int $lines = 1): void
    {
        $this->renderCursorAction(Key::KEY_DOWN, ['n' => $lines]);
    }

    /**
     * @param int $columns
     * @return void
     */
    public function moveLeft(int $columns = 1): void
    {
        $this->renderCursorAction(Key::KEY_LEFT, ['n' => $columns]);
    }

    /**
     * @param int $columns
     * @return void
     */
    public function moveRight(int $columns = 1): void
    {
        $this->renderCursorAction(Key::KEY_RIGHT, ['n' => $columns]);
    }

    /**
     * @param int $line
     * @param int $column
     * @return void
     */
    public function moveTo(int $line, int $column): void
    {
        $this->renderCursorAction(Key::KEY_SET_POSITION, ['l' => $line, 'c' => $column]);
    }
}
