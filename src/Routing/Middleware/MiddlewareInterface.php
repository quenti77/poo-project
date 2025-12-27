<?php

namespace Tuto\Routing\Middleware;

use Tuto\Http\Requests\Request;

interface MiddlewareInterface
{
    /**
     * @param Request $request
     * @param callable(Request, callable): mixed $next
     * @return mixed
     */
    public function handle(Request $request, callable $next): mixed;
}