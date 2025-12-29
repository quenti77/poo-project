<?php

namespace App\Commands\Migrate;

use DateTimeImmutable;
use Tuto\CLI\Command;
use Tuto\CLI\Input\Input;
use Tuto\CLI\Output\Ansi;
use Tuto\CLI\Output\Output;

class MigrationMakeCommand extends Command
{
    public function getName(): string
    {
        return "migrate:make";
    }

    public function getDescription(): string
    {
        return "Create new migration";
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return int
     */
    public function execute(Input $input, Output $output): int
    {
        $migrationName = $input->getArgument(0);
        if ($migrationName === null) {
            $migrationName = $output->ask('Nom de la migration')
                ->setValidator(function (string $value): bool|string {
                    if (!preg_match('/^[a-z0-9_]+$/', $value)) {
                        return "Le nom doit être en snake_case (lettres minuscules, chiffres et underscores uniquement).";
                    }
                    return true;
                })
                ->ask();
        }

        $currentDate = new DateTimeImmutable()->format("Y_m_d_H_i_s");
        $filename = "{$currentDate}_{$migrationName}.php";
        $migrationPath = container('path.database') . "/migrations/{$filename}";

        if (file_exists($migrationPath)) {
            $output->error("File '{$migrationPath}' already exist");
            return self::EXIT_FAILURE;
        }

        $output->write("Creating file '{$filename}' ");
        $output->badge("DOING", Ansi::FG_YELLOW);

        $fileContents = <<<'EOF'
<?php

use Tuto\Database\ConnectionInterface;
use Tuto\Database\Migrations\MigrationInterface;

return new class implements MigrationInterface
{
    /**
     * @param ConnectionInterface $connection
     * @return void
     */
    public function up(ConnectionInterface $connection): void
    {
        // Write your migration here
    }

    /**
     * @param ConnectionInterface $connection
     * @return void
     */
    public function down(ConnectionInterface $connection): void
    {
        // Write your rollback here
    }
};

EOF;
        file_put_contents($migrationPath, $fileContents);

        $output->write("Creating file '{$filename}' ");
        $output->badge("DONE", Ansi::FG_GREEN);
        $output->writeln();

        return self::EXIT_SUCCESS;
    }
}
