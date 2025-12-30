<?php

namespace Tuto\Error;

use RuntimeException;
use Throwable;
use Tuto\CLIOld\Output\Ansi;
use Tuto\CLIOld\Output\Output;
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
        $output->errorBlock("⚠ Application Error", 2);

        $output->writeln(Ansi::FG_YELLOW . "An unexpected error occurred while processing your request." . Ansi::RESET);
        $output->writeln();

        $output->comment("The error has been logged and our team has been notified.");
        $output->comment("Please try again later or contact support if the problem persists.");
        $output->writeln();

        // Afficher un ID d'erreur pour référence (basé sur le timestamp et type)
        $errorId = strtoupper(substr(md5($errorDetails->type . $errorDetails->message . $errorDetails->file), 0, 8));
        $output->writeln(Ansi::DIM . "Error ID: {$errorId}" . Ansi::RESET);
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
        $output->errorBlock("⚠ {$errorDetails->type}", 2);

        // Message d'erreur principal
        $output->write(Ansi::FG_RED . Ansi::BOLD);
        $output->writeln($errorDetails->message);
        $output->write(Ansi::RESET);
        $output->writeln();

        // Détails de l'erreur
        $output->comment('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $output->writeln();

        // Localisation de l'erreur
        $output->write(Ansi::FG_CYAN . Ansi::BOLD . '📍 Location' . Ansi::RESET);
        $output->writeln();
        $output->writeln('   ' . Ansi::FG_YELLOW . 'File:  ' . Ansi::RESET . $errorDetails->file);
        $output->writeln('   ' . Ansi::FG_YELLOW . 'Line:  ' . Ansi::RESET . $errorDetails->line);
        if ($errorDetails->code !== 0) {
            $output->writeln('   ' . Ansi::FG_YELLOW . 'Code:  ' . Ansi::RESET . $errorDetails->code);
        }
        $output->writeln();

        // Stack trace si disponible
        if (!empty($errorDetails->trace)) {
            $output->write(Ansi::FG_CYAN . Ansi::BOLD . '📚 Stack Trace' . Ansi::RESET);
            $output->writeln();

            $traceItems = array_slice($errorDetails->formatTrace(), 0, 5); // Limiter à 5 entrées
            foreach ($traceItems as $index => $item) {
                $num = str_pad((string) ($index + 1), 2, ' ', STR_PAD_LEFT);
                $output->write('   ' . Ansi::DIM . "#{$num} " . Ansi::RESET);

                if ($item['class']) {
                    $output->write(Ansi::FG_MAGENTA . $item['class']);
                    $output->write(Ansi::DIM . '::' . Ansi::RESET);
                }

                $output->write(Ansi::FG_BLUE . $item['function'] . '()' . Ansi::RESET);
                $output->writeln();

                if ($item['file'] !== 'unknown') {
                    $output->writeln('       ' . Ansi::DIM . $item['file'] . ':' . $item['line'] . Ansi::RESET);
                }
            }

            if (count($errorDetails->trace) > 5) {
                $remaining = count($errorDetails->trace) - 5;
                $output->writeln('   ' . Ansi::DIM . "... and {$remaining} more" . Ansi::RESET);
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