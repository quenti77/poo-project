<?php

namespace App\Commands;

use Tuto\CLI\Command;
use Tuto\CLI\Input\Input;
use Tuto\CLI\Output\Output;
use Tuto\Utils\File;

class MaintenanceCommand extends Command
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return "maintenance";
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return "Close or open the project";
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return int
     * @throws \JsonException
     */
    public function execute(Input $input, Output $output): int
    {
        /**
         *      cookie_name: string,
         *      cookie_duration: int,
         *      secret: string,
         *      retry: int,
         *      allowed_ips: string[]
         */
        $mode = $input->getArgument(0, 'down');
        if (!in_array($mode, ['down', 'up'])) {
            $output->error("Mode '{$mode}' not found");
            return self::EXIT_FAILURE;
        }

        $file = File::absolute(container()->getWithoutError('maintenance.file', 'storage/framework/down.json'));
        if ($mode === 'up') {
            $output->info("Removing file '{$file}'");
            if (is_file($file)) {
                unlink($file);
            }
            $output->success("File '{$file}' removed");
            return self::EXIT_SUCCESS;
        }

        $data = [];
        $secret = $input->getOption('secret');
        if ($secret) {
            $data['secret'] = $secret;
        }

        $retry = $input->getOption('retry');
        if ($retry) {
            $data['retry'] = (int) $retry;
        }

        $output->info("Adding file '{$file}'");
        file_put_contents($file, json_encode($data, JSON_THROW_ON_ERROR));
        $output->success("File '{$file}' added");

        return self::EXIT_SUCCESS;
    }
}