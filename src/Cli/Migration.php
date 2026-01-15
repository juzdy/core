<?php

namespace Juzdy\Cli;

use Juzdy\Database;
use PDO;

/**
 * Migration class for handling database migrations.
 */
class Migration
{
    private const MIGRATION_TABLE = 'migrations';

    private string $migrationPath;

    /**
     * @param string $migrationPath Path to migration files directory
     */
    public function __construct(string $migrationPath)
    {
        $this->migrationPath = rtrim($migrationPath, '/');
    }

    /**
     * Ensure the migrations table exists.
     *
     * @return void
     */
    public function ensureMigrationTable(): void
    {
        $pdo = Database::connect();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS " . self::MIGRATION_TABLE . " (
                id INT AUTO_INCREMENT PRIMARY KEY,
                version VARCHAR(50) NOT NULL UNIQUE,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    /**
     * Get list of already executed migrations.
     *
     * @return array
     */
    public function getExecutedMigrations(): array
    {
        $pdo = Database::connect();
        $stmt = $pdo->query("SELECT version FROM " . self::MIGRATION_TABLE . " ORDER BY version");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Record a migration as executed.
     *
     * @param string $version
     * @return void
     */
    public function recordMigration(string $version): void
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("INSERT INTO " . self::MIGRATION_TABLE . " (version) VALUES (?)");
        $stmt->execute([$version]);
    }

    /**
     * Get list of pending migrations.
     *
     * @return array Array of [version => filepath]
     */
    public function getPendingMigrations(): array
    {
        $executed = $this->getExecutedMigrations();
        $migrations = $this->findMigrationFiles();

        $pending = [];
        foreach ($migrations as $version => $filepath) {
            if (!in_array($version, $executed)) {
                $pending[$version] = $filepath;
            }
        }

        // Sort using version_compare for proper semantic version ordering
        uksort($pending, 'version_compare');
        return $pending;
    }

    /**
     * Find all migration files in the migration path.
     *
     * @return array Array of [version => filepath]
     */
    public function findMigrationFiles(): array
    {
        $migrations = [];

        if (!is_dir($this->migrationPath)) {
            return $migrations;
        }

        $files = array_merge(
            glob($this->migrationPath . '/migration-*.sql'),
            glob($this->migrationPath . '/migration-*.php')
        );

        foreach ($files as $file) {
            $basename = basename($file);
            // Extract version from filename like "migration-1.0.0.sql" or "migration-1.0.0.php"
            if (preg_match('/^migration-(.+)\.(sql|php)$/', $basename, $matches)) {
                $version = $matches[1];
                $migrations[$version] = $file;
            }
        }

        // Sort using version_compare for proper semantic version ordering
        uksort($migrations, 'version_compare');
        return $migrations;
    }

    /**
     * Execute a migration file.
     *
     * @param string $filepath
     * @return bool
     */
    public function executeMigrationFile(string $filepath): bool
    {
        $extension = pathinfo($filepath, PATHINFO_EXTENSION);

        if ($extension === 'sql') {
            return $this->executeSqlFile($filepath);
        } elseif ($extension === 'php') {
            return $this->executePhpFile($filepath);
        }

        return false;
    }

    /**
     * Execute a SQL file.
     *
     * @param string $filepath
     * @return bool
     * @throws \Exception on failure
     */
    public function executeSqlFile(string $filepath): bool
    {
        if (!file_exists($filepath)) {
            return false;
        }

        $sql = file_get_contents($filepath);
        if (empty(trim($sql))) {
            return true;
        }

        $pdo = Database::connect();

        // Split by semicolons and execute each statement
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($s) => !empty($s)
        );

        // Use transaction for atomicity (note: DDL statements like DROP TABLE,
        // CREATE TABLE, ALTER TABLE cause implicit commits in MySQL and cannot
        // be rolled back)
        $pdo->beginTransaction();
        try {
            foreach ($statements as $statement) {
                $pdo->exec($statement);
            }
            // Only commit if transaction is still active (DDL statements cause implicit commits)
            if ($pdo->inTransaction()) {
                $pdo->commit();
            }
            return true;
        } catch (\Exception $e) {
            // Only rollback if transaction is still active
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Execute a PHP migration file.
     *
     * @param string $filepath
     * @return bool
     */
    public function executePhpFile(string $filepath): bool
    {
        if (!file_exists($filepath)) {
            return false;
        }

        // The PHP file should return a callable or be a script that runs migrations
        // It has access to the Database class via the Juzdy\Database namespace
        $pdo = Database::connect();

        // Include the file - it can access $pdo variable
        $result = include $filepath;

        // If the file returns false, consider it failed
        if ($result === false) {
            return false;
        }

        return true;
    }

    /**
     * Run all pending migrations.
     *
     * @param callable|null $onMigration Callback called for each migration (version, filepath)
     * @param callable|null $onError Callback called on error (version, filepath, exception)
     * @return int Number of migrations executed
     * @throws \Exception if a migration fails and no error callback is provided
     */
    public function migrate(?callable $onMigration = null, ?callable $onError = null): int
    {
        $this->ensureMigrationTable();
        $pending = $this->getPendingMigrations();
        $count = 0;

        foreach ($pending as $version => $filepath) {
            if ($onMigration) {
                $onMigration($version, $filepath);
            }

            try {
                if ($this->executeMigrationFile($filepath)) {
                    $this->recordMigration($version);
                    $count++;
                } else {
                    $exception = new \RuntimeException("Migration file returned false: $filepath");
                    if ($onError) {
                        $onError($version, $filepath, $exception);
                    }
                    throw $exception;
                }
            } catch (\Exception $e) {
                if ($onError) {
                    $onError($version, $filepath, $e);
                }
                throw $e;
            }
        }

        return $count;
    }
}
