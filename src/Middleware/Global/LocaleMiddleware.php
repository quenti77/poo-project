<?php

namespace Tuto\Middleware\Global;

use DateTimeImmutable;
use Tuto\Http\Components\Cookie;
use Tuto\Http\Requests\HttpMethod;
use Tuto\Http\Requests\Request;
use Tuto\Middleware\MiddlewareInterface;
use Tuto\Translate\Locale;
use Tuto\Translate\Translator;

class LocaleMiddleware implements MiddlewareInterface
{
    /**
     * @param Translator $translator
     */
    public function __construct(private readonly Translator $translator)
    {
    }

    /**
     * @param Request $request
     * @param callable(Request): mixed $next
     * @return mixed
     */
    public function handle(Request $request, callable $next): mixed
    {
        $fallbackLocale = $this->translator->getFallback();

        $currentLocale = $this->findLocaleInString($request->uri->path);
        if ($currentLocale) {
            $this->updateCookie($request, $currentLocale);
            $this->translator->updateCurrentLocale($currentLocale);
            return $next($request);
        }

        $currentLocale = $this->findLocaleInCookie($request);
        if ($currentLocale === null) {
            $currentLocale = $fallbackLocale;
            $this->updateCookie($request, $currentLocale);
        }

        $currentRoute = $request->getCurrentRoute();
        $currentUri = "/{$currentLocale}{$request->uri->getCompletePath()}";
        if ($currentRoute === null) {
            return redirect($currentUri);
        }

        if ($currentRoute->getMethod() !== HttpMethod::GET) {
            $this->translator->updateCurrentLocale($currentLocale);
            return $next($request);
        }

        if ($currentRoute->getName() === null) {
            return redirect($currentUri);
        }

        $matches = $currentRoute->getMatches();
        $matches['lang'] = $currentLocale->toBCP();

        return redirect(router()->generate($currentRoute->getName(), $matches->all()));
    }

    private function findLocaleInCookie(Request $request): Locale|null
    {
        $cookieName = container()->getWithoutError('locale.cookie_name', 'locale.cookie_name');
        if (!$request->cookies->offsetExists($cookieName)) {
            return null;
        }

        /** @var Cookie $cookie */
        $cookie = $request->cookies[$cookieName];
        return $this->findLocaleInString($cookie->value);
    }

    /**
     * @param string $value
     * @return Locale|null
     */
    private function findLocaleInString(string $value): Locale|null
    {
        foreach ($this->translator->getLocales() as $locale) {
            if (str_contains($value, $locale->toBCP())) {
                return $locale;
            }
            if (str_contains($value, $locale->toPOSIX())) {
                return $locale;
            }
        }
        return null;
    }

    private function updateCookie(Request $request, Locale $locale): void
    {
        $cookieName = container()->getWithoutError('locale.cookie_name', 'locale.cookie_name');
        $cookie = new Cookie(
            name: $cookieName,
            value: $locale,
            expiredAt: new DateTimeImmutable('+1 month'),
            path: '/',
            domain: '',
            secure: true,
            httpOnly: true,
        );
        $request->cookies->offsetSet($cookieName, $cookie);
    }
}