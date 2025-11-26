<?php

namespace App\Commands;

use DateTimeImmutable;
use ReflectionException;
use Tuto\CLI\Ansi;
use Tuto\CLI\Command;
use Tuto\CLI\Input;
use Tuto\CLI\Output;
use Tuto\CLI\Style;
use Tuto\Database\Migrations\MigrationsRepository;

class MigrateMakeCommand extends Command
{
    public function __construct(private readonly MigrationsRepository $migrationsRepository)
    {
    }

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
     * @throws ReflectionException
     */
    public function execute(Input $input, Output $output): int
    {
        $errorStyle = Style::create()->fgStandard(Ansi::FG_RED);

        $migrationName = $input->getArgument(0);
        if ($migrationName === null) {
            $output->writeln($errorStyle->apply("Command needs a migration name : 'php cli.php migrate:make <name>'"));
            return self::EXIT_FAILURE;
        }

        $currentDate = new DateTimeImmutable()->format("Y_m_d_H_i_s");
        $filename = "{$currentDate}_{$migrationName}.php";
        $migrationPath = container('path.database') . "/migrations/{$filename}";
        if (file_exists($migrationPath)) {
            $output->writeln($errorStyle->apply("File '{$migrationPath}' already exist"));
            return self::EXIT_FAILURE;
        }

        $output->write("Creating file '{$filename}' ");
        $output->block("DOING", Ansi::FG_YELLOW);
        $fileContents = <<<EOF
<?php

use Tuto\Database\ConnectionInterface;
use Tuto\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(ConnectionInterface \$connection): void
    {
    }
    
    public function down(ConnectionInterface \$connection): void
    {
    }
};

EOF;
        file_put_contents($migrationPath, $fileContents);

        $output->write("Creating file '{$filename}' ");
        $output->block("DONE", Ansi::FG_GREEN);
        $output->writeln();

        return self::EXIT_SUCCESS;
    }
}