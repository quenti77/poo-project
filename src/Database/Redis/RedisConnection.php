<?php

namespace Tuto\Database\Redis;

use Redis;
use RedisException;
use Tuto\Cache\CacheConnectionException;

class RedisConnection extends Redis
{
    /**
     * @param string $host
     * @param int $port
     * @param string|null $password
     * @param int $database
     * @param int $timeout
     */
    public function __construct(
        string $host,
        int $port = 6379,
        string|null $password = null,
        int $database = 0,
        int $timeout = 2,
    ) {
        parent::__construct();

        if (!$this->connect($host, $port, $timeout)) {
            throw new CacheConnectionException("Failed to connect to Redis at {$host}:{$port}");
        }

        if ($password !== null && !$this->auth($password)) {
            throw new CacheConnectionException("Failed to authenticate to Redis");
        }

        if (!$this->select($database)) {
            throw new CacheConnectionException("Failed to select Redis database '{$database}'");
        }
    }

    public function __destruct()
    {
        try {
            $this->close();
        } catch (RedisException $exception) {
            logger()->warning("Error during closing redis connection : {$exception->getMessage()}");
        }

        parent::__destruct();
    }
}
