# CLI Commands

JUZDY Core provides a CLI application framework for building command-line tools, including built-in commands for database operations.

## Table of Contents

- [Overview](#overview)
- [Basic Usage](#basic-usage)
- [Creating Commands](#creating-commands)
- [Built-in Commands](#built-in-commands)
- [Command Arguments](#command-arguments)
- [Best Practices](#best-practices)

## Overview

The CLI system provides:
- **Command-line application framework**
- **Built-in database commands** (setup, migrate, demo)
- **Argument parsing**
- **Easy command creation**

## Basic Usage

### Running CLI Application

```php
// cli.php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Juzdy\Cli\Application;
use Juzdy\Container\Container;

$container = new Container();
$app = $container->get(Application::class);

// Register commands
$app->register('db:setup', \Juzdy\Cli\Command\DbSetupCommand::class);
$app->register('db:migrate', \Juzdy\Cli\Command\DbMigrateCommand::class);

// Run
$app->run($argv);
```

### Execute Commands

```bash
# Run command
php cli.php db:setup

# With arguments
php cli.php db:migrate --version=001

# List commands
php cli.php list

# Help
php cli.php help db:migrate
```

## Creating Commands

### Basic Command

```php
<?php
namespace App\Command;

use Juzdy\Cli\AbstractCommand;

class HelloCommand extends AbstractCommand
{
    protected string $name = 'hello';
    protected string $description = 'Say hello';
    
    public function execute(array $args = []): int
    {
        $name = $args['name'] ?? 'World';
        
        $this->output("Hello, {$name}!");
        
        return 0; // Success
    }
}
```

### Command with Dependencies

```php
use Juzdy\Cli\AbstractCommand;

class UserListCommand extends AbstractCommand
{
    protected string $name = 'user:list';
    protected string $description = 'List all users';
    
    public function __construct(
        private UserRepository $repository,
        private LoggerInterface $logger
    ) {
    }
    
    public function execute(array $args = []): int
    {
        try {
            $users = $this->repository->all();
            
            $this->output("Users:");
            foreach ($users as $user) {
                $this->output("  - {$user->getName()} ({$user->getEmail()})");
            }
            
            return 0;
        } catch (Exception $e) {
            $this->error("Error listing users: " . $e->getMessage());
            $this->logger->error('User list command failed', [
                'exception' => $e
            ]);
            return 1;
        }
    }
}
```

### Command Output Methods

```php
class MyCommand extends AbstractCommand
{
    public function execute(array $args = []): int
    {
        // Standard output
        $this->output("Regular message");
        
        // Error output
        $this->error("Error message");
        
        // Info message
        $this->info("Info message");
        
        // Success message
        $this->success("Success message");
        
        // Line break
        $this->output("");
        
        // Formatted output
        $this->output("Name: " . $this->format($name, 'green'));
        
        return 0;
    }
    
    protected function format(string $text, string $color): string
    {
        $colors = [
            'red' => "\033[31m",
            'green' => "\033[32m",
            'yellow' => "\033[33m",
            'blue' => "\033[34m",
            'reset' => "\033[0m",
        ];
        
        return ($colors[$color] ?? '') . $text . $colors['reset'];
    }
}
```

## Built-in Commands

### DbSetupCommand

Creates database tables:

```php
use Juzdy\Cli\Command\DbSetupCommand;

class MyDbSetupCommand extends DbSetupCommand
{
    public function execute(array $args = []): int
    {
        $this->output("Setting up database...");
        
        $pdo = Database::connect();
        
        // Create users table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            )
        ");
        
        // Create posts table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS posts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ");
        
        $this->success("Database setup complete!");
        
        return 0;
    }
}
```

### DbMigrateCommand

Run database migrations:

```php
use Juzdy\Cli\Command\DbMigrateCommand;

// migrations/001_create_users.php
return [
    'up' => "
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL
        )
    ",
    'down' => "
        DROP TABLE users
    "
];

// Usage
php cli.php db:migrate --version=001
```

### DbDemoCommand

Seed database with demo data:

```php
use Juzdy\Cli\Command\DbDemoCommand;

class MyDemoCommand extends DbDemoCommand
{
    public function execute(array $args = []): int
    {
        $this->output("Seeding demo data...");
        
        // Create demo users
        for ($i = 1; $i <= 10; $i++) {
            $user = new User();
            $user->set('name', "User {$i}");
            $user->set('email', "user{$i}@example.com");
            $user->set('password', password_hash('password', PASSWORD_DEFAULT));
            $user->save();
            
            $this->output("Created user: {$user->get('name')}");
        }
        
        $this->success("Demo data created!");
        
        return 0;
    }
}
```

## Command Arguments

### Parsing Arguments

```php
class DeployCommand extends AbstractCommand
{
    protected string $name = 'deploy';
    
    public function execute(array $args = []): int
    {
        // Boolean flags
        $force = isset($args['force']) || isset($args['f']);
        $verbose = isset($args['verbose']) || isset($args['v']);
        
        // Value arguments
        $environment = $args['env'] ?? 'production';
        $branch = $args['branch'] ?? 'main';
        
        if ($verbose) {
            $this->output("Deploying branch: {$branch}");
            $this->output("Environment: {$environment}");
            $this->output("Force: " . ($force ? 'yes' : 'no'));
        }
        
        // Deployment logic...
        
        return 0;
    }
}

// Usage:
// php cli.php deploy --env=staging --branch=develop --force
// php cli.php deploy -v -f
```

### Argument Validation

```php
class BackupCommand extends AbstractCommand
{
    protected string $name = 'backup';
    
    public function execute(array $args = []): int
    {
        // Required argument
        if (!isset($args['path'])) {
            $this->error("Error: --path is required");
            $this->output("Usage: backup --path=/path/to/backup");
            return 1;
        }
        
        $path = $args['path'];
        
        // Validate path
        if (!is_writable(dirname($path))) {
            $this->error("Error: Path is not writable: {$path}");
            return 1;
        }
        
        // Perform backup...
        
        return 0;
    }
}
```

### Interactive Input

```php
class InitCommand extends AbstractCommand
{
    protected string $name = 'init';
    
    public function execute(array $args = []): int
    {
        // Prompt for input
        $this->output("Project name: ");
        $name = trim(fgets(STDIN));
        
        $this->output("Author email: ");
        $email = trim(fgets(STDIN));
        
        // Confirm action
        $this->output("Create project '{$name}'? (y/n): ");
        $confirm = trim(fgets(STDIN));
        
        if (strtolower($confirm) !== 'y') {
            $this->output("Cancelled.");
            return 0;
        }
        
        // Create project...
        
        return 0;
    }
}
```

## Advanced Commands

### Progress Indicators

```php
class ImportCommand extends AbstractCommand
{
    protected string $name = 'import';
    
    public function execute(array $args = []): int
    {
        $file = $args['file'] ?? null;
        
        if (!$file || !file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }
        
        $lines = file($file);
        $total = count($lines);
        
        $this->output("Importing {$total} records...");
        
        foreach ($lines as $index => $line) {
            // Import record...
            
            // Show progress
            $percent = round(($index + 1) / $total * 100);
            $this->output("\rProgress: {$percent}%", false);
        }
        
        $this->output(""); // New line
        $this->success("Import complete!");
        
        return 0;
    }
}
```

### Scheduling Commands

```php
class SchedulerCommand extends AbstractCommand
{
    protected string $name = 'schedule:run';
    
    public function __construct(
        private Application $app
    ) {
    }
    
    public function execute(array $args = []): int
    {
        $schedule = [
            '0 0 * * *' => 'backup',           // Daily at midnight
            '0 */6 * * *' => 'cache:clear',    // Every 6 hours
            '*/5 * * * *' => 'queue:work',     // Every 5 minutes
        ];
        
        foreach ($schedule as $cron => $command) {
            if ($this->shouldRun($cron)) {
                $this->output("Running: {$command}");
                $this->app->run(['cli.php', $command]);
            }
        }
        
        return 0;
    }
    
    private function shouldRun(string $cron): bool
    {
        // Parse cron expression and check if should run now
        return true; // Simplified
    }
}
```

### Multi-Step Commands

```php
class SetupCommand extends AbstractCommand
{
    protected string $name = 'setup';
    
    public function execute(array $args = []): int
    {
        $steps = [
            'Database Setup' => [$this, 'setupDatabase'],
            'Create Admin User' => [$this, 'createAdmin'],
            'Seed Demo Data' => [$this, 'seedData'],
            'Build Assets' => [$this, 'buildAssets'],
        ];
        
        foreach ($steps as $name => $callback) {
            $this->output("Step: {$name}");
            
            try {
                $callback();
                $this->success("  ✓ Complete");
            } catch (Exception $e) {
                $this->error("  ✗ Failed: " . $e->getMessage());
                return 1;
            }
            
            $this->output("");
        }
        
        $this->success("Setup complete!");
        
        return 0;
    }
    
    private function setupDatabase(): void
    {
        // Setup logic...
    }
    
    private function createAdmin(): void
    {
        // Create admin...
    }
}
```

## Best Practices

### 1. Return Proper Exit Codes

```php
public function execute(array $args = []): int
{
    try {
        // Command logic...
        return 0; // Success
    } catch (Exception $e) {
        $this->error($e->getMessage());
        return 1; // Error
    }
}
```

### 2. Provide Help Text

```php
class MyCommand extends AbstractCommand
{
    protected string $name = 'my:command';
    protected string $description = 'Does something useful';
    
    public function getHelp(): string
    {
        return <<<HELP
Usage: my:command [options]

Options:
  --name=NAME     Specify name
  --force         Force execution
  -v, --verbose   Verbose output

Examples:
  my:command --name=John
  my:command --force -v
HELP;
    }
}
```

### 3. Handle Errors Gracefully

```php
public function execute(array $args = []): int
{
    try {
        $this->doWork();
        return 0;
    } catch (ValidationException $e) {
        $this->error("Validation failed: " . $e->getMessage());
        return 2;
    } catch (Exception $e) {
        $this->error("Unexpected error: " . $e->getMessage());
        $this->logger->error('Command failed', ['exception' => $e]);
        return 1;
    }
}
```

### 4. Use Dependency Injection

```php
class ReportCommand extends AbstractCommand
{
    public function __construct(
        private ReportGenerator $generator,
        private EmailService $emailer
    ) {
    }
    
    public function execute(array $args = []): int
    {
        $report = $this->generator->generate();
        $this->emailer->send($report);
        return 0;
    }
}
```

### 5. Log Command Execution

```php
public function execute(array $args = []): int
{
    $this->logger->info('Command started', [
        'command' => $this->name,
        'args' => $args
    ]);
    
    try {
        // Execute...
        
        $this->logger->info('Command completed', [
            'command' => $this->name
        ]);
        
        return 0;
    } catch (Exception $e) {
        $this->logger->error('Command failed', [
            'command' => $this->name,
            'exception' => $e
        ]);
        
        return 1;
    }
}
```

## Related Documentation

- [Architecture Overview](./architecture.md) - Framework architecture
- [Model & Database](./model.md) - Database operations in CLI
- [Configuration](./configuration.md) - CLI configuration

---

[← Back to Documentation Index](./README.md) | [Next: Configuration →](./configuration.md)
