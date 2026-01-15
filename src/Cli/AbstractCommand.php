<?php

namespace Juzdy\Cli;

/**
 * Abstract base class for CLI commands.
 */
abstract class AbstractCommand implements CommandInterface
{
    /**
     * Output a message to the console.
     *
     * @param string $message
     * @return void
     */
    protected function output(string $message): void
    {
        echo $message . PHP_EOL;
    }

    /**
     * Output an error message to the console.
     *
     * @param string $message
     * @return void
     */
    protected function error(string $message): void
    {
        fwrite(STDERR, "Error: $message" . PHP_EOL);
    }

    /**
     * Output a success message to the console.
     *
     * @param string $message
     * @return void
     */
    protected function success(string $message): void
    {
        echo "\033[32m$message\033[0m" . PHP_EOL;
    }

    /**
     * Output a warning message to the console.
     *
     * @param string $message
     * @return void
     */
    protected function warning(string $message): void
    {
        echo "\033[33m$message\033[0m" . PHP_EOL;
    }

    /**
     * Output an info message to the console.
     *
     * @param string $message
     * @return void
     */
    protected function info(string $message): void
    {
        echo "\033[36m$message\033[0m" . PHP_EOL;
    }
}
