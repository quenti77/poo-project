<?php

namespace Tuto\Cache\Drivers;

use DateTimeInterface;
use RuntimeException;
use Throwable;
use Tuto\Cache\CacheException;
use Tuto\Utils\CurrentTime;

class FileCache extends AbstractCache
{
    /**
     * @param CurrentTime $currentTime
     * @param string $cachePath
     * @param string $prefix
     */
    public function __construct(
        private readonly CurrentTime $currentTime,
        private readonly string $cachePath,
        private readonly string $prefix = 'cache',
    ) {
        $this->ensureCacheDirectoryExists();
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        $filePath = $this->getFilePath($key);
        if (!file_exists($filePath)) {
            return false;
        }

        try {
            $content = file_get_contents($filePath);
            if ($content === false) {
                return false;
            }

            $data = unserialize($content, ['allowed_classes' => true]);
            if ($this->isExpired($data)) {
                $this->delete($key);
                return false;
            }

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $filePath = $this->getFilePath($key);
        if (!file_exists($filePath)) {
            return $default;
        }

        try {
            $content = file_get_contents($filePath);
            if ($content === false) {
                return $default;
            }

            $data = unserialize($content, ['allowed_classes' => true]);
            if ($this->isExpired($data)) {
                $this->delete($key);
                return $default;
            }

            return $data['value'];
        } catch (Throwable $exception) {
            throw new CacheException("Failed to get cache key '{$key}': {$exception->getMessage()}", 0, $exception);
        }
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param DateTimeInterface|int|null $ttl
     * @return bool
     */
    public function set(string $key, mixed $value, DateTimeInterface|int|null $ttl = null): bool
    {
        $filePath = $this->getFilePath($key);
        $expiration = $this->calculateExpiration($ttl);

        $data = [
            'key' => $key,
            'value' => $value,
            'expiration' => $expiration,
            'created_at' => $this->currentTime->now()->getTimestamp(),
        ];

        try {
            $serialized = serialize($data);
            $result = file_put_contents($filePath, $serialized, LOCK_EX);
            return $result !== false;
        } catch (Throwable $exception) {
            throw new CacheException("Failed to get cache key '{$key}': {$exception->getMessage()}", 0, $exception);
        }
    }

    /**
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        $filePath = $this->getFilePath($key);
        if (!file_exists($filePath)) {
            return false;
        }

        try {
            return unlink($filePath);
        } catch (Throwable $exception) {
            throw new CacheException("Failed to delete cache key '{$key}': {$exception->getMessage()}", 0, $exception);
        }
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        try {
            return rmdir(dirname($this->getDirPath('default_key')));
        } catch (Throwable $exception) {
            throw new CacheException("Failed to clear cache: {$exception->getMessage()}", 0, $exception);
        }
    }

    /**
     * @param string $key
     * @param int $value
     * @return int|false
     */
    public function increment(string $key, int $value = 1): int|false
    {
        $current = $this->get($key, 0);
        if (!is_numeric($current)) {
            return false;
        }

        $newValue = ((int) $current) + $value;
        $this->set($key, $newValue);

        return $newValue;
    }

    /**
     * @param string $key
     * @param int $value
     * @return int|false
     */
    public function decrement(string $key, int $value = 1): int|false
    {
        return $this->increment($key, -$value);
    }

    /**
     * @return void
     */
    private function ensureCacheDirectoryExists(): void
    {
        if (!is_dir($this->cachePath) && !mkdir($this->cachePath, 0755, true) && !is_dir($this->cachePath)) {
            throw new RuntimeException("Failed to create cache directory: {$this->cachePath}");
        }
        if (!is_writable($this->cachePath)) {
            throw new RuntimeException("Cache directory is not writable: '{$this->cachePath}'");
        }
    }

    /**
     * @param string $key
     * @return string
     */
    private function getDirPath(string $key): string
    {
        $hash = hash('sha256', $key);
        $subDirHash = substr($hash, 0, 2);
        $dir = "{$this->cachePath}/{$this->prefix}/{$subDirHash}";

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }

        return $dir;
    }

    /**
     * @param string $key
     * @return string
     */
    private function getFilePath(string $key): string
    {
        $hash = hash('sha256', $key);
        return "{$this->getDirPath($key)}/{$hash}.cache";
    }

    /**
     * @param DateTimeInterface|int|null $ttl
     * @return int|null
     */
    private function calculateExpiration(DateTimeInterface|int|null $ttl): int|null
    {
        if ($ttl === null) {
            return null;
        }

        if ($ttl instanceof DateTimeInterface) {
            return $ttl->getTimestamp();
        }

        return $this->currentTime->now()->getTimestamp() + $ttl;
    }

    /**
     * @param array $data
     * @return bool
     */
    private function isExpired(array $data): bool
    {
        $expiration = $data['expiration'] ?? 0;
        return $this->currentTime->now()->getTimestamp() > $expiration;
    }
}