<?php

namespace Tuto\Middleware\Global;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonException;
use ReflectionException;
use Tuto\Http\Components\Cookie;
use Tuto\Http\Requests\Request;
use Tuto\Http\Responses\HttpCode;
use Tuto\Middleware\MiddlewareInterface;
use Tuto\Utils\File;

class MaintenanceModeMiddleware implements MiddlewareInterface
{
    /**
     * @param Request $request
     * @param callable(Request): mixed $next
     * @return mixed
     * @throws ReflectionException
     */
    public function handle(Request $request, callable $next): mixed
    {
        $maintenanceFile = container()->getWithoutError('maintenance.file', 'storage/framework/down.json');
        $maintenanceFile = File::absolute($maintenanceFile);
        if (!file_exists($maintenanceFile)) {
            return $next($request);
        }

        $maintenanceData = $this->getMaintenanceData($maintenanceFile);
        if ($this->canBypass($request, $maintenanceData)) {
            $cookie = new Cookie(
                $maintenanceData['cookie_name'],
                $maintenanceData['secret'],
                new DateTimeImmutable("+{$maintenanceData['cookie_duration']} second"),
            );
            $request->cookies->offsetSet($maintenanceData['cookie_name'], $cookie);
            return $next($request);
        }

        return view(
            view: 'maintenance',
            data: ['retry' => $maintenanceData['retry']],
            layout: null,
            code: HttpCode::SERVICE_UNAVAILABLE,
            headers: ['Retry-After' => (string) $maintenanceData['retry']],
        );
    }

    /**
     * @param string $file
     * @return array{
     *     cookie_name: string,
     *     cookie_duration: int,
     *     secret: string,
     *     retry: int,
     *     allowed_ips: string[]
     * }
     */
    private function getMaintenanceData(string $file): array
    {
        $data = [
            'cookie_name' => container()->getWithoutError('maintenance.cookie_name', 'maintenance_bypass'),
            'cookie_duration' => (int)container()->getWithoutError('maintenance.cookie_duration', 2 * 3600),
            'secret' => container()->getWithoutError('maintenance.secret', 'change_me'),
            'retry' => (int)container()->getWithoutError('maintenance.retry', 3600),
            'allowed_ips' => container()->getWithoutError('maintenance.allowed_ips', []),
        ];

        try {
            $content = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
            $allowedIps = $content['allowed_ips'] ?? null;
            if ($allowedIps !== null && !is_array($content['allowed_ips'])) {
                unset($content['allowed_ips']);
            }
        } catch (JsonException) {
            $content = [];
        }

        foreach ($data as $key => $value) {
            $data[$key] = $content[$key] ?? $value;
        }

        return $data;
    }

    /**
     * @param Request $request
     * @param array{
     *      cookie_name: string,
     *      cookie_duration: int,
     *      secret: string,
     *      retry: int,
     *      allowed_ips: string[]
     *  } $maintenanceData
     * @return bool
     */
    private function canBypass(Request $request, array $maintenanceData): bool
    {
        try {
            $bypassCookie = $request->cookies->offsetGet($maintenanceData['cookie_name']);
        } catch (InvalidArgumentException) {
            $bypassCookie = null;
        }
        if ($bypassCookie !== null && hash_equals($maintenanceData['secret'], $bypassCookie->value)) {
            return true;
        }

        try {
            $bypassQuery = $request->query->offsetGet('maintenance_pass');
        } catch (InvalidArgumentException) {
            $bypassQuery = null;
        }
        if ($bypassQuery !== null && hash_equals($maintenanceData['secret'], $bypassQuery)) {
            return true;
        }

        $clientIp = $_SERVER['REMOTE_ADDR'] ?? null;
        return $clientIp && in_array($clientIp, $maintenanceData['allowed_ips'], true);
    }
}
