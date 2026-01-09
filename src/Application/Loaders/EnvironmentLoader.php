<?php

namespace Tuto\Application\Loaders;

class EnvironmentLoader implements LoaderInterface
{
    /**
     * @return void
     */
    public function load(): void
    {
        $this->loadEnvFile('.env');
        $this->loadEnvFile('.env.local');

        $currentEnv = env('APP_ENV', 'local');
        $this->loadEnvFile(".env.{$currentEnv}");
        $this->loadEnvFile(".env.{$currentEnv}.local");
    }

    private function loadEnvFile(string $filepath): void
    {
        $path = ROOT . "/{$filepath}";
        if (file_exists($path)) {
            env()?->load($path);
        }
    }
}
