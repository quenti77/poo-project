<?php

namespace Tuto\Console\Terminal\Actions;

use BackedEnum;

trait RenderActionTrait
{
    /** @var bool $ansiEnabled */
    private bool $ansiEnabled = false;

    /**
     * @return bool
     */
    public function ansiEnabled(): bool
    {
        return $this->ansiEnabled;
    }

    /**
     * @param string|BackedEnum $action
     * @param array<string, string> $context
     * @return void
     */
    private function renderCursorAction(string|BackedEnum $action, array $context = []): void
    {
        if ($this->ansiEnabled) {
            $cursor = is_string($action) ? $action : $action->value;
            foreach ($context as $key => $value) {
                $cursor = str_replace("{{$key}}", $value, $cursor);
            }
            echo $cursor;
        }
    }
}