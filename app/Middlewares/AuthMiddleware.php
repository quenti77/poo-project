<?php

namespace App\Middlewares;

use App\Entities\User;
use App\ValueObjects\Level;
use Tuto\Http\Exceptions\HttpUnauthorizedException;
use Tuto\Http\Requests\Request;
use Tuto\Middleware\MiddlewareInterface;
use Tuto\Template\Engine;

class AuthMiddleware implements MiddlewareInterface
{
    /** @var Level|null $minimumLevel */
    protected Level|null $minimumLevel = null;

    /**
     * @param Engine $engine
     */
    public function __construct(private readonly Engine $engine)
    {
    }

    /**
     * @param int $level
     * @return $this
     */
    public function withMinimumLevel(int $level = 0): static
    {
        $this->minimumLevel = new Level($level);
        return $this;
    }

    /**
     * @param Request $request
     * @param callable(Request): mixed $next
     * @return mixed
     */
    public function handle(Request $request, callable $next): mixed
    {
        /** @var User|null $user */
        $user = $request->session->get('user');
        if ($user === null) {
            throw new HttpUnauthorizedException($request, "User must be connected");
        }

        if ($this->minimumLevel && !$user->getGroup()->getLevel()->canAccess($this->minimumLevel)) {
            throw new HttpUnauthorizedException($request, "User must needed more permissions");
        }

        $this->engine->addGlobalVariable('user', $user);

        return $next($request);
    }
}