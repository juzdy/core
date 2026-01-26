# Getting Started with JUZDY Core

This guide will help you get started with JUZDY Core framework quickly.

## Table of Contents

- [Installation](#installation)
- [Basic Setup](#basic-setup)
- [Your First Application](#your-first-application)
- [Configuration](#configuration)
- [Next Steps](#next-steps)

## Installation

### Requirements

- PHP 8.2 or higher
- Composer
- A web server (Apache, Nginx, or PHP built-in server)

### Install via Composer

```bash
composer require juzdy/core
```

### Project Structure

Create the following directory structure:

```
your-project/
├── public/
│   └── index.php          # Entry point
├── src/
│   ├── Handler/           # Your HTTP handlers
│   └── Model/             # Your models
├── config/
│   └── app.php            # Configuration files
├── composer.json
└── vendor/
```

## Basic Setup

### 1. Create Entry Point

Create `public/index.php`:

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Juzdy\Bootstrap;
use Juzdy\Container\Container;
use Juzdy\Config;

// Load configuration
Config::load(__DIR__ . '/../config/*.php');

// Create container
$container = new Container();

// Get bootstrap instance and boot the application
$bootstrap = $container->get(Bootstrap::class);
$bootstrap->boot();
```

### 2. Create Configuration

Create `config/app.php`:

```php
<?php
return [
    'app' => [
        'name' => 'My JUZDY Application',
        'env' => 'development',
    ],
    'http' => [
        'base_url' => 'http://localhost:8000',
    ],
    'bootstrap' => [
        'discover' => false, // Enable package auto-discovery
    ],
];
```

## Your First Application

### Creating a Handler

Create `src/Handler/IndexHandler.php`:

```php
<?php
namespace App\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;

class IndexHandler extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        $data = [
            'message' => 'Welcome to JUZDY Core!',
            'version' => '1.1.0',
            'time' => date('Y-m-d H:i:s')
        ];
        
        return $this->response($data);
    }
}
```

### Setting Up Routing

The framework uses automatic routing based on class names. The `IndexHandler` will automatically be accessible at the root URL (`/`).

For a handler like `UserProfileHandler`, the route would be `/user/profile`.

### Running Your Application

Using PHP's built-in server:

```bash
cd public
php -S localhost:8000
```

Visit `http://localhost:8000` in your browser to see your application running.

## Configuration

### Configuration Files

JUZDY Core uses file-based configuration with support for:

- Glob patterns: `Config::load(__DIR__ . '/config/*.php')`
- Dot notation access: `Config::get('app.name')`
- Dynamic references: `@{key}` placeholders
- Array merging for multiple config files

### Environment-Specific Configuration

Create environment-specific config files:

```php
// config/database.php
return [
    'db' => [
        'host' => '@{DB_HOST}',  // References environment variable
        'port' => 3306,
        'name' => 'myapp',
    ],
];
```

### Accessing Configuration

```php
use Juzdy\Config;

$dbHost = Config::get('db.host');
$appName = Config::get('app.name', 'Default Name'); // With default value
```

## Working with Models

### Creating a Model

Create `src/Model/User.php`:

```php
<?php
namespace App\Model;

use Juzdy\Model;

class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';
    
    // Lifecycle hook example
    protected function _beforeSave(): void
    {
        if (!$this->get('created_at')) {
            $this->set('created_at', date('Y-m-d H:i:s'));
        }
    }
}
```

### Using Models in Handlers

```php
class UserHandler extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        $user = new User();
        $user->set('name', 'John Doe');
        $user->set('email', 'john@example.com');
        $user->save();
        
        return $this->response([
            'user' => $user->toArray()
        ]);
    }
}
```

## Dependency Injection

The framework uses a powerful DI container. Dependencies are automatically injected:

```php
use Psr\EventDispatcher\EventDispatcherInterface;
use App\Service\UserService;

class UserHandler extends Handler
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private UserService $userService
    ) {
    }
    
    public function handle(RequestInterface $request): ResponseInterface
    {
        $users = $this->userService->getAllUsers();
        return $this->response(['users' => $users]);
    }
}
```

## Next Steps

Now that you have a basic application running, explore these topics:

1. **[Architecture Overview](./architecture.md)** - Understand the framework's design
2. **[Dependency Injection Container](./container.md)** - Master the DI system
3. **[HTTP Handling & Middleware](./http.md)** - Learn about request processing
4. **[Event System](./events.md)** - Implement event-driven features
5. **[Model & Database](./model.md)** - Work with databases and ORM
6. **[Layout & Views](./layout.md)** - Render templates
7. **[CLI Commands](./cli.md)** - Create command-line tools

## Common Issues

### Issue: Class Not Found

**Solution**: Make sure your `composer.json` has the correct autoload configuration:

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    }
}
```

Run `composer dump-autoload` after changes.

### Issue: Container Cannot Resolve Dependency

**Solution**: Ensure the class exists and can be autowired, or register it explicitly using attributes:

```php
use Juzdy\Container\Attribute\Preference;

#[Preference([
    MyInterface::class => MyImplementation::class
])]
class Bootstrap extends \Juzdy\Bootstrap
{
    // ...
}
```

## Additional Resources

- [Main README](../README.md)
- [Full Documentation](./README.md)
- [GitHub Repository](https://github.com/juzdy/core)

---

[← Back to Documentation Index](./README.md) | [Next: Architecture Overview →](./architecture.md)
