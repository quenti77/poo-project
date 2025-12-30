<?php

namespace App\Commands;

use JsonException;
use Tuto\Console\Commands\AbstractCommand;
use Tuto\Console\Commands\CommandStatus;
use Tuto\Console\Components\Input;
use Tuto\Console\Components\Output;
use Tuto\Utils\File;

class MaintenanceCommand extends AbstractCommand
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
     * @return CommandStatus
     * @throws JsonException
     */
    public function execute(Input $input, Output $output): CommandStatus
    {
        $mode = $input->getArgument(0, 'down');
        if (!in_array($mode, ['down', 'up'])) {
            $output->error("Mode '{$mode}' not found");
            $output->writeln();
            return CommandStatus::GENERIC_FAILURE;
        }

        $file = File::absolute(container()->getWithoutError('maintenance.file', 'storage/framework/down.json'));
        if ($mode === 'up') {
            $output->info("Removing file '{$file}'");
            $output->writeln();
            if (is_file($file)) {
                unlink($file);
            }
            $output->success("File '{$file}' removed");
            $output->writeln();
            return CommandStatus::SUCCESS;
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
        $output->writeln();
        file_put_contents($file, json_encode($data, JSON_THROW_ON_ERROR));
        $output->success("File '{$file}' added");
        $output->writeln();

        return CommandStatus::SUCCESS;
    }
}