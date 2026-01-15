<?php

namespace Juzdy\Cli;

/**
 * Interface for CLI commands.
 */
interface CommandInterface
{
    /**
     * Get the command name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the command description.
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Execute the command.
     *
     * @param array $args Command line arguments
     * @return int Exit code (0 for success, non-zero for failure)
     */
    public function execute(array $args = []): int;
}
