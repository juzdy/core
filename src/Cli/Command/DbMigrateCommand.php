<?php

namespace Juzdy\Cli\Command;

use Juzdy\Cli\AbstractCommand;
use Juzdy\Cli\Migration;
use Juzdy\Config;

/**
 * Command to run database migrations.
 */
class DbMigrateCommand extends AbstractCommand
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'db:migrate';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Run pending database migrations (etc/db/migration-*.sql/.php)';
    }

    /**
     * @inheritDoc
     */
    public function execute(array $args = []): int
    {
        $root = Config::get('root');
        $migrationPath = $root . 'etc/db/';

        $this->info('Checking for pending migrations...');

        $migration = new Migration($migrationPath);

        try {
            $migration->ensureMigrationTable();
        } catch (\Exception $e) {
            $this->error('Failed to ensure migration table: ' . $e->getMessage());
            return 1;
        }

        $pending = $migration->getPendingMigrations();

        if (empty($pending)) {
            $this->success('No pending migrations found.');
            return 0;
        }

        $this->output('Found ' . count($pending) . ' pending migration(s):');
        foreach ($pending as $version => $filepath) {
            $this->output("  - $version: " . basename($filepath));
        }

        $this->output('');
        
        try {
            $count = $migration->migrate(
                function ($version, $filepath) {
                    $this->info("Executing migration: $version");
                },
                function ($version, $filepath, $exception) {
                    $this->error("Migration $version failed: " . $exception->getMessage());
                }
            );
            $this->success("Successfully executed $count migration(s).");
            return 0;
        } catch (\Exception $e) {
            $this->error('Migration failed: ' . $e->getMessage());
            return 1;
        }
    }
}
