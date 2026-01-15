<?php

namespace Juzdy\Cli\Command;

use Juzdy\Cli\AbstractCommand;
use Juzdy\Cli\Migration;
use Juzdy\Config;

/**
 * Command to load demo data into the database.
 */
class DbDemoCommand extends AbstractCommand
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'db:demo';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Load demo data into the database (runs etc/db/demo.sql or demo.php)';
    }

    /**
     * @inheritDoc
     */
    public function execute(array $args = []): int
    {
        $root = Config::get('root');
        $demoPath = $root . 'etc/db/';

        $this->info('Loading demo data...');

        // Check for demo.php first, then demo.sql (PHP takes priority)
        $migration = new Migration($demoPath);

        $phpFile = $demoPath . 'demo.php';
        $sqlFile = $demoPath . 'demo.sql';

        try {
            if (file_exists($phpFile)) {
                $this->output("Executing: $phpFile");
                if (!$migration->executePhpFile($phpFile)) {
                    $this->error("Failed to execute: $phpFile");
                    return 1;
                }
            } elseif (file_exists($sqlFile)) {
                $this->output("Executing: $sqlFile");
                if (!$migration->executeSqlFile($sqlFile)) {
                    $this->error("Failed to execute: $sqlFile");
                    return 1;
                }
            } else {
                $this->warning("No demo file found at $demoPath (demo.sql or demo.php)");
                return 0;
            }
        } catch (\Exception $e) {
            $this->error("Demo data load failed: " . $e->getMessage());
            return 1;
        }

        $this->success('Demo data loaded successfully!');
        return 0;
    }
}
