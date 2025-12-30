<?php

namespace Tuto\Console\Terminal\Actions;

trait CursorActionTrait
{
    use RenderActionTrait;

    public function forceShowCursor(): void
    {
        $this->enableRawMode();
        $this->showCursor();
        $this->disableRawMode();
    }

    /**
     * @return void
     */
    public function showCursor(): void
    {
        $this->renderCursorAction(Cursor::SHOW);
    }

    /**
     * @return void
     */
    public function hideCursor(): void
    {
        $this->renderCursorAction(Cursor::HIDE);
    }

    /**
     * @return void
     */
    public function saveCursorPosition(): void
    {
        $this->renderCursorAction(Cursor::SAVE);
    }

    public function clearCurrentLine(): void
    {
        $this->renderCursorAction(Cursor::CLEAR_LINE);
    }

    /**
     * @return void
     */
    public function restoreCursorPosition(): void
    {
        $this->renderCursorAction(Cursor::RESTORE);
    }
}
