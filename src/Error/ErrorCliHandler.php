<?php

namespace Tuto\Error;

use RuntimeException;
use Throwable;
use Tuto\Console\Components\Ansi;
use Tuto\Console\Components\Output;
use Tuto\Logger\LoggerLevel;

class ErrorCliHandler extends ErrorHandler
{
    private static Output|null $output = null;

    public static function withOutput(Output $output): void
    {
        static::$output = $output;
    }

    protected static function renderError(ErrorDetails $errorDetails): void
    {
        try {
            if (static::$output === null) {
                throw new RuntimeException('Output not defined');
            }

            static::logError($errorDetails);
            static::displayErrorCli($errorDetails, static::$output);
        } catch (Throwable $exception) {
            static::logError(ErrorFactory::fromThrowable($exception, LoggerLevel::EMERGENCY));
            static::renderFallbackError($errorDetails, $exception);
        }
    }

    /**
     * Affiche l'erreur avec un rendu CLI formaté et coloré
     *
     * @param ErrorDetails $errorDetails
     * @param Output $output
     * @return void
     */
    private static function displayErrorCli(ErrorDetails $errorDetails, Output $output): void
    {
        $isDebug = container()->getWithoutError('app.debug', true);

        if ($isDebug) {
            static::displayErrorCliDebug($errorDetails, $output);
        } else {
            static::displayErrorCliProduction($errorDetails, $output);
        }
    }

    /**
     * Affiche l'erreur en mode production (version simplifiée)
     *
     * @param ErrorDetails $errorDetails
     * @param Output $output
     * @return void
     */
    private static function displayErrorCliProduction(ErrorDetails $errorDetails, Output $output): void
    {
        $output->writeln();
        $output->blockError("⚠ Application Error");

        $output->warning("An unexpected error occurred while processing your request.");
        $output->writeln("\n");

        $output->comment("The error has been logged and our team has been notified.");
        $output->comment("Please try again later or contact support if the problem persists.");
        $output->writeln("\n");

        $errorId = strtoupper(substr(md5($errorDetails->type . $errorDetails->message . $errorDetails->file), 0, 8));
        $output->comment("Error ID: {$errorId}");
        $output->writeln();
    }

    /**
     * Affiche l'erreur en mode debug (version complète avec détails)
     *
     * @param ErrorDetails $errorDetails
     * @param Output $output
     * @return void
     */
    private static function displayErrorCliDebug(ErrorDetails $errorDetails, Output $output): void
    {
        // En-tête d'erreur avec bloc rouge
        $output->writeln();
        $output->blockError("⚠ {$errorDetails->type}");

        // Message d'erreur principal
        $output->error($errorDetails->message);
        $output->writeln();

        // Détails de l'erreur
        $output->comment('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $output->writeln("\n");

        // Localisation de l'erreur
        $output->info(Ansi::BOLD->value . '📍 Location');
        $output->writeln();
        $output->warning('   File:  ' . Ansi::RESET->value . $errorDetails->file . "\n");
        $output->warning('   Line:  ' . Ansi::RESET->value . $errorDetails->line . "\n");
        if ($errorDetails->code !== 0) {
            $output->warning('   Code:  ' . Ansi::RESET->value . $errorDetails->code . "\n");
        }
        $output->writeln();

        // Stack trace si disponible
        if (!empty($errorDetails->trace)) {
            $output->info(Ansi::BOLD->value . '📚 Stack Trace');
            $output->writeln();

            $traceItems = array_slice($errorDetails->formatTrace(), 0, 5); // Limiter à 5 entrées
            foreach ($traceItems as $index => $item) {
                $num = str_pad((string) ($index + 1), 2, ' ', STR_PAD_LEFT);
                $output->comment("   #{$num} ");

                if ($item['class']) {
                    $output->write(Ansi::FG_MAGENTA->value . $item['class']);
                    $output->comment('::');
                }

                $output->write(Ansi::FG_BLUE->value . $item['function'] . '()');
                $output->writeln();

                if ($item['file'] !== 'unknown') {
                    $output->comment('       ' . $item['file'] . ':' . $item['line']);
                }
            }

            if (count($errorDetails->trace) > 5) {
                $remaining = count($errorDetails->trace) - 5;
                $output->comment("   ... and {$remaining} more\n");
            }
            $output->writeln();
        }

        $output->comment('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $output->writeln();
    }

    /**
     * @param ErrorDetails $errorDetails
     * @param Throwable $throwable
     * @return void
     */
    protected static function renderFallbackError(ErrorDetails $errorDetails, Throwable $throwable): void
    {
        echo "Internal Server Error\n";
        echo "Original Error: {$errorDetails->message}\n";
        echo "At: {$errorDetails->file} #{$errorDetails->line}\n\n";
        echo "Rendering Error: {$throwable->getMessage()}";
    }
}