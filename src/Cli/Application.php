<?php

namespace Juzdy\Cli;

/**
 * CLI Application - Entry point for CLI commands.
 */
class Application
{
    /**
     * @var CommandInterface[]
     */
    private array $commands = [];

    /**
     * Register a command.
     *
     * @param CommandInterface $command
     * @return self
     */
    public function registerCommand(CommandInterface $command): self
    {
        $this->commands[$command->getName()] = $command;
        return $this;
    }

    /**
     * Get all registered commands.
     *
     * @return CommandInterface[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Run the CLI application.
     *
     * @param array $argv Command line arguments
     * @return int Exit code
     */
    public function run(array $argv): int
    {
        // Remove script name
        array_shift($argv);

        if (empty($argv)) {
            $this->showHelp();
            return 0;
        }

        $commandName = array_shift($argv);

        if ($commandName === 'help' || $commandName === '--help' || $commandName === '-h') {
            $this->showHelp();
            return 0;
        }

        if ($commandName === 'list') {
            $this->listCommands();
            return 0;
        }

        if (!isset($this->commands[$commandName])) {
            fwrite(STDERR, "Unknown command: $commandName" . PHP_EOL);
            $this->showHelp();
            return 1;
        }

        return $this->commands[$commandName]->execute($argv);
    }

    /**
     * Show the help message.
     *
     * @return void
     */
    private function showHelp(): void
    {
        echo "CLI" . PHP_EOL;
        echo "===================" . PHP_EOL;
        echo PHP_EOL;
        echo "Usage: php bin/cli <command> [arguments]" . PHP_EOL;
        echo PHP_EOL;
        echo "Available commands:" . PHP_EOL;
        $this->listCommands();
        echo PHP_EOL;
        echo "Use 'php bin/cli help' for more information." . PHP_EOL;
    }

    /**
     * List all available commands.
     *
     * @return void
     */
    private function listCommands(): void
    {
        foreach ($this->commands as $name => $command) {
            echo sprintf("  %-20s %s", $name, $command->getDescription()) . PHP_EOL;
        }
    }
}
