<?php

namespace App\Providers;

use App\Entities\User;
use Tuto\Application\ServiceProvider\ServiceProviderInterface;
use Tuto\Template\Engine;
use Tuto\Translate\Translator;

class TemplateServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Engine $engine
     */
    public function __construct(
        private readonly Engine $engine,
        private readonly Translator $translator,
    ) {
    }

    /**
     * @return void
     */
    public function register(): void
    {
        $this->engine->addFunction('can', $this->can(...));
        $this->engine->addFunction('isAdmin', fn () => $this->can(30));

        $this->engine->addFunction('transUrl', fn(string $name, array $params = []) => $this->translatableUrl($name, $params, true));
        $this->engine->addFunction('transPath', fn(string $name, array $params = []) => $this->translatableUrl($name, $params, false));
    }

    /**
     * @param int $minLevel
     * @return bool
     */
    private function can(int $minLevel = 10): bool
    {
        /** @var User|null $user */
        $user = request()->session->get('user');
        if ($user === null) {
            return false;
        }
        return $user->getGroup()->getLevel()->canAccess($minLevel);
    }

    /**
     * @param string $name
     * @param array $params
     * @param bool $fullUri
     * @return string
     */
    private function translatableUrl(string $name, array $params, bool $fullUri): string
    {
        $locale = $this->translator->getCurrentLocale();
        $params = ['lang' => $locale->toBCP(), ...$params];

        return router()->generate($name, $params, $fullUri);
    }
}
