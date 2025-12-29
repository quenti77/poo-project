<?php

use Random\RandomException;
use Tuto\Application\HttpApplication;
use Tuto\Base\Environment;
use Tuto\Collections\Collection;
use Tuto\Container\DependencyInjectionContainer;
use Tuto\Container\Resolver;
use Tuto\Http\Requests\Request;
use Tuto\Http\Responses\HttpCode;
use Tuto\Http\Responses\JsonResponse;
use Tuto\Http\Responses\RedirectResponse;
use Tuto\Http\Responses\ViewResponse;
use Tuto\Logger\LoggerInterface;
use Tuto\Logger\LoggerType\NullLogger;
use Tuto\Routing\Router;
use Tuto\Translate\Translator;

if (!function_exists('collect')) {
    /**
     * @template TKey of array-key
     * @template-covariant TValue
     *
     * @param array<TKey, TValue> $items
     * @return Collection<TKey, TValue>
     */
    function collect(array $items = []): Collection
    {
        return new Collection($items);
    }
}

if (!function_exists('container')) {
    /**
     * @template T of object
     * @param class-string<T>|string|null $item
     * @return (
     *     $item is null ? DependencyInjectionContainer : (
     *         $item is class-string ? T : mixed
     *     )
     * )
     */
    function container(string|null $item = null): mixed
    {
        static $resolver = null;
        static $container = null;

        if ($resolver === null && $container === null) {
            $resolver = new Resolver();
            $container = new DependencyInjectionContainer();

            $resolver->withContainer($container);
            $container->withResolver($resolver);
        }

        try {
            return $item === null ? $container : $container->get($item);
        } catch (ReflectionException) {
            return $container;
        }
    }
}

if (!function_exists('env')) {
    /**
     * @param string|null $key
     * @param bool|float|int|string|null $defaultValue
     * @return ($key is null ? Environment : bool|float|int|string|null)
     */
    function env(string|null $key = null, bool|float|int|string|null $defaultValue = null): bool|float|int|string|null|Environment
    {
        static $environment = new Environment();
        return $key === null ? $environment : $environment->get($key, $defaultValue);
    }
}

if (!function_exists('request')) {
    /**
     * @return Request
     */
    function request(): Request
    {
        static $request = Request::fromGlobals();
        return $request;
    }
}

if (!function_exists('router')) {
    /**
     * @return Router
     */
    function router(): Router
    {
        static $router = new Router();
        return $router;
    }
}

if (!function_exists('app')) {
    /**
     * @return HttpApplication
     */
    function app(): HttpApplication
    {
        static $app = new HttpApplication(request());
        return $app;
    }
}

if (!function_exists('view')) {
    /**
     * @param string $view
     * @param array $data
     * @param string|null $layout
     * @param HttpCode $code
     * @param array $headers
     * @param string $httpVersion
     * @return ViewResponse
     * @throws ReflectionException
     */
    function view(
        string $view,
        array $data = [],
        string|null $layout = 'base',
        HttpCode $code = HttpCode::OK,
        array $headers = [],
        string $httpVersion = 'HTTP/2',
    ): ViewResponse {
        return new ViewResponse($view, $data, $code, $layout, $headers, $httpVersion);
    }
}

if (!function_exists('json')) {
    /**
     * @param mixed $data
     * @param HttpCode $code
     * @param array $headers
     * @param string $httpVersion
     * @return JsonResponse
     * @throws JsonException
     */
    function json(
        mixed $data = null,
        HttpCode $code = HttpCode::OK,
        array $headers = [],
        string $httpVersion = 'HTTP/1.1',
    ): JsonResponse {
        return new JsonResponse($data, $code, $headers, $httpVersion);
    }
}

if (!function_exists('redirect')) {
    /**
     * @param string $location
     * @param HttpCode $code
     * @param array $headers
     * @param string $httpVersion
     * @return RedirectResponse
     */
    function redirect(
        string $location,
        HttpCode $code = HttpCode::FOUND,
        array $headers = [],
        string $httpVersion = 'HTTP/2',
    ): RedirectResponse {
        return new RedirectResponse($location, $code, $headers, $httpVersion);
    }
}

if (!function_exists('logger')) {
    /**
     * @return LoggerInterface
     */
    function logger(): LoggerInterface {
        return container(LoggerInterface::class);
    }
}

if (!function_exists('trans')) {
    /**
     * @param string $key
     * @param int|float $count
     * @param array $context
     * @return string|array
     */
    function trans(string $key, int|float $count = 1, array $context = []): string|array {
        static $translator = container(Translator::class);
        return $translator->translate($key, $count, $context);
    }
}

/**
 * Génère un ULID (26 chars, Crock ford Base32).
 *
 * @param int|null $ms Timestamp en millisecondes depuis l'époque Unix. Si null, on utilise le temps courant.
 * @return string ULID (26 caractères)
 * @throws InvalidArgumentException
 * @throws RandomException
 * @throws RuntimeException
 */
function ulid(?int $ms = null): string
{
    // alphabet Crock ford Base32 (sans I, L, O, U)
    $alphabet = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    // timestamp en ms (48 bits)
    if ($ms === null) {
        // microtime(true) retourne secondes en float, *1000 -> ms
        $ms = (int)floor(microtime(true) * 1000);
    }

    if ($ms < 0 || $ms > 0xFFFFFFFFFFFF) { // 48 bits max
        throw new InvalidArgumentException('Timestamp hors de la plage 48-bit.');
    }

    // construire 6 octets big-endian du timestamp (48 bits)
    $timeHex = str_pad(dechex($ms), 12, '0', STR_PAD_LEFT); // 12 hex chars = 6 bytes
    $timeBytes = hex2bin($timeHex);

    // 10 octets d'entropie (80 bits)
    if (function_exists('random_bytes')) {
        $rand = random_bytes(10);
    } else {
        throw new RuntimeException('Aucun générateur de bytes aléatoires disponible.');
    }

    // 16 octets = 128 bits total (6 pour le timestamp, 10 pour random)
    $bytes = $timeBytes . $rand;

    // convertir en chaîne de bits, préfixer de 2 zéros pour obtenir 130 bits (26 * 5)
    $bits = '00';
    for ($i = 0, $len = strlen($bytes); $i < $len; $i++) {
        $bits .= str_pad(decbin(ord($bytes[$i])), 8, '0', STR_PAD_LEFT);
    }

    // découper en 26 groupes de 5 bits → indices base32
    $ulid = '';
    for ($i = 0; $i < 26; $i++) {
        $chunk = substr($bits, $i * 5, 5);
        $idx = bindec($chunk);
        $ulid .= $alphabet[$idx];
    }

    return $ulid;
}
