<?php

namespace Tuto\Utils;

use ReflectionException;
use Tuto\Error\ErrorFactory;
use Tuto\Utils\Dump\DumpHelper;
use Tuto\Utils\Dump\DumpInterface;
use Tuto\Utils\Dump\VarType;

final class VarDumpRender
{
    use DumpHelper;

    public static bool $withScript = true;

    /**
     * @param int $deepPosition
     */
    public function __construct(private readonly int $deepPosition = 3)
    {
    }

    /**
     * @param mixed ...$vars
     * @return void
     */
    public function render(mixed ...$vars): void
    {
        if (container()->getWithoutError('app.debug', false) === false) {
            logger()->warning('dump found during script running');
            return;
        }

        $varDump = new VarDump($this->deepPosition);
        $render = $varDump->dump(...$vars);

        try {
            $view = view('dump', ['render' => $render, 'withScript' => self::$withScript], null);
        } catch (ReflectionException $exception) {
            logger()->error('Generate view for dump impossible', ErrorFactory::fromThrowable($exception)->toArray());
            /** @noinspection ForgottenDebugOutputInspection */
            var_dump(...$vars);
            return;
        }

        echo $view->getBody();
        self::$withScript = false;
    }
}
